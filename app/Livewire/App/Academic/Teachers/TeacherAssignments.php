<?php

namespace App\Livewire\App\Academic\Teachers;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use App\Models\Tenant\Academic\Subject;
use App\Models\Tenant\Academic\TeacherSubjectSection;
use App\Models\Tenant\Academic\AcademicYear;
use App\Models\Tenant\Teacher;
use App\Services\Academic\Teachers\TeacherAssignmentService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeacherAssignments extends Component
{
    public Teacher $teacher;
    public ?int    $activeSectionId = null;
    public ?int    $filterShiftId   = null;

    public string  $searchSection = '';
    public string  $searchSubject = '';

    public function mount(Teacher $teacher): void
    {
        $this->teacher = $teacher->load(['assignments.subject', 'assignments.section.grade']);

        // Pre-seleccionar la primera sección del maestro si tiene asignaciones
        $this->activeSectionId = $this->teacher->assignments->first()?->school_section_id;
    }

    #[Computed]
    public function shifts(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolShift::where('school_id', $this->teacher->school_id)->get();
    }

    #[Computed]
    public function sections(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolSection::with(['grade.level', 'shift', 'technicalTitle'])
            ->where('school_id', $this->teacher->school_id)
            ->where('is_active', true)
            ->when($this->filterShiftId, fn ($q) => $q->where('school_shift_id', $this->filterShiftId))
            ->when($this->searchSection, function($q) {
                $q->where(function($query) {
                    $query->where('label', 'like', "%{$this->searchSection}%")
                          ->orWhereHas('grade', fn($g) => $g->where('name', 'like', "%{$this->searchSection}%"))
                          ->orWhereHas('technicalTitle', fn($t) => $t->where('name', 'like', "%{$this->searchSection}%"));
                });
            })
            ->get()
            ->sortBy(fn ($s) => $s->grade->name . $s->label);
    }

    /**
     * Todas las materias disponibles para la escuela, filtradas por búsqueda y 
     * con indicador de si están asignadas al maestro en la sección activa.
     */
    #[Computed]
    public function subjectsForActiveSection(): array
    {
        if (! $this->activeSectionId) {
            return ['basic' => collect(), 'technical' => collect()];
        }

        $year = AcademicYear::where('school_id', $this->teacher->school_id)
            ->where('is_active', true)
            ->first();

        $assignedSubjectIds = TeacherSubjectSection::where('teacher_id', $this->teacher->id)
            ->where('school_section_id', $this->activeSectionId)
            ->where('academic_year_id', $year?->id)
            ->where('is_active', true)
            ->pluck('subject_id')
            ->toArray();

        $allSubjects = Subject::availableForSchool($this->teacher->school_id)
            ->active()
            ->when($this->searchSubject, function($q) {
                $q->where(function($query) {
                    $query->where('name', 'like', "%{$this->searchSubject}%")
                          ->orWhere('code', 'like', "%{$this->searchSubject}%");
                });
            })
            ->get()
            ->map(fn ($s) => [
                'id'         => $s->id,
                'name'       => $s->name,
                'code'       => $s->code,
                'color'      => $s->color,
                'type'       => $s->type,
                'is_assigned'=> in_array($s->id, $assignedSubjectIds),
            ]);

        return [
            'basic'     => $allSubjects->where('type', Subject::TYPE_BASIC)->values(),
            'technical' => $allSubjects->where('type', Subject::TYPE_TECHNICAL)->values(),
        ];
    }

    /**
     * Toggle de asignación: asigna si no está asignada, desasigna si ya está.
     * Un solo clic — sin confirmación (la UI muestra el estado claramente).
     */
    public function toggleSubject(int $subjectId): void
    {
        $this->authorize('teachers.assign_subjects');

        $year = AcademicYear::where('school_id', $this->teacher->school_id)
            ->where('is_active', true)
            ->firstOrFail();

        $existing = TeacherSubjectSection::where('teacher_id', $this->teacher->id)
            ->where('subject_id', $subjectId)
            ->where('school_section_id', $this->activeSectionId)
            ->where('academic_year_id', $year->id)
            ->first();

        if ($existing) {
            // Desasignar
            app(TeacherAssignmentService::class)->remove($existing);
            $this->dispatch('notify', type: 'info', message: 'Materia desasignada.');
        } else {
            // Asignar
            try {
                app(TeacherAssignmentService::class)->assign(
                    $this->teacher,
                    $subjectId,
                    $this->activeSectionId
                );
                $this->dispatch('notify', type: 'success', message: 'Materia asignada.');
            } catch (\Illuminate\Database\QueryException) {
                $this->dispatch('notify', type: 'error', message: 'Esta asignación ya existe.');
            }
        }

        // Invalidar computed para re-renderizar el grid
        unset($this->subjectsForActiveSection);
        $this->teacher->refresh();
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.academic.teachers.teacher-assignments');

        return $view->layout('layouts.app-module', config('modules.academico'));
    }
}