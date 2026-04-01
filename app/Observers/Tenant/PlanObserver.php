<?php

namespace App\Observers\Tenant;

use App\Models\Tenant\Plan;
use Illuminate\Support\Facades\Log;

class PlanObserver
{
    /**
     * CAMBIO: Movemos a "saved" (después de guardar) solo para logging
     * La lógica de "solo un featured" ahora está en el componente Livewire
     */
    public function saved(Plan $plan): void
    {
        if ($plan->is_featured) {
            Log::info('Plan marcado como destacado', [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
            ]);
        }
    }

    /**
     * Opcional: Log cuando se elimina un plan
     */
    public function deleted(Plan $plan): void
    {
        Log::info('Plan eliminado', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'was_featured' => $plan->is_featured,
        ]);
    }
}