<?php

namespace App\Jobs;

use App\Imports\RawStudentImport;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentImportRecord;
use App\Services\Students\StudentService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessStudentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(private readonly int $importRecordId) {}

    public function handle(StudentService $studentService): void
    {
        $record = StudentImportRecord::find($this->importRecordId);
        
        // Prevención de errores si el registro ya no existe
        if (!$record) {
            Log::error("StudentImportRecord no encontrado: {$this->importRecordId}");
            return;
        }

        $record->update(['status' => 'processing']);

        try {
            // Verificar que el archivo realmente exista antes de intentar leerlo
            if (!Storage::exists($record->file_path)) {
                throw new \Exception("El archivo {$record->file_path} no se encuentra en el disco.");
            }

            $absolutePath = Storage::path($record->file_path);
            
            // Usamos RawStudentImport para cargar la colección cruda
            $rows = Excel::toCollection(new RawStudentImport, $absolutePath)->first();

            if (!$rows || $rows->isEmpty()) {
                throw new \Exception('El archivo está vacío o no tiene un formato válido (pestaña vacía).');
            }

            $mapping        = $record->mapping ?? [];
            $schoolId       = $record->school_id;
            $defaultSection = $record->default_section_id;
            $total          = $rows->count();

            $record->update(['total_rows' => $total]);

            // Forma CORRECTA de llamar a un scope en Laravel
            $sections = SchoolSection::where('school_id', $schoolId)
                ->withFullRelations() // Sin el prefijo "scope" y encadenado
                ->get();

            $errors    = [];
            $success   = 0;
            $processed = 0;

            foreach ($rows->chunk(100) as $chunk) {
                foreach ($chunk as $row) {
                    $rowArray = $row->toArray();
                    try {
                        $mapped    = $this->applyMapping($rowArray, $mapping);
                        $sectionId = $this->resolveSection($mapped, $sections, $defaultSection);
                        $this->importRow($mapped, $sectionId, $schoolId, $studentService);
                        $success++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'data'  => $rowArray,
                            'error' => $e->getMessage(),
                        ];
                    }
                    $processed++;
                }

                // Actualizar progreso por chunk para que la UI se refresque
                $record->update([
                    'processed_rows' => $processed,
                    'success_rows'   => $success,
                    'failed_rows'    => count($errors),
                ]);
            }

            $record->update([
                'status' => 'completed',
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            // Capturamos cualquier error fatal (Lectura de archivo, conexión DB, etc.)
            Log::error("Error fatal importando estudiantes (Record: {$this->importRecordId}): " . $e->getMessage());
            
            $record->update([
                'status' => 'failed',
                'errors' => [['error' => 'Error Crítico: ' . $e->getMessage()]],
            ]);
        }
    }

    private function applyMapping(array $row, array $mapping): array
    {
        $result = [];
        // Nos aseguramos de inicializar minerd_id para evitar offset errors luego
        $result['minerd_id'] = null; 

        foreach ($mapping as $csvKey => $orvianField) {
            if ($orvianField && array_key_exists($csvKey, $row)) {
                $result[$orvianField] = trim((string) ($row[$csvKey] ?? ''));
            }
        }
        return $result;
    }

    private function resolveSection(array $mapped, $sections, ?int $defaultSectionId): ?int
    {
        if (!empty($mapped['section_name'])) {
            $needle = strtolower(trim($mapped['section_name']));
            $match  = $sections->first(function ($section) use ($needle) {
                return str_contains(strtolower($section->full_label), $needle)
                    || str_contains($needle, strtolower($section->label ?? ''));
            });
            if ($match) {
                return $match->id;
            }
        }
        return $defaultSectionId;
    }

    private function importRow(array $data, ?int $sectionId, int $schoolId, StudentService $studentService): void
    {
        $firstName = trim($data['first_name'] ?? '');
        $lastName  = trim($data['last_name'] ?? '');
        $minerdId  = trim($data['minerd_id'] ?? '');

        if ($firstName === '' || $lastName === '') {
            throw new \InvalidArgumentException('El nombre y apellido son obligatorios.');
        }

        $cleanRnc = !empty($data['rnc']) ? preg_replace('/[^0-9]/', '', $data['rnc']) : null;
        $gender   = strtoupper($data['gender'] ?? '');
        
        if (!in_array($gender, ['M', 'F'])) {
            $gender = null;
        }

        $dateOfBirth = !empty($data['date_of_birth']) ? $this->parseDate($data['date_of_birth'])?->toDateString() : null;
        $enrollmentDate = !empty($data['enrollment_date']) ? $this->parseDate($data['enrollment_date'])?->toDateString() : null;

        // --- LÓGICA DE UPSERT (Actualizar o Crear) ---
        
        // 1. Definir los atributos por los que buscaremos al estudiante (Criterio de unicidad)
        $searchAttributes = ['school_id' => $schoolId];
        
        if (!empty($minerdId)) {
            // Si el Excel trae minerd_id, lo usamos como clave principal de búsqueda en el JSON
            $searchAttributes['metadata->minerd_id'] = $minerdId;
        } elseif ($cleanRnc) {
            // Si no hay minerd_id pero hay RNC, usamos el RNC
            $searchAttributes['rnc'] = $cleanRnc;
        } else {
            // Si no tiene ni minerd_id ni rnc, usamos Nombre + Apellido + Fecha de Nacimiento
            // (Esto asume que no hay dos Juan Perez nacidos el mismo día en la misma escuela)
            $searchAttributes['first_name'] = $firstName;
            $searchAttributes['last_name'] = $lastName;
            $searchAttributes['date_of_birth'] = $dateOfBirth;
        }

        // 2. Definir los datos que queremos actualizar (o insertar si es nuevo)
        $updateAttributes = [
            'school_section_id'  => $sectionId,
            'first_name'         => $firstName,
            'last_name'          => $lastName,
            'rnc'                => $cleanRnc ?: null,
            'gender'             => $gender,
            'date_of_birth'      => $dateOfBirth,
            'place_of_birth'     => $data['place_of_birth'] ?? null,
            'blood_type'         => $data['blood_type'] ?? null,
            'allergies'          => $data['allergies'] ?? null,
            'medical_conditions' => $data['medical_conditions'] ?? null,
            'enrollment_date'    => $enrollmentDate,
            'is_active'          => true,
        ];

        // Manejar el minerd_id en el campo metadata
        if (!empty($minerdId)) {
            // Si estamos creando, asignamos el minerd_id. 
            // Si estamos actualizando, esto asegura que se mantenga.
            // Para no sobreescribir otros datos en 'metadata' si ya existen, lo ideal es:
            // Obtener el estudiante existente primero si usamos updateOrCreate, pero para mantenerlo 
            // simple, enviaremos esto al servicio (que debería usar Eloquent `updateOrCreate`).
            $updateAttributes['metadata'] = ['minerd_id' => $minerdId];
        }

        // Usamos Eloquent directamente para el Upsert para asegurar que el Observer se dispare (para el QR)
        $student = Student::firstOrNew($searchAttributes);
        
        // Si ya tenía metadata, mezclamos para no perder datos anteriores
        if ($student->exists && !empty($minerdId)) {
            $existingMetadata = $student->metadata ?? [];
            $existingMetadata['minerd_id'] = $minerdId;
            $updateAttributes['metadata'] = $existingMetadata;
        }

        $student->fill($updateAttributes);
        $student->save();
        
        // Nota: Si usas `$studentService->createStudent()`, asegúrate de que ese método internamente 
        // pueda manejar actualizaciones, de lo contrario, el código anterior (firstOrNew + save) 
        // es la forma correcta de hacerlo en un importador de Upsert.
    }

    private function parseDate(string $value): ?Carbon
    {
        try {
            // Para Excel, a veces las fechas vienen como un número flotante. 
            // Esto maneja formatos de string estándar.
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }
}