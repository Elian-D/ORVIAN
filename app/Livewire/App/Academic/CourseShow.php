<?php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class CourseShow extends Component
{
    use WithPagination;

    public SchoolSection $section;

    // Campos de edición inline
    public string $editingLabel   = '';
    public ?int   $editingShiftId = null;
    public bool   $isEditing      = false;

    public function mount(SchoolSection $section): void
    {
        // Guard: la sección debe pertenecer a la escuela del usuario
        abort_if($section->school_id !== Auth::user()->school_id, 403);

        $this->section = $section->load([
            'grade.level',
            'shift',
            'technicalTitle.family',
        ]);

        $this->editingLabel   = $section->label;
        $this->editingShiftId = $section->school_shift_id;
    }

    #[Computed]
    public function students(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->section->students()
            ->with('user:id,email')
            ->orderBy('last_name')
            ->paginate(20);
    }

    #[Computed]
    public function shifts(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolShift::where('school_id', Auth::user()->school_id)->get();
    }

    #[Computed]
    public function stats(): array
    {
        $students = $this->section->students();
        return [
            'total'    => $students->count(),
            'active'   => $students->where('is_active', true)->count(),
            'inactive' => $students->where('is_active', false)->count(),
        ];
    }

    public function startEdit(): void
    {
        $this->isEditing = true;
    }

    public function cancelEdit(): void
    {
        $this->isEditing      = false;
        $this->editingLabel   = $this->section->label;
        $this->editingShiftId = $this->section->school_shift_id;
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editingLabel'   => 'required|string|max:10',
            'editingShiftId' => 'required|integer|exists:school_shifts,id',
        ]);

        $this->section->update([
            'label'           => strtoupper(trim($this->editingLabel)),
            'school_shift_id' => $this->editingShiftId,
        ]);

        $this->section->refresh();
        $this->isEditing = false;
        $this->dispatch('notify', type: 'success', message: 'Sección actualizada.');
    }

    public function toggleStatus(): void
    {
        if ($this->section->is_active
            && $this->section->students()->where('is_active', true)->exists()) {
            $this->dispatch('notify', type: 'error',
                message: 'No se puede desactivar: tiene estudiantes activos.');
            return;
        }

        $this->section->update(['is_active' => ! $this->section->is_active]);
        $this->section->refresh();

        $msg = $this->section->is_active ? 'Sección reactivada.' : 'Sección desactivada.';
        $this->dispatch('notify', type: 'info', message: $msg);
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.academic.course-show');

        return $view->layout('layouts.app-module', config('modules.academico'));
    }
}