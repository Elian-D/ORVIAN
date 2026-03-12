<?php

namespace Database\Seeders\AppInit;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleOwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. IMPORTANTE: Forzamos el team_id a null para crear roles globales
        setPermissionsTeamId(null);

        // 2. Definir Roles Globales
        $roles = [
            'Owner' => 'Acceso total al sistema y gestión de escuelas.',
            'TechnicalSupport' => 'Soporte técnico y visualización de logs.',
            'Administrative' => 'Gestión de planes, facturación y reportes globales.',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'school_id' => null, // Aseguramos que sea global
            ]);
        }

        // 3. Permisos básicos de administración global (ejemplos)
        $permissions = [
            'access admin hub',
            'manage schools',
            'manage plans',
            'impersonate users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // 4. Asignar todos los permisos al Owner
        $ownerRole = Role::where('name', 'Owner')->first();
        $ownerRole->syncPermissions(Permission::all());

        // 5. El Support solo accede al hub y gestiona escuelas (lectura)
        $supportRole = Role::where('name', 'TechnicalSupport')->first();
        $supportRole->givePermissionTo(['access admin hub', 'manage schools']);
    }
}