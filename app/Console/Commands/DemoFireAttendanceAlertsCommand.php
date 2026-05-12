<?php

namespace App\Console\Commands;

use App\Models\Tenant\Student;
use App\Services\Communications\AttendanceAlertEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DemoFireAttendanceAlertsCommand extends Command
{
    // Usamos un argumento en lugar de una opción para que sea más rápido de teclear en vivo.
    // Ejemplo: php artisan orvian:demo-fire-alerts 1
    protected $signature   = 'orvian:demo-fire-alerts {school : ID del centro educativo a evaluar}';
    protected $description = 'DEMO: Limpia la caché anti-spam y fuerza el envío de alertas de asistencia para una escuela específica.';

    public function handle(AttendanceAlertEvaluator $evaluator): int
    {
        $schoolId = $this->argument('school');
        
        $students = Student::query()
            ->active()
            ->with('section')
            ->where('school_id', $schoolId)
            ->get();

        if ($students->isEmpty()) {
            $this->error("⚠️ No se encontraron estudiantes activos para la escuela ID: {$schoolId}");
            return Command::FAILURE;
        }

        $this->info("🚀 Iniciando DEMO para la escuela ID {$schoolId}. Estudiantes a evaluar: {$students->count()}");
        
        // Obtenemos la semana actual, que es la misma variable que usa tu Evaluator
        $weekKey = Carbon::now()->weekOfYear;

        $this->withProgressBar($students, function (Student $student) use ($evaluator, $weekKey) {
            
            // 1. Reconstruimos las llaves de caché exactas que usa tu Evaluator
            $cacheKeyAbsence   = "alert_absence_{$student->id}_{$weekKey}";
            $cacheKeyTardiness = "alert_tardiness_{$student->id}_{$weekKey}";
            
            // 2. Destruimos la caché para este estudiante específico (forzando el reinicio de los 7 días)
            Cache::forget($cacheKeyAbsence);
            Cache::forget($cacheKeyTardiness);

            // 3. Evaluamos. Como ya no hay caché, el evaluador despachará el Job sí o sí si supera el umbral.
            $evaluator->evaluate($student);
        });

        $this->newLine();

        // --- REPORTE DE OMITIDOS (Reutilizando tu lógica) ---
        $skippedIds = $evaluator->getSkippedReport();
        
        if (!empty($skippedIds)) {
            $total = count($skippedIds);
            $this->warn("Aviso: {$total} estudiantes fueron omitidos porque no tienen teléfono de tutor.");
        }

        $this->info('✅ ¡Ejecución de Demo completada! Los mensajes deberían estar encolados o enviándose.');

        return Command::SUCCESS;
    }
}