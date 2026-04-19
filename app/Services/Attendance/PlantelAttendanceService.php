<?php

namespace App\Services\Attendance;

use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\Student;
use App\Models\Tenant\Academic\SchoolShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PlantelAttendanceService
{
    public function __construct(
        protected ExcuseService $excuseService
    ) {}

    // ── Gestión de Sesión ─────────────────────────────────────────

    public function openDailySession(int $schoolId, int $shiftId, Carbon $date): DailyAttendanceSession
    {
        $existing = DailyAttendanceSession::where('school_id', $schoolId)
            ->where('date', $date)
            ->where('school_shift_id', $shiftId)
            ->first();

        if ($existing) {
            throw new \Exception('Ya existe una sesión abierta para esta fecha y tanda.');
        }

        // Aplicamos el filtro de la tanda usando el nuevo scope
        $totalExpected = Student::active()
            ->where('school_id', $schoolId)
            ->inShift($shiftId) // <-- FILTRO APLICADO
            ->count();

        return DailyAttendanceSession::create([
            'school_id'       => $schoolId,
            'school_shift_id' => $shiftId,
            'date'            => $date,
            'opened_at'       => now(),
            'opened_by'       => Auth::id(),
            'total_expected'  => $totalExpected,
        ]);
    }

    public function closeDailySession(DailyAttendanceSession $session): void
    {
        if (! $session->isOpen()) {
            throw new \Exception('Esta sesión ya está cerrada.');
        }

        $records = PlantelAttendanceRecord::where('daily_attendance_session_id', $session->id)->get();

        $session->update([
            'closed_at'        => now(),
            'closed_by'        => Auth::id(),
            'total_registered' => $records->count(),
            'total_present'    => $records->where('status', PlantelAttendanceRecord::STATUS_PRESENT)->count(),
            'total_late'       => $records->where('status', PlantelAttendanceRecord::STATUS_LATE)->count(),
            'total_absent'     => $records->where('status', PlantelAttendanceRecord::STATUS_ABSENT)->count(),
            'total_excused'    => $records->where('status', PlantelAttendanceRecord::STATUS_EXCUSED)->count(),
        ]);
    }

    // ── Registro Individual ───────────────────────────────────────

    /**
     * Registrar entrada de un estudiante al plantel.
     *
     * MODIFICACIÓN: Si el estudiante tiene una excusa tipo 'license' o médica
     * activa al momento de registrar entrada, se permite el registro como
     * STATUS_PRESENT pero se agrega una alerta en metadata para coordinación.
     */
    public function recordAttendance(array $data): PlantelAttendanceRecord
    {
        $session = DailyAttendanceSession::where('school_id', $data['school_id'])
            ->where('date', $data['date'])
            ->where('school_shift_id', $data['school_shift_id'])
            ->active()
            ->first();

        if (! $session) {
            throw new \Exception(
                'No hay sesión de asistencia abierta para esta fecha. Un administrador debe abrirla primero.'
            );
        }

        $existing = PlantelAttendanceRecord::where('student_id', $data['student_id'])
            ->where('date', $data['date'])
            ->where('school_shift_id', $data['school_shift_id'])
            ->first();

        if ($existing) {
            throw new \Exception('Este estudiante ya tiene registro de asistencia para hoy.');
        }

        $status = $data['status'] ?? $this->determineStatus(
            $data['time'],
            $data['school_shift_id']
        );

        // ── Alerta de licencia activa ─────────────────────────────
        // Si el estudiante tiene una licencia o excusa médica aprobada
        // para hoy pero se presentó físicamente, se registra la entrada
        // normalmente pero se agrega un flag en metadata para coordinación.
        $metadata = $data['metadata'] ?? [];
        $activeLicense = $this->excuseService->getActiveLicenseForStudent(
            $data['student_id'],
            Carbon::parse($data['date'])
        );

        if ($activeLicense) {
            $metadata['license_alert'] = true;
            $metadata['license_id']    = $activeLicense->id;
            $metadata['license_type']  = $activeLicense->type;
            $metadata['alert_message'] = 'Estudiante con licencia activa ha ingresado al plantel.';

            Log::info('Estudiante con licencia activa registró entrada', [
                'student_id'  => $data['student_id'],
                'excuse_id'   => $activeLicense->id,
                'excuse_type' => $activeLicense->type,
                'date'        => $data['date'],
            ]);
        }

        $record = PlantelAttendanceRecord::create([
            ...$data,
            'daily_attendance_session_id' => $session->id,
            'status'                      => $status,
            'metadata'                    => $metadata,
        ]);

        $session->incrementRegistered();

        return $record;
    }

    // ── Marcado Masivo de Ausencias ───────────────────────────────

    /**
     * Marcar como ausentes a todos los estudiantes sin registro al final del día.
     *
     * MODIFICACIÓN: Antes de crear el registro de ausencia, se verifica si el
     * estudiante tiene una excusa aprobada para la fecha de la sesión.
     * Si existe → STATUS_EXCUSED con nota automática.
     * Si no existe → STATUS_ABSENT normal.
     */
    public function markAbsences(DailyAttendanceSession $session): int
    {
        $studentsWithRecord = PlantelAttendanceRecord::where('daily_attendance_session_id', $session->id)
            ->pluck('student_id');

        // Filtramos para que solo traiga los ausentes de ESTA tanda
        $absentStudents = Student::active()
            ->where('school_id', $session->school_id)
            ->inShift($session->school_shift_id) // <-- FILTRO APLICADO
            ->whereNotIn('id', $studentsWithRecord)
            ->get();

        $sessionDate = Carbon::parse($session->date);
        $marked = 0;

        foreach ($absentStudents as $student) {
            // Verificar si tiene excusa aprobada para este día
            $hasApprovedExcuse = $this->excuseService->hasApprovedExcuseForDate(
                $student->id,
                $sessionDate
            );

            PlantelAttendanceRecord::create([
                'school_id'                   => $session->school_id,
                'student_id'                  => $student->id,
                'daily_attendance_session_id' => $session->id,
                'school_shift_id'             => $session->school_shift_id,
                'date'                        => $session->date,
                'time'                        => now()->format('H:i:s'),
                'status'                      => $hasApprovedExcuse
                                                    ? PlantelAttendanceRecord::STATUS_EXCUSED
                                                    : PlantelAttendanceRecord::STATUS_ABSENT,
                'method'                      => PlantelAttendanceRecord::METHOD_MANUAL,
                'registered_by'               => Auth::id(),
                'notes'                       => $hasApprovedExcuse
                                                    ? 'Excusa aplicada automáticamente.'
                                                    : null,
            ]);

            $marked++;
        }

        return $marked;
    }

    // ── Helpers ───────────────────────────────────────────────────

    protected function determineStatus(string $time, int $shiftId): string
    {
        $shift = SchoolShift::find($shiftId);

        if (! $shift || ! $shift->start_time) {
            return PlantelAttendanceRecord::STATUS_PRESENT;
        }

        $arrivalTime   = Carbon::parse($time);
        $shiftStart    = Carbon::parse($shift->start_time);
        $lateThreshold = $shiftStart->copy()->addMinutes(15);

        return $arrivalTime->lte($lateThreshold)
            ? PlantelAttendanceRecord::STATUS_PRESENT
            : PlantelAttendanceRecord::STATUS_LATE;
    }

    public function isStudentPresentInPlantel(int $studentId, Carbon $date, int $shiftId): bool
    {
        $record = PlantelAttendanceRecord::where('student_id', $studentId)
            ->where('date', $date)
            ->where('school_shift_id', $shiftId)
            ->first();

        return $record && $record->isPresent();
    }
}