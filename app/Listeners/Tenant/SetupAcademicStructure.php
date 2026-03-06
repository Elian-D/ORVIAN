<?php

namespace App\Listeners\Tenant;

use App\Events\Tenant\TenantCreated;
use Illuminate\Support\Facades\Log;

class SetupAcademicStructure
{
    public function handle(TenantCreated $event): void
    {
        $school = $event->school;

        // Aquí irá la lógica de dominio en las próximas fases.
        // Ejemplo de lo que haremos:
        // 1. Crear el primer "Año Escolar" (ej: 2025-2026)
        // 2. Si es modalidad Académica, crear grados de 1ro a 6to.
        
        Log::info("Estructura académica inicial creada para: {$school->name}");
    }
}