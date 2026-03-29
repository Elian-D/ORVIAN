<?php

namespace Database\Seeders\AppInit;

use Spatie\Permission\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleAcademicSeeder extends Seeder
{
    public function run(): void
    {
        setPermissionsTeamId(null);

    // 1. Roles base de Escuela con sus colores
    $schoolRoles = [
        ['name' => 'School Principal', 'color' => '#4F46E5'], // Indigo
        ['name' => 'Teacher',          'color' => '#0EA5E9'], // Sky
        ['name' => 'Secretary',        'color' => '#F59E0B'], // Amber
        ['name' => 'Student',          'color' => '#10B981'], // Emerald
        ['name' => 'Staff',            'color' => '#64748B'], // Slate
    ];

    foreach ($schoolRoles as $roleData) {
        Role::firstOrCreate(
            ['name' => $roleData['name'], 'guard_name' => 'web'],
            [
                'school_id' => null, 
                'color' => $roleData['color'],
                'is_system' => true
            ]
        );
    }

        // 2. Definición de permisos por Rol (Usando los nuevos nombres)
        
        // El Principal (Director) tiene todo lo del Tenant
        $principal = Role::where('name', 'School Principal')->first();
        $tenantPermissions = Permission::whereHas('group', function($q) {
            $q->where('context', 'tenant');
        })->get();
        $principal->syncPermissions($tenantPermissions);

        // Docente
        $teacher = Role::where('name', 'Teacher')->first();
        $teacher->syncPermissions([
            'users.view', 
            // Aquí agregarás en el futuro: 'asistencia.marcar', 'notas.subir', etc.
        ]);

        // Secretaria
        $secretary = Role::where('name', 'Secretary')->first();
        $secretary->syncPermissions([
            'users.view',
            'users.create',
            'roles.view',
            'settings.view',
        ]);
    }
}