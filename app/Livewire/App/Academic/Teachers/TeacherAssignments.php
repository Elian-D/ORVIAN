<?php

namespace App\Livewire\App\Academic\Teachers;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\TeacherSubjectSection;
use App\Models\Tenant\Teacher;
use App\Services\Academic\Teachers\TeacherAssignmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeacherAssignments extends Component
{
    use AuthorizesRequests;

    public Teacher $teacher;

    // Formulario de nueva asignación
    public int    $selectedSubjectId = 0;
    public int    $selectedSectionId = 0;
    public int $assignmentToDelete = 0;

    public function mount(Teacher $teacher): void
    {
        $this->teacher = $teacher->load(['assignments.subject', 'assignments.section.grade']);
    }

    /**
     * Secciones disponibles en la escuela para el selector.
     */
    #[Computed]
    public function sections()
    {
        return SchoolSection::with(['grade', 'shift', 'technicalTitle'])
            ->where('school_id', $this->teacher->school_id)
            ->get();
    }

    /**
     * Materias disponibles en función de la sección seleccionada.
     * Se recalcula reactivamente cuando $selectedSectionId cambia.
     */
    #[Computed]
    public function availableSubjects()
    {
        if (! $this->selectedSectionId) return collect();

        return app(TeacherAssignmentService::class)
            ->getAvailableSubjects($this->teacher, $this->selectedSectionId);
    }

    /**
     * Asignaciones actuales agrupadas por sección para el panel izquierdo.
     */
    #[Computed]
    public function currentAssignments()
    {
        return TeacherSubjectSection::with(['subject', 'section.grade'])
            ->where('teacher_id', $this->teacher->id)
            ->where('is_active', true)
            ->get()
            ->groupBy('school_section_id');
    }

    public function removeConfirmed(TeacherAssignmentService $service): void
{
    if (!$this->assignmentToDelete) return;

    // Reutilizamos tu lógica de eliminación
    $this->remove($this->assignmentToDelete, $service);

    // Reseteamos y cerramos el modal disparando un evento al navegador
    $this->assignmentToDelete = 0;
    $this->dispatch('close-modal', 'confirm-assignment-deletion');
}

    public function assign(TeacherAssignmentService $service): void
    {
        $this->authorize('teachers.assign_subjects');

        $this->validate([
            'selectedSubjectId' => 'required|integer|exists:subjects,id',
            'selectedSectionId' => 'required|integer|exists:school_sections,id',
        ]);

        try {
            $service->assign($this->teacher, $this->selectedSubjectId, $this->selectedSectionId);
            $this->reset(['selectedSubjectId', 'selectedSectionId']);
            unset($this->currentAssignments, $this->availableSubjects);
            $this->dispatch('notify', type: 'success', message: 'Asignación creada correctamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Violación del unique constraint
            $this->dispatch('notify', type: 'error', message: 'Esta asignación ya existe para el año activo.');
        }
    }

    public function remove(int $assignmentId, TeacherAssignmentService $service): void
    {
        $this->authorize('teachers.assign_subjects');

        $assignment = TeacherSubjectSection::findOrFail($assignmentId);
        $service->remove($assignment);

        unset($this->currentAssignments);
        $this->dispatch('notify', type: 'info', message: 'Asignación eliminada.');
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.academic.teachers.teacher-assignments');

        return $view->layout('layouts.app-module', config('modules.academico'));
    }
}