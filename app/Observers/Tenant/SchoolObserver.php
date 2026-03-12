<?php

namespace App\Observers\Tenant;

use App\Models\Tenant\School;
use App\Models\Tenant\Plan;

class SchoolObserver
{
    /**
     * Handle the School "creating" event.
     */
    public function creating(School $school): void
    {
        // Si al crear la escuela no se ha especificado un plan (ej. desde el registro inicial)
        // le asignamos el plan básico por defecto usando el slug.
        if (empty($school->plan_id)) {
            $defaultPlan = Plan::where('slug', Plan::BASIC)->first();
            
            if ($defaultPlan) {
                $school->plan_id = $defaultPlan->id;
            }
        }
    }
}