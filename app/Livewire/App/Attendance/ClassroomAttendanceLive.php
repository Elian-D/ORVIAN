<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\Academic\TeacherSubjectSection;
use App\Models\Tenant\Student;
use App\Services\Attendance\ClassroomAttendanceService;
use App\Services\Attendance\ExcuseService;
use Carbon\Carbon;
use Livewire\Component;

class ClassroomAttendanceLive extends Component
{
    public int $assignmentId;
    public string $attendanceDate;

    // Pase de lista: [student_id => status]
    public array $statuses = [];

    // IDs de estudiantes con excusa aprobada para hoy
    // (determinados al montar el componente)
    public array $excusedStudentIds = [];

    // Control de UI
    public bool $submitted = false;
    public array $submitResult = [];

    public function mount(int $assignmentId): void
    {
        $this->assignmentId    = $assignmentId;
        $this->attendanceDate  = today()->toDateString();
    }

    /**
     * Al cargar la lista de estudiantes se consulta ExcuseService para
     * identificar quiénes tienen excusa activa.
     * En la vista, esos estudiantes tendrán:
     *   - Status pre-seleccionado como STATUS_EXCUSED
     *   - Botón "Presente" deshabilitado (disabled en Alpine/Blade)
     */
    public function loadStudents(ExcuseService $excuseService): void
    {
        $date = Carbon::parse($this->attendanceDate);

        // Una sola consulta para todos los excusados de la fecha
        $this->excusedStudentIds = $excuseService
            ->getCoveredStudentsForDate($date)
            ->toArray();

        $assignment = TeacherSubjectSection::with('section.students.active')->find($this->assignmentId);

        if (! $assignment) return;

        $this->statuses = [];

        foreach ($assignment->section->students as $student) {
            // Pre-seleccionar excusados: botón Presente deshabilitado en la vista
            $this->statuses[$student->id] = in_array($student->id, $this->excusedStudentIds)
                ? \App\Models\Tenant\ClassroomAttendanceRecord::STATUS_EXCUSED
                : \App\Models\Tenant\ClassroomAttendanceRecord::STATUS_PRESENT;
        }
    }

    public function setStatus(int $studentId, string $status): void
    {
        // Prevenir cambio si el estudiante está excusado
        if (in_array($studentId, $this->excusedStudentIds)) {
            return;
        }

        $this->statuses[$studentId] = $status;
    }

    public function submitAttendance(ClassroomAttendanceService $service): void
    {
        $this->authorize('attendance_classroom.record');

        $result = $service->takeClassAttendance(
            $this->assignmentId,
            Carbon::parse($this->attendanceDate),
            $this->statuses
        );

        $this->submitted   = true;
        $this->submitResult = $result;

        $this->dispatch('toast',
            type: $result['skipped'] === 0 ? 'success' : 'warning',
            message: "Pase de lista guardado. {$result['recorded']} registrados, {$result['skipped']} omitidos."
        );
    }

    public function render()
    {
        $assignment = TeacherSubjectSection::with([
            'subject:id,name,code,color',
            'section.students' => fn ($q) => $q->active()->select('id', 'first_name', 'last_name', 'photo_path'),
        ])->find($this->assignmentId);

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.classroom-attendance-live', [
            'assignment' => $assignment,
            'statusOptions' => \App\Models\Tenant\ClassroomAttendanceRecord::STATUS_LABELS,
        ]);

        return $view->layout('layouts.app-module', config('modules.asistencia'));
    }
}