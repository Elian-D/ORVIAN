<?php

namespace Database\Seeders\AppInit;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAcademicSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Aseguramos el scope global para roles base
        setPermissionsTeamId(null);

        // 2. Definir Roles de Escuela (Inmutables por lógica de negocio)
        $schoolRoles = [
            'School Principal' => 'Director con control total sobre su centro educativo.',
            'Teacher'          => 'Docente con acceso a gestión de notas y asistencia.',
            'Secretary'        => 'Personal administrativo para inscripciones y reportes.',
            'Student'          => 'Acceso a perfil académico y materiales de clase.',
        ];

        foreach ($schoolRoles as $roleName => $description) {
            Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
                'school_id'  => null, // Siguen siendo globales pero se asignarán con scope
            ]);
        }

        // 3. Permisos específicos del Tenant (Escuela)
        $tenantPermissions = [
            'manage academic years',
            'manage sections',
            'view enrollment',
            'mark attendance',
            'upload grades',
            'manage teachers',
        ];

        foreach ($tenantPermissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        // 4. Asignación inicial de permisos a roles base
        $principal = Role::where('name', 'School Principal')->first();
        $principal->syncPermissions($tenantPermissions); // El director puede hacer todo lo del tenant

        $teacher = Role::where('name', 'Teacher')->first();
        $teacher->syncPermissions(['mark attendance', 'upload grades', 'view enrollment']);
        
        $secretary = Role::where('name', 'Secretary')->first();
        $secretary->syncPermissions(['manage sections', 'view enrollment', 'manage teachers']);
    }
}