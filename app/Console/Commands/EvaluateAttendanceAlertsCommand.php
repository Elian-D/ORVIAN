<?php

namespace App\Console\Commands;

use App\Models\Tenant\Student;
use App\Services\Communications\AttendanceAlertEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EvaluateAttendanceAlertsCommand extends Command
{
    protected $signature   = 'orvian:evaluate-attendance-alerts {--school= : ID del centro. Si se omite, evalúa todos.}';
    protected $description = 'Evalúa umbrales de asistencia y despacha notificaciones WhatsApp a tutores.';

    public function handle(AttendanceAlertEvaluator $evaluator): int
    {
        $query = Student::query()->active()->with('section');

        if ($schoolId = $this->option('school')) {
            $query->where('school_id', $schoolId);
        }

        $students = $query->get();

        $this->info("Evaluando {$students->count()} estudiante(s)...");
        $this->withProgressBar($students, fn (Student $student) => $evaluator->evaluate($student));
        $this->newLine();

        // --- PROCESAMIENTO DEL LOG CONSOLIDADO ---
        $skippedIds = $evaluator->getSkippedReport();
        
        if (!empty($skippedIds)) {
            $total = count($skippedIds);
            Log::warning("AttendanceAlerts: Se omitieron {$total} estudiantes por falta de tutor_phone.", [
                'count' => $total,
                'student_ids' => $skippedIds // Esto guarda todos los IDs en un solo array dentro del log
            ]);
            
            $this->warn("Aviso: {$total} estudiantes no tienen teléfono de tutor. Ver logs para detalles.");
        }

        $this->info('Evaluación completada.');

        return Command::SUCCESS;
    }
}