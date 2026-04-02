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
            ['name' => 'School Principal',     'color' => '#4F46E5'], // Indigo
            ['name' => 'Academic Coordinator', 'color' => '#8B5CF6'], // Violet (Nuevo)
            ['name' => 'Teacher',              'color' => '#0EA5E9'], // Sky
            ['name' => 'Secretary',            'color' => '#F59E0B'], // Amber
            ['name' => 'Student',              'color' => '#10B981'], // Emerald
            ['name' => 'Staff',                'color' => '#64748B'], // Slate
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

        // 2. Definición de permisos por Rol

        // --- SCHOOL PRINCIPAL (Director) ---
        // Tiene acceso total a todo lo que pertenezca al contexto 'tenant'
        $principal = Role::where('name', 'School Principal')->first();
        $tenantPermissions = Permission::whereHas('group', function($q) {
            $q->where('context', 'tenant');
        })->get();
        $principal->syncPermissions($tenantPermissions);


        // --- ACADEMIC COORDINATOR ---
        $coordinator = Role::where('name', 'Academic Coordinator')->first();
        $coordinator->syncPermissions([
            'users.view',
            'roles.view',
            'students.view', 'students.edit',
            'teachers.view', 'teachers.assign_subjects',
            'attendance_plantel.view', 'attendance_plantel.open_session', 'attendance_plantel.close_session',
            'attendance_classroom.view', 'attendance_classroom.reports',
            'excuses.view', 'excuses.submit', 'excuses.approve', 'excuses.reject',
        ]);


        // --- TEACHER (Docente) ---
        $teacher = Role::where('name', 'Teacher')->first();
        $teacher->syncPermissions([
            'users.view',
            'students.view',
            'attendance_classroom.view', 'attendance_classroom.record', 'attendance_classroom.edit', 'attendance_classroom.reports',
            'excuses.view', 'excuses.submit',
        ]);


        // --- SECRETARY (Secretaria) ---
        $secretary = Role::where('name', 'Secretary')->first();
        $secretary->syncPermissions([
            'users.view',
            'users.create',
            'roles.view',
            'settings.view',
            'students.view', 'students.create', 'students.edit', 'students.delete', 'students.import',
            'teachers.view',
            'attendance_plantel.view', 'attendance_plantel.record', 'attendance_plantel.reports',
            'excuses.view', 'excuses.submit',
        ]);

        // --- STUDENT ---
        // Generalmente solo lectura de su propio perfil y ver sus asistencias/excusas
        $studentRole = Role::where('name', 'Student')->first();
        $studentRole->syncPermissions([
            'attendance_classroom.view',
            'excuses.view',
            'excuses.submit',
        ]);
    }
}