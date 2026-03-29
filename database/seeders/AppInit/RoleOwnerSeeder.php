<?php

namespace Database\Seeders\AppInit;

use Spatie\Permission\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleOwnerSeeder extends Seeder
{
    public function run(): void
    {
        // Forzamos scope global
        setPermissionsTeamId(null);

        // 1. Definir Roles Globales con colores potentes
        $roles = [
            ['name' => 'Owner',            'color' => '#7C3AED'], // Violet
            ['name' => 'TechnicalSupport', 'color' => '#06B6D4'], // Cyan
            ['name' => 'Administrative',   'color' => '#EC4899'], // Pink
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => 'web'],
                [
                    'school_id' => null,
                    'color' => $roleData['color'],
                    'is_system' => true
                ]
            );
        }

        // 2. Asignar Permisos al Owner (Acceso Total Global)
        $ownerRole = Role::where('name', 'Owner')->first();
        // El Owner recibe todos los permisos que no tienen school_id y pertenecen a grupos globales
        $globalPermissions = Permission::whereHas('group', function($q) {
            $q->where('context', 'global');
        })->get();
        
        $ownerRole->syncPermissions($globalPermissions);

        // 3. TechnicalSupport (Soporte técnico)
        $supportRole = Role::where('name', 'TechnicalSupport')->first();
        $supportRole->syncPermissions([
            'admin.access',
            'schools.view',
            'logs.activity',
            'logs.auth',
        ]);

        // 4. Administrative (Gestión comercial)
        $adminRole = Role::where('name', 'Administrative')->first();
        $adminRole->syncPermissions([
            'admin.access',
            'schools.view',
            'schools.create',
            'schools.edit',
            'plans.view',
            'plans.manage',
            'global_users.view',
        ]);
    }
}