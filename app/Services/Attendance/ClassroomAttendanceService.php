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
            $data['school_id'],
            $data['status']
        );

        $existing = ClassroomAttendanceRecord::where('student_id', $data['student_id'])
            ->where('teacher_subject_section_id', $data['teacher_subject_section_id'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing) {
            $existing->update([
                'status'              => $data['status'],
                'class_time'          => $data['class_time'],
                'teacher_notes'       => $data['teacher_notes'] ?? $existing->teacher_notes,
                // Una corrección hecha por otro usuario actualiza quién queda acreditado.
                'recorded_by_user_id' => $data['recorded_by_user_id'],
            ]);
            return $existing->fresh();
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
     * @param  int    $recordedByUserId  User::id de quien presiona "Guardar" — el
     *                                   titular de la clase o un sustituto (otro
     *                                   maestro, director o coordinador).
     */
    public function takeClassAttendance(
        int $assignmentId,
        Carbon $date,
        array $studentStatuses,
        int $recordedByUserId
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
                    'recorded_by_user_id'         => $recordedByUserId,
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
    protected function validateCrossAttendance(int $studentId, Carbon $date, int $schoolId, string $incomingStatus): void
    {
        $student = Student::with('section.shift')->findOrFail($studentId);
        $shiftId = $student->section?->shift?->id ?? SchoolShift::where('school_id', $schoolId)->first()?->id;

        if (! $shiftId) return;

        $plantelRecord = PlantelAttendanceRecord::where('student_id', $studentId)
            ->whereDate('date', $date)
            ->where('school_shift_id', $shiftId)
            ->first();

        if (! $plantelRecord) {
            $hasExcuse = $this->excuseService->hasConfirmedExcuseForDate($studentId, $date);
            if (! $hasExcuse) {
                throw new \Exception('El estudiante no ha registrado entrada al plantel hoy.');
            }
            return;
        }

        $lockedInPlantel = in_array($plantelRecord->status, [
            PlantelAttendanceRecord::STATUS_ABSENT,
            PlantelAttendanceRecord::STATUS_EXCUSED,
        ]);

        // Solo es una combinación imposible si se intenta guardar PRESENTE/TARDE
        // mientras el plantel dice ausente/excusado. Guardar ABSENT o EXCUSED
        // (coherente con el plantel) siempre debe permitirse.
        if ($lockedInPlantel && in_array($incomingStatus, [
            ClassroomAttendanceRecord::STATUS_PRESENT,
            ClassroomAttendanceRecord::STATUS_LATE,
        ])) {
            throw new \Exception(
                "El estudiante está marcado como '{$plantelRecord->status_label}' en el plantel. No puede registrarse como presente en aula."
            );
        }
    }
}