<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\School;
use App\Events\Tenant\SchoolConfigured;
use Illuminate\Support\Facades\DB;

class CompleteOnboardingAction
{
    public function __construct(
        protected CreateSchoolPrincipalAction $createPrincipal
    ) {}

    /**
     * Finaliza el proceso de Onboarding del Wizard.
     */
    public function execute(array $wizardData): School
    {
        return DB::transaction(function () use ($wizardData) {
            // 1. Crear la Escuela con los datos anidados en 'school'
            $school = School::create([
                'sigerd_code'             => $wizardData['school']['sigerd_code'],
                'name'                    => $wizardData['school']['name'],
                'regimen_gestion'         => $wizardData['school']['regimen_gestion'],
                'modalidad'               => $wizardData['school']['modalidad'],
                'regional_education_id'   => $wizardData['school']['regional_education_id'],
                'educational_district_id' => $wizardData['school']['educational_district_id'],
                'municipality_id'         => $wizardData['school']['municipality_id'],
                'phone'                   => $wizardData['school']['phone'],
                'address_detail'          => $wizardData['school']['address_detail'],
                'plan_id'                 => $wizardData['plan_id'], // Este sí está en la raíz
                'is_active'               => true,
                'is_configured'           => true,
            ]);

            // 2. Sincronizar Relaciones (Datos anidados en 'academic')
            $school->levels()->sync($wizardData['academic']['level_ids']);

            // REEMPLAZO PARA SHIFTS (HasMany no soporta sync)
            $school->shifts()->delete(); // Limpia por si acaso (aunque sea creación inicial)
            foreach ($wizardData['academic']['shift_ids'] as $type) {
                $school->shifts()->create(['type' => $type]);
            }

            // 3. Títulos técnicos (Usamos la llave 'title_ids' que definiste en el componente)
            if (!empty($wizardData['academic']['title_ids'])) {
                $school->technicalTitles()->sync($wizardData['academic']['title_ids']);
            }

            // 4. Crear al Director (Datos anidados en 'principal')
            $this->createPrincipal->execute($wizardData['principal'], $school->id);

            // 5. Disparar Evento
            // Pasamos el array 'academic' completo que contiene las fechas y el nombre del año
            event(new SchoolConfigured($school, $wizardData['academic']));

            return $school;
        });
    }
}