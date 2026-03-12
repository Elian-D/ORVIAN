<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\School;
use App\Models\User;
use App\Events\Tenant\SchoolConfigured;
use Illuminate\Support\Facades\DB;

class CompleteTenantOnboardingAction
{
    /**
     * Finaliza la configuración para una escuela que ya existe (Stub).
     */
    public function execute(int $schoolId, array $wizardData, User $principalUser): School
    {
        return DB::transaction(function () use ($schoolId, $wizardData, $principalUser) {
            $school = School::findOrFail($schoolId);

            // 1. Actualizar la Escuela Stub
            $school->update([
                'sigerd_code'             => $wizardData['school']['sigerd_code'],
                'name'                    => $wizardData['school']['name'],
                'regimen_gestion'         => $wizardData['school']['regimen_gestion'],
                'modalidad'               => $wizardData['school']['modalidad'],
                'regional_education_id'   => $wizardData['school']['regional_education_id'],
                'educational_district_id' => $wizardData['school']['educational_district_id'],
                'municipality_id'         => $wizardData['school']['municipality_id'],
                'phone'                   => $wizardData['school']['phone'],
                'address_detail'          => $wizardData['school']['address_detail'],
                'plan_id'                 => $wizardData['plan_id'],
                'is_active'               => true,
                'is_configured'           => true,
                'stub_expires_at'         => null, // Limpiamos el TTL de expiración
            ]);

            // 2. Sincronizar Niveles
            $school->levels()->sync($wizardData['academic']['level_ids']);

            // 3. Gestionar Tandas (Shifts)
            $school->shifts()->delete(); 
            foreach ($wizardData['academic']['shift_ids'] as $type) {
                $school->shifts()->create(['type' => $type]);
            }

            // 4. Títulos Técnicos
            if (!empty($wizardData['academic']['title_ids'])) {
                $school->technicalTitles()->sync($wizardData['academic']['title_ids']);
            }

            // 5. Asignar Rol de Director al usuario actual
            // Importante: Usamos el scope de Spatie para el school_id
            setPermissionsTeamId($school->id);
            if (!$principalUser->hasRole('School Principal')) {
                $principalUser->assignRole('School Principal');
            }

            // 6. Disparar Evento para que los Listeners creen la estructura académica
            event(new SchoolConfigured($school, $wizardData['academic']));

            return $school;
        });
    }
}