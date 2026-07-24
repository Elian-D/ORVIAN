<?php

namespace App\Services\Attendance;

use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\Student;
use App\Models\Tenant\Academic\SchoolShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // <--- Necesario para transacciones
use App\Services\FacialRecognition\FaceEncodingManager; // <--- Usar el Manager
use Illuminate\Http\UploadedFile;

class PlantelAttendanceService
{
    public function __construct(
        protected ExcuseService $excuseService,
        protected FaceEncodingManager $faceManager,
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

        // Aviso informativo: si el estudiante tiene una excusa de varios días
        // que todavía no ha terminado y de todas formas se presenta, se anota
        // en el propio registro para que Dirección lo vea en el Hub — no cambia
        // el status, no toca la excusa, solo queda registrado el hecho.
        $metadata = $data['metadata'] ?? [];
        $pendingExcuse = $this->excuseService->getMultiDayExcuseStillPendingForStudent(
            $data['student_id'],
            Carbon::parse($data['date'])
        );

        if ($pendingExcuse) {
            $metadata['excuse_alert']  = true;
            $metadata['excuse_id']     = $pendingExcuse->id;
            $metadata['alert_message'] = 'Estudiante con excusa activa de varios días ha ingresado al plantel.';
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
            // Verificar si tiene excusa confirmada para este día
            $hasConfirmedExcuse = $this->excuseService->hasConfirmedExcuseForDate(
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
                'status'                      => $hasConfirmedExcuse
                                                    ? PlantelAttendanceRecord::STATUS_EXCUSED
                                                    : PlantelAttendanceRecord::STATUS_ABSENT,
                'method'                      => PlantelAttendanceRecord::METHOD_MANUAL,
                'registered_by'               => Auth::id(),
                'notes'                       => $hasConfirmedExcuse
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

            if (!$shift || !$shift->start_time) {
                return PlantelAttendanceRecord::STATUS_PRESENT;
            }

            $arrivalTime   = Carbon::parse($time);
            $shiftStart    = Carbon::parse($shift->start_time);
            $lateThreshold = $shiftStart->copy()->addMinutes($shift->late_threshold_minutes ?? 15);

            return $arrivalTime->lte($lateThreshold)
                ? PlantelAttendanceRecord::STATUS_PRESENT
                : PlantelAttendanceRecord::STATUS_LATE;
        }


    // ── Métodos nuevos (agregar al final del servicio) ────────────────

    /**
     * Identifica al estudiante por código QR y registra su asistencia.
     * Usado por el API Gateway del Kiosko (KioskQrRecordController).
     */
    public function recordByQr(int $schoolId, int $sessionId, string $qrCode): AttendanceResult
    {
        return DB::transaction(function () use ($schoolId, $sessionId, $qrCode) {
            $session = DailyAttendanceSession::where('id', $sessionId)
                ->where('school_id', $schoolId)
                ->active()
                ->first();

            if (! $session) return AttendanceResult::fail('SESSION_CLOSED', 'Sesión no abierta.');

            $student = Student::where('school_id', $schoolId)->where('qr_code', $qrCode)->active()->first();
            if (! $student) return AttendanceResult::fail('NOT_FOUND', 'Estudiante no encontrado.');

            $alreadyRecorded = PlantelAttendanceRecord::where('student_id', $student->id)
                ->where('daily_attendance_session_id', $session->id)->exists();

            if ($alreadyRecorded) return AttendanceResult::fail('ALREADY_RECORDED', 'Ya registrado hoy.');

            $now = now();
            $record = $this->recordAttendance([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'school_shift_id' => $session->school_shift_id,
                'date' => $session->date,
                'time' => $now->format('H:i:s'),
                'method' => PlantelAttendanceRecord::METHOD_QR,
            ]);

            return AttendanceResult::ok($student, $record->status, $now);
        });
    }

    public function recordByFacial(int $schoolId, int $sessionId, UploadedFile $photo): AttendanceResult
    {
        return DB::transaction(function () use ($schoolId, $sessionId, $photo) {
            $session = DailyAttendanceSession::where('id', $sessionId)
                ->where('school_id', $schoolId)
                ->active()
                ->first();

            if (! $session) return AttendanceResult::fail('SESSION_CLOSED', 'Sesión no abierta.');

            // Usamos el Manager que SÍ existe
            $match = $this->faceManager->identifyStudent($schoolId, $photo);

            if (!$match) {
                return AttendanceResult::fail('NO_MATCH', 'No se pudo identificar el rostro.');
            }

            $student = Student::where('id', $match['student_id'])
                ->where('school_id', $schoolId)
                ->active()
                ->first();

            if (! $student) return AttendanceResult::fail('NOT_FOUND', 'Estudiante no encontrado.');

            $alreadyRecorded = PlantelAttendanceRecord::where('student_id', $student->id)
                ->where('daily_attendance_session_id', $session->id)->exists();

            if ($alreadyRecorded) return AttendanceResult::fail('ALREADY_RECORDED', 'Ya registrado hoy.');

            $now = now();
            $record = $this->recordAttendance([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'school_shift_id' => $session->school_shift_id,
                'date' => $session->date,
                'time' => $now->format('H:i:s'),
                'method' => PlantelAttendanceRecord::METHOD_FACIAL,
                'metadata' => ['confidence' => $match['confidence'] ?? null],
            ]);

            return AttendanceResult::ok($student, $record->status, $now, $match['confidence'] ?? null);
        });
    }
}