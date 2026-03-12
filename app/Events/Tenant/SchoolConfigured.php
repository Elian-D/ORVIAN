<?php

namespace App\Events\Tenant;

use App\Models\Tenant\School;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SchoolConfigured
{
    use Dispatchable, SerializesModels;

    /**
     * @param School $school La escuela recién creada
     * @param array $academicData Datos para el año escolar (start_date, end_date, etc.)
     */
    public function __construct(
        public School $school,
        public array $academicData
    ) {}
}