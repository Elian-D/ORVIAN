<?php

namespace App\Actions\Tenant;

use App\Events\Tenant\SchoolConfigured;
use App\Models\Tenant\School;
use App\Models\User;
use App\Services\Communications\ChatwootService;
use App\Services\School\SchoolRoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompleteTenantOnboardingAction
{
    public function __construct(
        protected SchoolRoleService $roleService,
        protected ChatwootService $chatwootService,
    ) {}

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
                'province_id'             => $wizardData['school']['province_id'],
                'phone'                   => $wizardData['school']['phone'],
                'address_detail'          => $wizardData['school']['address_detail'],
                'plan_id'                 => $wizardData['plan_id'],
                'is_active'               => true,
                'is_configured'           => true,
                'stub_expires_at'         => null,
            ]);

            // 2. Sincronizar Niveles
            $school->levels()->sync($wizardData['academic']['level_ids']);

            // 3. Gestionar Tandas
            $school->shifts()->delete();
            
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

            // 4. Títulos Técnicos
            if (!empty($wizardData['academic']['title_ids'])) {
                $school->technicalTitles()->sync($wizardData['academic']['title_ids']);
            }

            // 5. Crear roles base del tenant ANTES de asignarlos al usuario.
            //    El service maneja setPermissionsTeamId internamente y lo resetea a null al terminar.
            $this->roleService->seedDefaultRoles($school);

            // 6. Asignar rol de Director al usuario actual.
            setPermissionsTeamId($school->id);
            
            // EL FIX: Buscamos la instancia en lugar de usar el string
            $tenantRole = \App\Models\Role::where('name', 'School Principal')
                ->where('school_id', $school->id)
                ->firstOrFail();

            if (! $principalUser->hasRole($tenantRole)) {
                $principalUser->assignRole($tenantRole);
            }
            
            setPermissionsTeamId(null); // Resetear scope antes de disparar el evento

            // 7. Disparar Evento
            event(new SchoolConfigured($school, $wizardData['academic']));

            // 8. Sincronizar Director con Chatwoot — justo después de que el rol fue asignado.
            $this->syncPrincipalToChatwoot($principalUser);

            return $school;
        });
    }

    /**
     * Sincroniza el Director (usuario existente) con Chatwoot como Agente.
     * El rol 'School Principal' ya fue asignado en el paso 6 antes de esta llamada.
     */
    protected function syncPrincipalToChatwoot(User $user): void
    {
        try {
            $existing = $this->chatwootService->findAgentByEmail($user->email);

            if ($existing) {
                Log::info("CompleteTenantOnboardingAction: Agente ya existe en Chatwoot [{$user->email}]");
                return;
            }

            $response = $this->chatwootService->createAgent(
                name:  $user->name,
                email: $user->email,
                role:  'agent',
            );

            if ($response->successful()) {
                Log::info("CompleteTenantOnboardingAction: Agente creado en Chatwoot [{$user->email}]");

                // Persistir el chatwoot_agent_id en preferences para uso futuro
                $user->updateQuietly([
                    'preferences' => array_merge($user->preferences ?? [], [
                        'chatwoot_agent_id' => $response->json('id'),
                    ]),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error("CompleteTenantOnboardingAction: Fallo al crear agente en Chatwoot", [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}