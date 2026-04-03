<?php

namespace App\Services\Attendance;

use App\Models\Tenant\ClassroomAttendanceRecord;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\Student;
use App\Models\Tenant\Academic\SchoolShift;
use App\Models\Tenant\Academic\TeacherSubjectSection;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ClassroomAttendanceService
{
    public function __construct(
        protected PlantelAttendanceService $plantelService,
        protected ExcuseService $excuseService
    ) {}

    // ── Registro Individual ───────────────────────────────────────

    /**
     * Registrar asistencia de un estudiante en una clase.
     * Aplica validación cruzada estricta antes de guardar.
     */
    public function recordClassAttendance(array $data): ClassroomAttendanceRecord
    {
        $this->validateCrossAttendance(
            $data['student_id'],
            Carbon::parse($data['date']),
            $data['school_id']
        );

        $existing = ClassroomAttendanceRecord::where('student_id', $data['student_id'])
            ->where('teacher_subject_section_id', $data['teacher_subject_section_id'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing) {
            throw new \Exception('Ya existe registro de asistencia para este estudiante en esta clase.');
        }

        return ClassroomAttendanceRecord::create($data);
    }

    // ── Pase de Lista Completo ────────────────────────────────────

    /**
     * Procesar el pase de lista completo de una clase.
     *
     * INTEGRACIÓN CON EXCUSESERVICE: Si el status enviado para un estudiante
     * es 'present' pero el ExcuseService indica que está excusado para la fecha,
     * el status se fuerza a STATUS_EXCUSED para mantener consistencia.
     *
     * @param  array  $studentStatuses  [ student_id => status, ... ]
     */
    public function takeClassAttendance(
        int $assignmentId,
        Carbon $date,
        array $studentStatuses
    ): array {
        $assignment = TeacherSubjectSection::findOrFail($assignmentId);

        // Obtener estudiantes con excusa aprobada para este día (una sola consulta)
        $excusedStudentIds = $this->excuseService->getCoveredStudentsForDate($date);

        $recorded = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($studentStatuses as $studentId => $status) {
            try {
                // Si el maestro envía 'present' pero el estudiante tiene excusa,
                // se fuerza a 'excused' para mantener la lógica del sistema.
                if (
                    $status === ClassroomAttendanceRecord::STATUS_PRESENT
                    && $excusedStudentIds->contains($studentId)
                ) {
                    $status = ClassroomAttendanceRecord::STATUS_EXCUSED;
                }

                $this->recordClassAttendance([
                    'school_id'                   => $assignment->section->school_id,
                    'student_id'                  => $studentId,
                    'teacher_subject_section_id'  => $assignmentId,
                    'teacher_id'                  => $assignment->teacher_id,
                    'date'                        => $date,
                    'class_time'                  => now()->format('H:i:s'),
                    'status'                      => $status,
                ]);

                $recorded++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[$studentId] = $e->getMessage();

                Log::warning('Error al registrar asistencia de aula', [
                    'student_id'    => $studentId,
                    'assignment_id' => $assignmentId,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        return [
            'recorded' => $recorded,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }

    // ── Actualización de Registro Existente ───────────────────────

    public function updateRecord(ClassroomAttendanceRecord $record, string $status, ?string $notes = null): void
    {
        $record->update([
            'status'        => $status,
            'teacher_notes' => $notes ?? $record->teacher_notes,
        ]);
    }

    // ── Detección de Pasilleo ─────────────────────────────────────

    /**
     * Detectar estudiantes presentes en plantel pero ausentes en aula (pasilleo).
     */
    public function detectDiscrepancies(Carbon $date, int $schoolId): Collection
    {
        $discrepancies = collect();

        $presentInPlantel = PlantelAttendanceRecord::where('school_id', $schoolId)
            ->whereDate('date', $date)
            ->whereIn('status', [
                PlantelAttendanceRecord::STATUS_PRESENT,
                PlantelAttendanceRecord::STATUS_LATE,
            ])
            ->with('student')
            ->get();

        foreach ($presentInPlantel as $plantelRecord) {
            $classesAbsent = ClassroomAttendanceRecord::where('student_id', $plantelRecord->student_id)
                ->whereDate('date', $date)
                ->where('status', ClassroomAttendanceRecord::STATUS_ABSENT)
                ->count();

            if ($classesAbsent > 0) {
                $discrepancies->push([
                    'student'        => $plantelRecord->student,
                    'plantel_status' => $plantelRecord->status_label,
                    'classes_absent' => $classesAbsent,
                    'alert_type'     => 'pasilleo',
                ]);
            }
        }

        return $discrepancies;
    }

    // ── Validación Cruzada ────────────────────────────────────────

    /**
     * Regla de negocio estricta: no se puede registrar presencia en aula
     * si el estudiante está marcado como ausente en plantel.
     */
    protected function validateCrossAttendance(int $studentId, Carbon $date, int $schoolId): void
    {
        $student = Student::with('section.shift')->findOrFail($studentId);
        $shiftId = $student->section?->shift?->id ?? SchoolShift::where('school_id', $schoolId)->first()?->id;

        if (! $shiftId) {
            return; // Sin tanda configurada, se permite el registro
        }

        $plantelRecord = PlantelAttendanceRecord::where('student_id', $studentId)
            ->whereDate('date', $date)
            ->where('school_shift_id', $shiftId)
            ->first();

        if (! $plantelRecord) {
            // Si no tiene registro de plantel pero tiene excusa aprobada para hoy,
            // se permite el registro de aula (el sistema acepta que no pasó por la puerta
            // pero el maestro quiere dejar constancia de la excusa).
            $hasExcuse = $this->excuseService->hasApprovedExcuseForDate($studentId, $date);
            if (! $hasExcuse) {
                throw new \Exception(
                    'El estudiante no ha registrado entrada al plantel hoy. '.
                    'Debe pasar por la portería primero o tener una excusa aprobada.'
                );
            }
            return;
        }

        if (in_array($plantelRecord->status, [
            PlantelAttendanceRecord::STATUS_ABSENT,
            PlantelAttendanceRecord::STATUS_EXCUSED,
        ])) {
            throw new \Exception(
                "El estudiante está marcado como '{$plantelRecord->status_label}' en el plantel. ".
                'No puede registrarse como presente en aula.'
            );
        }
    }
}