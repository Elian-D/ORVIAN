<?php

namespace App\Livewire\App\Teachers;

use App\Filters\App\Teachers\TeacherFilters;
use App\Livewire\Base\DataTable;
use App\Models\Tenant\Teacher;
use App\Tables\App\TeacherTableConfig;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class TeacherIndex extends DataTable
{
    use AuthorizesRequests;

    #[Url]
    public array $filters = [
        'search'          => '',
        'status'          => '',
        'employment_type' => '',
        'has_user'        => true,
    ];

    // Modales
    public bool $showTerminateModal   = false;
    public bool $showReactivateModal  = false;

    public ?int $selectedTeacherId    = null;
    public string $termination_date   = '';
    public string $termination_reason = '';

    protected function getTableDefinition(): string
    {
        return TeacherTableConfig::class;
    }

    /**
     * Computed para no serializar el modelo en el snapshot Livewire.
     */
    #[Computed]
    public function selectedTeacher(): ?Teacher
    {
        return $this->selectedTeacherId
            ? Teacher::find($this->selectedTeacherId)
            : null;
    }

    // ── Acciones ──────────────────────────────────────────

    public function confirmTerminate(int $id): void
    {
        $this->selectedTeacherId  = $id;
        $this->termination_date   = now()->format('Y-m-d');
        $this->termination_reason = '';
        $this->showTerminateModal = true;
        $this->dispatch('open-modal', 'terminate-modal');
    }

    public function terminate(): void
    {
        $this->validate([
            'termination_date'   => 'required|date',
            'termination_reason' => 'nullable|string|max:1000',
        ]);

        $teacher = Teacher::findOrFail($this->selectedTeacherId);
        $teacher->update([
            'is_active'          => false,
            'termination_date'   => $this->termination_date,
            'termination_reason' => $this->termination_reason,
        ]);

        $this->dispatch('notify', type: 'info', message: "{$teacher->full_name} ha sido dado de baja.");
        $this->closeModals();
    }

    public function confirmReactivate(int $id): void
    {
        $this->selectedTeacherId  = $id;
        $this->showReactivateModal = true;
        $this->dispatch('open-modal', 'reactivate-teacher-modal');
    }

    public function reactivate(): void
    {
        $teacher = Teacher::findOrFail($this->selectedTeacherId);
        $teacher->update([
            'is_active'          => true,
            'termination_date'   => null,
            'termination_reason' => null,
        ]);

        $this->dispatch('notify', type: 'success', message: "{$teacher->full_name} ha sido reactivado.");
        $this->closeModals();
    }

    public function closeModals(): void
    {
        $this->reset([
            'showTerminateModal', 'showReactivateModal',
            'selectedTeacherId', 'termination_date', 'termination_reason',
        ]);
        $this->dispatch('close-modal', 'terminate-modal');
        $this->dispatch('close-modal', 'reactivate-teacher-modal');
    }

    // ── Render ────────────────────────────────────────────

    public function render()
    {
        $teachers = (new TeacherFilters($this->filters))
            ->apply(Teacher::withIndexRelations()->withCount('assignments'))
            ->latest()
            ->paginate($this->perPage);


        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.teachers.teacher-index', [
            'teachers' => $teachers,
        ]);

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'status'          => $value === '1' ? 'Activos' : 'Inactivos',
            'employment_type' => $value === 'full_time' ? 'Tiempo Completo' : 'Tiempo Parcial',
            'has_user'        => 'Con acceso al sistema',
            default           => parent::formatFilterValue($key, $value),
        };
    }
}