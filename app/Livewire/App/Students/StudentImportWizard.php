<?php

namespace App\Livewire\App\Students;

use App\Imports\RawStudentImport;
use App\Jobs\ProcessStudentImport;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\StudentImportRecord;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportWizard extends Component
{
    use WithFileUploads, AuthorizesRequests;

    public int $step = 1;

    // Paso 1
    public $file;
    public array $headers = [];

    // Paso 2
    public array $mapping = [];
    public ?int $defaultSectionId = null;

    // Paso 3
    public ?int $importRecordId = null;

    const ORVIAN_FIELDS = [
        'first_name'         => 'Nombres',
        'last_name'          => 'Apellidos',
        'rnc'                => 'Cédula / RNC',
        'gender'             => 'Género (M/F)',
        'date_of_birth'      => 'Fecha de Nacimiento',
        'place_of_birth'     => 'Lugar de Nacimiento',
        'blood_type'         => 'Tipo de Sangre',
        'allergies'          => 'Alergias',
        'medical_conditions' => 'Condiciones Médicas',
        'enrollment_date'    => 'Fecha de Ingreso',
        'section_name'       => 'Sección (normalización automática)',
    ];

    #[Computed]
    public function importRecord(): ?StudentImportRecord
    {
        if (!$this->importRecordId) return null;
        return StudentImportRecord::find($this->importRecordId);
    }

    #[Computed]
    public function sections()
    {
        return SchoolSection::withFullRelations()
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => $s->full_label]);
    }

    public function uploadFile(): void
    {
        $this->authorize('students.import');

        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.mimes'    => 'Solo se aceptan archivos Excel (.xlsx, .xls) o CSV.',
            'file.max'      => 'El archivo no puede superar los 10 MB.',
        ]);

        try {
            $rows = Excel::toCollection(new RawStudentImport, $this->file->getRealPath())->first();

            if (!$rows || $rows->isEmpty()) {
                $this->dispatch('notify', type: 'error', title: 'Archivo vacío', message: 'El archivo no contiene datos.');
                return;
            }

            $this->headers = array_keys($rows->first()->toArray());
            $this->mapping = [];
            foreach ($this->headers as $header) {
                $this->mapping[$header] = $this->autoDetectField($header);
            }

            $this->step = 2;

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', title: 'Error al leer el archivo', message: $e->getMessage());
        }
    }

    public function startImport(): void
    {
        $this->authorize('students.import');

        $this->validate([
            'defaultSectionId' => 'nullable|exists:school_sections,id',
        ]);

        $mappedFields = array_filter($this->mapping);
        if (!in_array('first_name', $mappedFields) || !in_array('last_name', $mappedFields)) {
            $this->dispatch('notify',
                type: 'error',
                title: 'Campos requeridos sin mapear',
                message: 'Debes mapear al menos los campos Nombres y Apellidos.'
            );
            return;
        }

        $schoolId  = Auth::user()->school_id;
        $extension = $this->file->getClientOriginalExtension();

        $stored = $this->file->storeAs(
            "imports/students/{$schoolId}",
            'import_' . now()->timestamp . '.' . $extension
        );

        $importRecord = StudentImportRecord::create([
            'school_id'          => $schoolId,
            'created_by'         => Auth::id(),
            'status'             => 'pending',
            'file_path'          => $stored,
            'mapping'            => $this->mapping,
            'default_section_id' => $this->defaultSectionId,
        ]);

        $this->importRecordId = $importRecord->id;
        ProcessStudentImport::dispatch($importRecord->id);

        $this->step = 3;
        $this->dispatch('notify', type: 'info', title: 'Importación iniciada', message: 'El proceso está corriendo en segundo plano.');
    }

    public function downloadErrors(): StreamedResponse
    {
        $errors = $this->importRecord?->errors ?? [];

        $allKeys = [];
        foreach ($errors as $err) {
            if (!empty($err['data'])) {
                $allKeys = array_merge($allKeys, array_keys($err['data']));
            }
        }
        $allKeys = array_unique($allKeys);

        $filename = 'errores_importacion_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($errors, $allKeys) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_merge($allKeys, ['Error']));
            foreach ($errors as $err) {
                $row   = array_map(fn ($k) => $err['data'][$k] ?? '', $allKeys);
                $row[] = $err['error'] ?? '';
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function resetWizard(): void
    {
        $this->reset(['step', 'file', 'headers', 'mapping', 'defaultSectionId', 'importRecordId']);
    }

    private function autoDetectField(string $header): string
    {
        $h = strtolower($header);
        return match (true) {
            str_contains($h, 'primer') && str_contains($h, 'nombre') => 'first_name',
            str_contains($h, 'nombre') && !str_contains($h, 'apellido') => 'first_name',
            str_contains($h, 'apellido') => 'last_name',
            str_contains($h, 'cedula') || str_contains($h, 'cédula') || str_contains($h, 'rnc') => 'rnc',
            str_contains($h, 'sexo') || str_contains($h, 'genero') || str_contains($h, 'género') || $h === 'sex' => 'gender',
            str_contains($h, 'nacimiento') || str_contains($h, 'birth') => 'date_of_birth',
            str_contains($h, 'lugar') || str_contains($h, 'place') => 'place_of_birth',
            str_contains($h, 'sangre') || str_contains($h, 'blood') => 'blood_type',
            str_contains($h, 'alergia') => 'allergies',
            str_contains($h, 'medic') || str_contains($h, 'condic') => 'medical_conditions',
            str_contains($h, 'seccion') || str_contains($h, 'sección') || str_contains($h, 'grado') || str_contains($h, 'curso') => 'section_name',
            str_contains($h, 'ingreso') || str_contains($h, 'matric') => 'enrollment_date',
            default => '',
        };
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.students.student-import-wizard');
        
        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }
}
