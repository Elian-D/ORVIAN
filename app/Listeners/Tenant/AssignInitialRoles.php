<?php

namespace App\Listeners\Tenant;

use App\Events\Tenant\SchoolConfigured;
use App\Models\User;

class AssignInitialRoles
{
    public function handle(SchoolConfigured $event): void
    {
        $school = $event->school;
        
        // Buscamos al usuario que creamos como principal para esta escuela
        $principal = User::where('school_id', $school->id)->first();

        if ($principal) {
            setPermissionsTeamId($school->id);
            if (!$principal->hasRole('School Principal')) {
                $principal->assignRole('School Principal');
            }
        }
    }
}