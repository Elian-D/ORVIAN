<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\Academic\AcademicYear;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\TeacherSubjectSection;
use App\Models\Tenant\ClassroomAttendanceRecord;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\Teacher;
use App\Services\Attendance\ClassroomAttendanceService;
use App\Services\Attendance\ExcuseService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClassroomAttendanceLive extends Component
{
    // ── Selección de clase ────────────────────────────────────────
    public ?int $selectedAssignmentId = null;
    public bool $isSubstituteMode     = false;
    public string $substituteSearch   = '';
    public ?int $substituteSectionId  = null;

    // ── Pase de lista ─────────────────────────────────────────────
    public array $studentStatuses  = [];
    public array $plantelStatuses  = [];  // [student_id => plantel status|null]
    public array $excusedStudentIds = [];

    // ── UI ────────────────────────────────────────────────────────
    public bool  $studentsLoaded = false;
    public bool  $submitted      = false;
    public array $submitResult   = [];

    private ?Teacher $teacher = null;

    protected function resolveTeacher(): ?Teacher
    {
        return $this->teacher ??= Teacher::where('user_id', Auth::id())->first();
    }

    // ── Modo sustituto ────────────────────────────────────────────

    public function toggleSubstituteMode(): void
    {
        $this->isSubstituteMode = ! $this->isSubstituteMode;
        $this->reset([
            'selectedAssignmentId', 'substituteSectionId', 'substituteSearch',
            'studentStatuses', 'plantelStatuses', 'excusedStudentIds',
            'studentsLoaded', 'submitted', 'submitResult',
        ]);
    }

    public function selectSubstituteSection(int $sectionId): void
    {
        $this->substituteSectionId  = $sectionId;
        $this->selectedAssignmentId = null;
        $this->reset(['studentStatuses', 'plantelStatuses', 'excusedStudentIds', 'studentsLoaded', 'submitted', 'submitResult']);
    }

    // ── Selección de asignación ───────────────────────────────────

    public function selectAssignment(int $assignmentId): void
    {
        $this->selectedAssignmentId = $assignmentId;
        $this->reset(['studentStatuses', 'plantelStatuses', 'excusedStudentIds', 'studentsLoaded', 'submitted', 'submitResult']);
    }

    public function clearAssignment(): void
    {
        $this->reset(['selectedAssignmentId', 'studentStatuses', 'plantelStatuses', 'excusedStudentIds', 'studentsLoaded', 'submitted', 'submitResult']);
    }

    // ── Carga de estudiantes ──────────────────────────────────────

    /**
     * Carga la lista de estudiantes y pre-llena el estado heredando el registro
     * del plantel (presente→presente, tarde→presente, ausente→ausente, excusado→excusado).
     * Los estudiantes ausentes/excusados en plantel quedan bloqueados en la UI.
     */
    public function loadStudents(ExcuseService $excuseService): void
    {
        if (! $this->selectedAssignmentId) return;

        $date = today();

        $this->excusedStudentIds = $excuseService
            ->getCoveredStudentsForDate($date)
            ->toArray();

        $assignment = TeacherSubjectSection::with([
            'section.students' => fn($q) => $q->active()
                ->select('id', 'first_name', 'last_name', 'photo_path', 'school_section_id', 'is_active'),
        ])->find($this->selectedAssignmentId);

        $students   = $assignment?->section?->students ?? collect();
        $studentIds = $students->pluck('id')->toArray();

        // 1. Historial: registros de aula ya guardados para esta materia hoy
        $classroomRecords = ClassroomAttendanceRecord::whereIn('student_id', $studentIds)
            ->where('teacher_subject_section_id', $this->selectedAssignmentId)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_id');

        // 2. Sincronización: registros del plantel del día
        $plantelRecords = PlantelAttendanceRecord::whereIn('student_id', $studentIds)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_id');

        $this->plantelStatuses = [];
        $this->studentStatuses = [];

        foreach ($students as $student) {
            $plantelRecord   = $plantelRecords->get($student->id);
            $classroomRecord = $classroomRecords->get($student->id);

            $this->plantelStatuses[$student->id] = $plantelRecord?->status;

            if ($classroomRecord) {
                // Prioridad 1: ya existe registro de aula para hoy → cargar ese estado
                $this->studentStatuses[$student->id] = $classroomRecord->status;
            } elseif ($plantelRecord) {
                // Prioridad 2: heredar del plantel
                $this->studentStatuses[$student->id] = match($plantelRecord->status) {
                    PlantelAttendanceRecord::STATUS_PRESENT,
                    PlantelAttendanceRecord::STATUS_LATE    => ClassroomAttendanceRecord::STATUS_PRESENT,
                    PlantelAttendanceRecord::STATUS_ABSENT  => ClassroomAttendanceRecord::STATUS_ABSENT,
                    PlantelAttendanceRecord::STATUS_EXCUSED => ClassroomAttendanceRecord::STATUS_EXCUSED,
                    default                                 => ClassroomAttendanceRecord::STATUS_PRESENT,
                };
            } elseif (in_array($student->id, $this->excusedStudentIds)) {
                // Prioridad 3: excusa aprobada sin registro de plantel
                $this->studentStatuses[$student->id] = ClassroomAttendanceRecord::STATUS_EXCUSED;
            } else {
                // Prioridad 4: sin datos → presente por defecto
                $this->studentStatuses[$student->id] = ClassroomAttendanceRecord::STATUS_PRESENT;
            }
        }

        $this->studentsLoaded = true;
    }

    // ── Cambio de estado ──────────────────────────────────────────

    public function setStatus(int $studentId, string $status): void
    {
        if ($this->isLockedByPlantel($studentId)) return;
        $this->studentStatuses[$studentId] = $status;
    }

    public function isLockedByPlantel(int $studentId): bool
    {
        return in_array($this->plantelStatuses[$studentId] ?? null, [
            PlantelAttendanceRecord::STATUS_ABSENT,
            PlantelAttendanceRecord::STATUS_EXCUSED,
        ]);
    }

    // ── Guardado ──────────────────────────────────────────────────

    public function saveAttendance(ClassroomAttendanceService $service): void
    {
        $this->authorize('attendance_classroom.record');

        if (! $this->selectedAssignmentId) {
            $this->dispatch('notify',
                type: 'error',
                title: 'Sin clase seleccionada',
                message: 'Selecciona una clase antes de guardar el pase de lista.',
            );
            return;
        }

        $result = $service->takeClassAttendance(
            $this->selectedAssignmentId,
            today(),
            $this->studentStatuses
        );

        $this->submitted    = true;
        $this->submitResult = $result;

        $recorded = $result['recorded'];
        $skipped  = $result['skipped'];

        if ($skipped === 0) {
            $this->dispatch('notify',
                type: 'success',
                title: '¡Pase de Lista Guardado!',
                message: "Se registraron {$recorded} " . ($recorded === 1 ? 'estudiante' : 'estudiantes') . ' correctamente.',
                duration: 7000,
            );
        } else {
            $this->dispatch('notify',
                type: 'warning',
                title: 'Guardado con advertencias',
                message: "{$recorded} registrados correctamente · {$skipped} omitidos por validación cruzada del plantel.",
                duration: 9000,
            );
        }
    }

    // ── Render ────────────────────────────────────────────────────

    public function render()
    {
        $teacher     = $this->resolveTeacher();
        $currentYear = AcademicYear::where('is_active', true)->first();

        // Mis asignaciones del año activo
        $myAssignments = collect();
        if ($teacher && $currentYear) {
            $myAssignments = TeacherSubjectSection::with([
                'subject:id,name,code,color',
                'section' => fn($q) => $q
                    ->select(['id', 'label', 'grade_id', 'school_shift_id', 'technical_title_id'])
                    ->withFullRelations(),
            ])
                ->where('teacher_id', $teacher->id)
                ->where('academic_year_id', $currentYear->id)
                ->where('is_active', true)
                ->get();
        }

        // Búsqueda de secciones en modo sustituto
        $substituteSections = collect();
        if ($this->isSubstituteMode && strlen($this->substituteSearch) >= 2 && $currentYear) {
            $validSectionIds = TeacherSubjectSection::where('academic_year_id', $currentYear->id)
                ->where('is_active', true)
                ->pluck('school_section_id')
                ->unique();

            $substituteSections = SchoolSection::withFullRelations()
                ->whereIn('id', $validSectionIds)
                ->where(fn($q) => $q
                    ->where('label', 'like', "%{$this->substituteSearch}%")
                    ->orWhereHas('grade', fn($q2) => $q2->where('name', 'like', "%{$this->substituteSearch}%"))
                )
                ->limit(8)
                ->get();
        }

        // Asignaciones de la sección seleccionada (sustituto)
        $sectionAssignments = collect();
        if ($this->isSubstituteMode && $this->substituteSectionId && $currentYear) {
            $sectionAssignments = TeacherSubjectSection::with([
                'subject:id,name,code,color',
                'teacher:id,first_name,last_name',
            ])
                ->where('school_section_id', $this->substituteSectionId)
                ->where('academic_year_id', $currentYear->id)
                ->where('is_active', true)
                ->get();
        }

        // Datos del pase de lista
        $students           = collect();
        $selectedAssignment = null;
        if ($this->selectedAssignmentId) {
            $selectedAssignment = TeacherSubjectSection::with([
                'subject:id,name,code,color',
                'section' => fn($q) => $q
                    ->select(['id', 'label', 'grade_id', 'school_shift_id', 'technical_title_id'])
                    ->withFullRelations(),
                'section.students' => fn($q) => $q->active()
                    ->select('id', 'first_name', 'last_name', 'photo_path', 'school_section_id', 'is_active'),
            ])->find($this->selectedAssignmentId);

            $students = $selectedAssignment?->section?->students ?? collect();
        }

        // Pasilleo: presente en PLANTEL (present/late) pero AUSENTE en AULA
        $pasilleoCount = 0;
        foreach ($this->studentStatuses as $studentId => $status) {
            if (
                $status === ClassroomAttendanceRecord::STATUS_ABSENT
                && in_array($this->plantelStatuses[$studentId] ?? null, [
                    PlantelAttendanceRecord::STATUS_PRESENT,
                    PlantelAttendanceRecord::STATUS_LATE,
                ])
            ) {
                $pasilleoCount++;
            }
        }

        // Verificar si hay registros de plantel hoy (indicador de sesión activa/cerrada)
        $hasPlantelRecordsToday = PlantelAttendanceRecord::whereDate('date', today())->exists();

        // Pre-computar IDs bloqueados para la vista
        $lockedStudentIds = array_keys(array_filter(
            $this->plantelStatuses,
            fn($s) => in_array($s, [
                PlantelAttendanceRecord::STATUS_ABSENT,
                PlantelAttendanceRecord::STATUS_EXCUSED,
            ])
        ));

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.classroom-attendance-live', [
            'myAssignments'            => $myAssignments,
            'substituteSections'       => $substituteSections,
            'sectionAssignments'       => $sectionAssignments,
            'students'                 => $students,
            'selectedAssignment'       => $selectedAssignment,
            'pasilleoCount'            => $pasilleoCount,
            'lockedStudentIds'         => $lockedStudentIds,
            'hasPlantelRecordsToday'   => $hasPlantelRecordsToday,
        ]);

        return $view->layout('layouts.app-module', config('modules.asistencia'));
    }
}
