<?php

namespace App\Jobs\Communications;

use App\Models\Tenant\Student;
use App\Services\Communications\WhatsAppService;
use App\Services\Communications\WhatsAppTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAttendanceAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 3;
    public int $backoff = 60; // Segundos entre reintentos

    public function __construct(
        public readonly Student $student,
        public readonly string  $type,   // 'absence' | 'tardiness'
        public readonly int     $count,
        public readonly string  $month,
    ) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        if (empty($this->student->tutor_phone)) {
            // Ya no necesitamos un Warning aquí, porque el comando ya nos avisó.
            return;
        }

        $message = match ($this->type) {
            'absence'   => WhatsAppTemplates::absenceAlert($this->student, $this->count, $this->month),
            'tardiness' => WhatsAppTemplates::tardinessAlert($this->student, $this->count, $this->month),
            default     => throw new \InvalidArgumentException("Tipo de alerta desconocido: {$this->type}"),
        };

        $whatsApp->sendTextMessage($this->student->tutor_phone, $message);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendAttendanceAlertJob: Falló definitivamente tras todos los reintentos', [
            'student_id' => $this->student->id,
            'type'       => $this->type,
            'error'      => $exception->getMessage(),
        ]);
    }
}