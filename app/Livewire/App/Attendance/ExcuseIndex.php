<?php

namespace App\Livewire\App\Attendance;

use App\Filters\App\Attendance\Excuse\ExcuseFilters;
use App\Livewire\Base\DataTable;
use App\Models\Tenant\AttendanceExcuse;
use App\Models\Tenant\Student;
use App\Services\Attendance\ExcuseService;
use App\Tables\App\Attendance\ExcuseTableConfig;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

#[Title('Gestión de Excusas')]
class ExcuseIndex extends DataTable
{
    use WithFileUploads;

    // ── Filtros (Sync con URL) ────────────────────────────────────
    #[Url]
    public array $filters = [
        'student'    => '',
        'status'     => '',
        'date_range' => ['from' => '', 'to' => ''],
    ];

    // ── Propiedades para el Formulario (Slide-over) ───────────────
    public bool   $showPanel  = false;
    public int    $studentId  = 0;
    public string $dateStart  = '';
    public string $dateEnd    = '';
    public string $type       = 'full_absence';
    public string $reason     = '';
    public        $attachment = null;

    // ── Propiedades para Revisión ─────────────────────────────────
    public bool   $showReview   = false;
    public ?int   $selectedId   = null;
    public string $reviewNotes  = '';
    public string $reviewAction = ''; // 'approve' | 'reject'

    protected function getTableDefinition(): string
    {
        return ExcuseTableConfig::class;
    }

    // ── Acciones de Panel ─────────────────────────────────────────
    public function submit(ExcuseService $service): void
    {
        $this->authorize('excuses.submit');

        $validated = $this->validate([
            'studentId'  => 'required|integer|exists:students,id',
            'dateStart'  => 'required|date',
            'dateEnd'    => 'required|date|after_or_equal:dateStart',
            'type'       => 'required|string',
            'reason'     => 'required|string|min:10|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Obtenemos el ID de la escuela del usuario autenticado
        $schoolId = Auth::user()->school_id;

        $data = [
            'school_id'  => $schoolId,
            'student_id' => $this->studentId,
            'date_start' => $this->dateStart,
            'date_end'   => $this->dateEnd,
            'type'       => $this->type,
            'reason'     => $this->reason,
        ];

        if ($this->attachment) {
            // Guardamos siguiendo el patrón: schools/{school_id}/excuses
            $data['attachment_path'] = $this->attachment->store(
                "schools/{$schoolId}/excuses", 
                'public'
            );
        }

        $service->submitExcuse($data);

        // Resetear formulario y cerrar panel
        $this->resetForm();
        $this->dispatch('close-modal', 'register-excuse'); 
        
        $this->dispatch('notify', type: 'success', message: 'Excusa registrada correctamente.');
    }

    // ── Acciones de Revisión ──────────────────────────────────────

    public function openReview(int $id, string $action): void
    {
        $this->selectedId   = $id;
        $this->reviewAction = $action;
        $this->reviewNotes  = '';
        
        // 1. Activamos la variable de Livewire (por seguridad)
        $this->showReview = true; 
        
        // 2. DISPARAMOS EL EVENTO QUE ALPINE ENTIENDE
        $this->dispatch('open-modal', 'review-excuse'); 
    }

    public function processReview(ExcuseService $service): void
    {
        if ($this->reviewAction === 'approve') {
            $this->approve($service);
        } else {
            $this->reject($service);
        }
        
        // Cerramos el modal usando el evento de Alpine
        $this->dispatch('close-modal', 'review-excuse');
    }

    public function approve(ExcuseService $service): void
    {
        $this->authorize('excuses.approve');
        
        $excuse = AttendanceExcuse::findOrFail($this->selectedId);
        $service->approveExcuse($excuse, $this->reviewNotes ?: null);

        $this->closeReview('Excusa aprobada y registros actualizados.');
    }

    public function reject(ExcuseService $service): void
    {
        $this->authorize('excuses.reject');
        
        $this->validate(['reviewNotes' => 'required|string|min:5']);

        $excuse = AttendanceExcuse::findOrFail($this->selectedId);
        $service->rejectExcuse($excuse, $this->reviewNotes);

        $this->closeReview('La excusa ha sido rechazada.', 'info');
    }

    public function closeReview(string $message = '', string $type = 'success'): void
    {
        $this->showReview = false;
        
        // Solo disparamos la notificación si hay un mensaje
        if (!empty($message)) {
            $this->dispatch('notify', type: $type, message: $message);
        }

        // Opcional: Asegurarnos de que el modal de Alpine se cierre
        $this->dispatch('close-modal', 'review-excuse');
    }

    private function resetForm(): void
    {
        $this->reset(['studentId', 'dateStart', 'dateEnd', 'type', 'reason', 'attachment']);
        $this->resetValidation();
    }

    // ── Renderizado ───────────────────────────────────────────────

    public function render()
    {
        $query = AttendanceExcuse::with([
            'student:id,first_name,last_name,rnc,school_id,school_section_id', // Agregamos foreign keys
            'submittedBy:id,name'
        ]);

        $excuses = (new ExcuseFilters($this->filters))
            ->apply($query)
            ->latest()
            ->paginate($this->perPage);

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.excuse-index', [
            'excuses'  => $excuses,
            'students' => Student::active()->select('id', 'first_name', 'last_name')->get(),
        ]);

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }

    public function clearFilter(string $key): void
    {
        if ($key === 'date_range') {
            // En lugar de asignar ['', ''], eliminamos la entrada. 
            // Livewire es suficientemente inteligente para manejar esto.
            unset($this->filters['date_range']);
            $this->resetPage();
            return;
        }

        parent::clearFilter($key);
    }

    public function clearAllFilters(): void
    {
        // Reseteamos a los valores iniciales pero sin las llaves de los filtros opcionales
        $this->filters = [
            'student'    => '',
            'status'     => '',
            // No incluimos date_range aquí para que no genere un chip vacío
        ];
        $this->resetPage();
    }

    /**
     * Sobrescribimos el formateo de filtros para manejar el array de fechas
     * y convertir IDs de estudiantes en nombres legibles en los Chips.
     */
    protected function formatFilterValue(string $key, mixed $value): string
    {
        // 1. Manejo de Rango de Fechas (Array asociativo: from, to)
        if ($key === 'date_range' && is_array($value)) {
            $from = !empty($value['from']) ? \Carbon\Carbon::parse($value['from'])->format('d/m/Y') : null;
            $to = !empty($value['to']) ? \Carbon\Carbon::parse($value['to'])->format('d/m/Y') : null;

            if ($from && $to) {
                return "{$from} - {$to}";
            } elseif ($from) {
                return "Desde: {$from}";
            } elseif ($to) {
                return "Hasta: {$to}";
            }
            
            return ''; 
        }
        // 2. Manejo de Estudiante (ID a Nombre)
        if ($key === 'student' && !empty($value)) {
            $student = \App\Models\Tenant\Student::find($value);
            return $student ? "{$student->first_name} {$student->last_name}" : (string)$value;
        }

        // 3. Manejo de Estados
        if ($key === 'status' && !empty($value)) {
            return match($value) {
                'pending'  => 'Pendiente',
                'approved' => 'Aprobado',
                'rejected' => 'Rechazado',
                default    => (string)$value
            };
        }

        // 4. Comportamiento por defecto para otros filtros
        return is_array($value) ? '' : (string) $value;
    }
}