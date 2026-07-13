<?php

namespace App\Livewire\App\Attendance;

use App\Filters\App\Attendance\Excuse\ExcuseFilters;
use App\Livewire\Base\DataTable;
use App\Models\Tenant\AttendanceExcuse;
use App\Services\Attendance\ExcuseService;
use App\Tables\App\Attendance\ExcuseTableConfig;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Gestión de Excusas')]
class ExcuseIndex extends DataTable
{
    // ── Filtros (Sync con URL) ────────────────────────────────────
    #[Url]
    public array $filters = [
        'student'    => '',
        'status'     => '',
        'date_range' => ['from' => '', 'to' => ''],
    ];

    // Compartido por los dos modales (confirmar / cancelar) — solo uno
    // puede estar abierto a la vez, así que un único id seleccionado alcanza.
    public ?int $selectedId = null;

    // ── Propiedades para Confirmación (pending → confirmed) ─────────
    public bool $showConfirmModal = false;

    // ── Propiedades para Cancelación (confirmed → cancelled) ────────
    public bool   $showCancelModal = false;
    public string $cancelNotes     = '';

    protected function getTableDefinition(): string
    {
        return ExcuseTableConfig::class;
    }

    /**
     * Excusa seleccionada para confirmar o cancelar, con los datos del
     * estudiante (incluida la foto) para el resumen del modal.
     */
    #[Computed]
    public function selectedExcuse(): ?AttendanceExcuse
    {
        return $this->selectedId
            ? AttendanceExcuse::with([
                'student:id,first_name,last_name,school_id,school_section_id,photo_path',
                'student.section:id,school_id,label,grade_id,technical_title_id',
                'student.section.grade:id,name',
                'student.section.technicalTitle:id,name,short_name',
            ])->find($this->selectedId)
            : null;
    }

    // ── Acción de Confirmación ──────────────────────────────────────

    public function openConfirm(int $id): void
    {
        $this->selectedId = $id;

        $this->showConfirmModal = true;
        $this->dispatch('open-modal', 'confirm-excuse');
    }

    public function confirm(ExcuseService $service): void
    {
        $this->authorize('manage_excuses');

        $excuse = AttendanceExcuse::findOrFail($this->selectedId);

        try {
            $service->confirmExcuse($excuse);
        } catch (\LogicException $e) {
            $this->closeConfirm($e->getMessage(), 'error');
            return;
        }

        $this->closeConfirm('Excusa confirmada y registros actualizados.');
    }

    public function closeConfirm(string $message = '', string $type = 'success'): void
    {
        $this->showConfirmModal = false;

        if (!empty($message)) {
            $this->dispatch('notify', type: $type, message: $message);
        }

        $this->dispatch('close-modal', 'confirm-excuse');
    }

    // ── Acción de Cancelación ──────────────────────────────────────

    public function openCancel(int $id): void
    {
        $this->selectedId  = $id;
        $this->cancelNotes = '';
        $this->resetValidation();

        $this->showCancelModal = true;
        $this->dispatch('open-modal', 'cancel-excuse');
    }

    public function cancel(ExcuseService $service): void
    {
        $this->authorize('manage_excuses');

        $this->validate(['cancelNotes' => 'required|string|min:5']);

        $excuse = AttendanceExcuse::findOrFail($this->selectedId);

        try {
            $service->cancelExcuse($excuse, $this->cancelNotes);
        } catch (\LogicException $e) {
            $this->closeCancel($e->getMessage(), 'error');
            return;
        }

        $this->closeCancel('La excusa ha sido cancelada.', 'info');
    }

    public function closeCancel(string $message = '', string $type = 'success'): void
    {
        $this->showCancelModal = false;

        if (!empty($message)) {
            $this->dispatch('notify', type: $type, message: $message);
        }

        $this->dispatch('close-modal', 'cancel-excuse');
    }

    // ── Renderizado ───────────────────────────────────────────────

    public function render()
    {
        $query = AttendanceExcuse::with([
            'student:id,first_name,last_name,school_id,school_section_id',
            'student.section:id,school_id,label,grade_id,technical_title_id',
            'student.section.grade:id,name',
            'student.section.technicalTitle:id,name,short_name',
            'submittedBy:id,name',
        ]);

        $excuses = (new ExcuseFilters($this->filters))
            ->apply($query)
            ->latest()
            ->paginate($this->perPage);

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.excuse-index', [
            'excuses' => $excuses,
        ]);

        return $view->layout('layouts.app-module', config('modules.asistencia'));
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
                'pending'   => 'Pendiente',
                'confirmed' => 'Confirmado',
                'cancelled' => 'Cancelado',
                default     => (string)$value
            };
        }

        // 4. Comportamiento por defecto para otros filtros
        return is_array($value) ? '' : (string) $value;
    }
}