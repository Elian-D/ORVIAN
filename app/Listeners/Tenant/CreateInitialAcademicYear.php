<?php

namespace App\Listeners\Tenant;

use App\Events\Tenant\SchoolConfigured;
use App\Models\Tenant\Academic\AcademicYear;

class CreateInitialAcademicYear
{
    public function handle(SchoolConfigured $event): void
    {
        AcademicYear::create([
            'school_id'  => $event->school->id,
            'name'       => $event->academicData['year_name'], // Ej: "2025-2026"
            'start_date' => $event->academicData['start_date'],
            'end_date'   => $event->academicData['end_date'],
            'is_active'  => true,
        ]);
    }
}