<?php

namespace App\Services\Communications;

use App\Jobs\Communications\SendAttendanceAlertJob;
use App\Models\Tenant\Student;
use App\Models\Tenant\PlantelAttendanceRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AttendanceAlertEvaluator
{
    protected int $absenceThreshold;
    protected int $tardinessThreshold;
    // Propiedad para acumular los omitidos
    protected array $skippedStudents = [];

    public function __construct()
    {
        $this->absenceThreshold   = config('communications.notifications.absence_threshold', 3);
        $this->tardinessThreshold = config('communications.notifications.tardiness_threshold', 3);
    }

    /**
     * Evalúa un estudiante y despacha alertas si supera los umbrales.
     * Protegido por caché anti-spam: máximo una alerta del mismo tipo por semana.
     */
    public function evaluate(Student $student): void
    {
        if (empty($student->tutor_phone)) { 
            // En lugar de Log::debug aquí, acumulamos el ID
            $this->skippedStudents[] = $student->id;
            return;
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();
        $monthName    = Carbon::now()->translatedFormat('F Y');
        $weekKey      = Carbon::now()->weekOfYear;

        $records = PlantelAttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $absences  = $records->where('status', 'absent')->count();
        $tardiness = $records->where('status', 'late')->count();

        // ── Alerta de ausencias ──
        if ($absences >= $this->absenceThreshold) {
            $cacheKey = "alert_absence_{$student->id}_{$weekKey}";
            if (! Cache::has($cacheKey)) {
                SendAttendanceAlertJob::dispatch($student, 'absence', $absences, $monthName);
                Cache::put($cacheKey, true, now()->addDays(7));
                Log::info('AttendanceAlertEvaluator: Job de ausencia despachado', [
                    'student_id' => $student->id,
                    'count'      => $absences,
                ]);
            }
        }

        // ── Alerta de tardanzas ──
        if ($tardiness >= $this->tardinessThreshold) {
            $cacheKey = "alert_tardiness_{$student->id}_{$weekKey}";
            if (! Cache::has($cacheKey)) {
                SendAttendanceAlertJob::dispatch($student, 'tardiness', $tardiness, $monthName);
                Cache::put($cacheKey, true, now()->addDays(7));
                Log::info('AttendanceAlertEvaluator: Job de tardanza despachado', [
                    'student_id' => $student->id,
                    'count'      => $tardiness,
                ]);
            }
        }
    }

    /**
     * Retorna la lista de omitidos y limpia el array para la próxima ejecución.
     */
    public function getSkippedReport(): array
    {
        $report = $this->skippedStudents;
        $this->skippedStudents = []; // Limpiamos para no duplicar si se llama varias veces
        return $report;
    }
}