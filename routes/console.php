<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // Importante añadir el import

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Limpieza de Stubs: corre diariamente a medianoche
Schedule::command('orvian:cleanup-stubs')->daily();

// Actualizar estados de usuario cada 5 minutos
Schedule::command('orvian:update-user-status')->everyFiveMinutes();

Schedule::command('orvian:evaluate-attendance-alerts')
    ->dailyAt('16:00')
    ->withoutOverlapping()
    ->description('Evalúa alertas de asistencia y notifica tutores por WhatsApp');