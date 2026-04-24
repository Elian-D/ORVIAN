<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\School;
use App\Events\Tenant\SchoolConfigured;
use Illuminate\Support\Facades\DB;

class CompleteOnboardingAction
{
    public function __construct(
        protected CreateSchoolPrincipalAction $createPrincipal,
        protected \App\Services\School\SchoolRoleService $roleService, 
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
                'province_id'             => $wizardData['school']['province_id'],
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

            // Definimos los horarios estándar (MINERD)
            $defaultShiftTimes = [
                School::SHIFT_MORNING   => ['start_time' => '07:30:00', 'end_time' => '12:30:00'],
                School::SHIFT_AFTERNOON => ['start_time' => '13:30:00', 'end_time' => '18:00:00'],
                School::SHIFT_EXTENDED  => ['start_time' => '08:00:00', 'end_time' => '16:00:00'],
                School::SHIFT_NIGHT     => ['start_time' => '18:00:00', 'end_time' => '22:00:00'],
            ];

            foreach ($wizardData['academic']['shift_ids'] as $type) {
                $times = $defaultShiftTimes[$type] ?? ['start_time' => '08:00:00', 'end_time' => '12:00:00'];
                
                $school->shifts()->create([
                    'type'       => $type,
                    'start_time' => $times['start_time'],
                    'end_time'   => $times['end_time'],
                ]);
            }

            // 3. Títulos técnicos (Usamos la llave 'title_ids' que definiste en el componente)
            if (!empty($wizardData['academic']['title_ids'])) {
                $school->technicalTitles()->sync($wizardData['academic']['title_ids']);
            }

            // --- PASO CRÍTICO ---
            // 4. CLONAR ROLES PRIMERO. 
            // Esto mete en la tabla 'roles' los registros con 'school_id' = $school->id
            $this->roleService->seedDefaultRoles($school);

            // 5. CREAR DIRECTOR DESPUÉS.
            // Ahora, cuando CreateSchoolPrincipalAction haga ->assignRole('School Principal'),
            // Spatie encontrará el rol que acabamos de crear en el paso 3.
            $this->createPrincipal->execute($wizardData['principal'], $school->id);

            // 6. Resetear ID de equipo al final de todo para seguridad
            setPermissionsTeamId(null);

            event(new SchoolConfigured($school, $wizardData['academic']));

            return $school;
        });
    }
}