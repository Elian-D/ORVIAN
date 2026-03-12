<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\School;
use App\Models\Tenant\Plan;
use Illuminate\Support\Facades\DB;

class CreateSchoolAction
{
    /**
     * Ejecuta la lógica de creación de una escuela.
     * * @param array $data Datos validados de la escuela
     * @return School
     */
    public function execute(array $data): School
    {
        return DB::transaction(function () use ($data) {
            // 1. Crear la escuela
            $school = School::create([
                'sigerd_code'     => $data['sigerd_code'],
                'name'            => $data['name'],
                'modalidad'       => $data['modalidad'],
                'sector'          => $data['sector'],
                'jornada'         => $data['jornada'],
                'municipality_id' => $data['municipality_id'],
                'district_id'     => $data['district_id'] ?? null,
                'plan_id'         => $data['plan_id'] ?? Plan::where('slug', Plan::BASIC)->first()->id,
                'is_active'       => true,
                'is_configured'   => false, // Se marcará true al final del Wizard
            ]);

            // 2. Aquí podrías disparar otras acciones lógicas:
            // (new CreateInitialAcademicPeriodsAction())->execute($school);
            
            // 3. Opcional: Registrar logs o telemetría
            
            return $school;
        });
    }
}