<?php

namespace App\Jobs;

use App\Imports\RawStudentImport;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentImportRecord;
use App\Services\Academic\Students\StudentService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessStudentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    protected ?Collection $sectionsCache = null;

    public function __construct(private readonly int $importRecordId) {}

    public function handle(StudentService $studentService): void
    {
        $record = StudentImportRecord::find($this->importRecordId);

        if (!$record) {
            Log::error("StudentImportRecord no encontrado: {$this->importRecordId}");
            return;
        }

        $record->update(['status' => 'processing']);

        try {
            if (!Storage::exists($record->file_path)) {
                throw new \Exception("El archivo {$record->file_path} no se encuentra en el disco.");
            }

            $absolutePath = Storage::path($record->file_path);
            $rows = Excel::toCollection(new RawStudentImport, $absolutePath)->first();

            if (!$rows || $rows->isEmpty()) {
                throw new \Exception('El archivo está vacío o no tiene un formato válido (pestaña vacía).');
            }

            $mapping        = $record->mapping ?? [];
            $schoolId       = $record->school_id;
            $defaultSection = $record->default_section_id;
            $total          = $rows->count();

            $record->update(['total_rows' => $total]);

            $errors      = [];
            $success     = 0;
            $waitingRoom = 0;
            $processed   = 0;

            foreach ($rows->chunk(100) as $chunk) {
                foreach ($chunk as $row) {
                    $rowArray = $row->toArray();
                    try {
                        $mapped            = $this->applyMapping($rowArray, $mapping);
                        $sectionResolution = $this->resolveSection(
                            rawSectionName:   $mapped['sigerd_section'] ?? null,
                            schoolId:         $schoolId,
                            defaultSectionId: $defaultSection,
                        );
                        $this->importRow($mapped, $sectionResolution, $schoolId, $studentService);
                        $success++;
                        if ($sectionResolution['section_id'] === null) {
                            $waitingRoom++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = ['data' => $rowArray, 'error' => $e->getMessage()];
                    }
                    $processed++;
                }

                $record->update([
                    'processed_rows'    => $processed,
                    'success_rows'      => $success,
                    'failed_rows'       => count($errors),
                    'waiting_room_rows' => $waitingRoom,
                ]);
            }

            $record->update([
                'status'            => 'completed',
                'waiting_room_rows' => $waitingRoom,
                'errors'            => $errors,
            ]);

        } catch (\Exception $e) {
            Log::error("Error fatal importando estudiantes (Record: {$this->importRecordId}): " . $e->getMessage());

            $record->update([
                'status' => 'failed',
                'errors' => [['error' => 'Error Crítico: ' . $e->getMessage()]],
            ]);
        }
    }

    private function applyMapping(array $row, array $mapping): array
    {
        $result = ['minerd_id' => null];

        foreach ($mapping as $csvKey => $orvianField) {
            if ($orvianField && array_key_exists($csvKey, $row)) {
                $result[$orvianField] = trim((string) ($row[$csvKey] ?? ''));
            }
        }
        return $result;
    }

    /**
     * Intenta resolver la sección de ORVIAN a partir del nombre crudo del curso en SIGERD.
     *
     * Estrategia (en orden de prioridad):
     * 1. Match fuzzy normalizado contra grade.name + label
     * 2. Sección por defecto del wizard (si está configurada)
     * 3. Sala de Espera: section_id = null, nombre crudo guardado en metadata
     *
     * @return array{section_id: int|null, metadata_sigerd: string|null, resolved: bool}
     */
    protected function resolveSection(
        ?string $rawSectionName,
        int $schoolId,
        ?int $defaultSectionId = null
    ): array {
        if (empty($rawSectionName)) {
            return [
                'section_id'      => $defaultSectionId,
                'metadata_sigerd' => null,
                'resolved'        => $defaultSectionId !== null,
            ];
        }

        $normalized = $this->normalizeSectionName($rawSectionName);
        $sections   = $this->getSectionsCache($schoolId);

        $matched = $sections->first(function ($section) use ($normalized) {
            $sectionLabel = $this->normalizeSectionName(
                $section->grade->name . ' ' . $section->label
            );
            return $sectionLabel === $normalized
                || (str_contains($normalized, strtolower($section->label))
                    && str_contains($normalized, strtolower(substr($section->grade->name, 0, 3))));
        });

        if ($matched) {
            return [
                'section_id'      => $matched->id,
                'metadata_sigerd' => null,
                'resolved'        => true,
            ];
        }

        if ($defaultSectionId) {
            return [
                'section_id'      => $defaultSectionId,
                'metadata_sigerd' => $rawSectionName,
                'resolved'        => true,
            ];
        }

        return [
            'section_id'      => null,
            'metadata_sigerd' => $rawSectionName,
            'resolved'        => false,
        ];
    }

    protected function normalizeSectionName(string $name): string
    {
        $name = mb_strtolower($name);
        $name = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $name
        );
        return preg_replace('/\s+/', ' ', trim($name));
    }

    protected function getSectionsCache(int $schoolId): Collection
    {
        if ($this->sectionsCache === null) {
            $this->sectionsCache = SchoolSection::with(['grade'])
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->get();
        }
        return $this->sectionsCache;
    }

    private function importRow(array $data, array $sectionResolution, int $schoolId, StudentService $studentService): void
    {
        $firstName = trim($data['first_name'] ?? '');
        $lastName  = trim($data['last_name'] ?? '');
        $minerdId  = trim($data['minerd_id'] ?? '');

        if ($firstName === '' || $lastName === '') {
            throw new \InvalidArgumentException('El nombre y apellido son obligatorios.');
        }

        $sectionId = $sectionResolution['section_id'];
        $cleanRnc  = !empty($data['rnc']) ? preg_replace('/[^0-9]/', '', $data['rnc']) : null;
        $gender    = strtoupper($data['gender'] ?? '');

        if (!in_array($gender, ['M', 'F'])) {
            $gender = null;
        }

        $dateOfBirth    = !empty($data['date_of_birth']) ? $this->parseDate($data['date_of_birth'])?->toDateString() : null;
        $enrollmentDate = !empty($data['enrollment_date']) ? $this->parseDate($data['enrollment_date'])?->toDateString() : null;

        $searchAttributes = ['school_id' => $schoolId];

        if (!empty($minerdId)) {
            $searchAttributes['metadata->minerd_id'] = $minerdId;
        } elseif ($cleanRnc) {
            $searchAttributes['rnc'] = $cleanRnc;
        } else {
            $searchAttributes['first_name']    = $firstName;
            $searchAttributes['last_name']     = $lastName;
            $searchAttributes['date_of_birth'] = $dateOfBirth;
        }

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
            'tutor_name'         => $data['tutor_name'] ?? null ?: null,
            'tutor_phone'        => $this->normalizePhone($data['tutor_phone'] ?? null),
        ];

        $student          = Student::firstOrNew($searchAttributes);
        $existingMetadata = $student->exists ? ($student->metadata ?? []) : [];

        $newMeta = ['imported_from' => 'sigerd', 'imported_at' => now()->toISOString()];
        if (!empty($minerdId)) {
            $newMeta['minerd_id'] = $minerdId;
        }
        if ($sectionResolution['metadata_sigerd'] !== null) {
            $newMeta['sigerd_section'] = $sectionResolution['metadata_sigerd'];
        }
        $newMeta['section_resolved'] = $sectionResolution['resolved'];

        $updateAttributes['metadata'] = array_merge($existingMetadata, $newMeta);

        $student->fill($updateAttributes);
        $student->save();
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) return null;

        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }

        if (strlen($digits) > 10) {
            return '+' . $digits;
        }

        return null;
    }

    private function parseDate(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }
}
