<?php

namespace Database\Seeders\AppInit;

use Spatie\Permission\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleAcademicSeeder extends Seeder
{
    public function run(): void
    {
        // Importante: Desactivamos el team_id para manejar roles globales
        setPermissionsTeamId(null);

        // 1. Definición de Roles Base y Colores
        $schoolRoles = [
            ['name' => 'School Principal',     'color' => '#4F46E5'], 
            ['name' => 'Academic Coordinator', 'color' => '#8B5CF6'], 
            ['name' => 'Teacher',              'color' => '#0EA5E9'], 
            ['name' => 'Secretary',            'color' => '#F59E0B'], 
            ['name' => 'Student',              'color' => '#10B981'], 
            ['name' => 'Staff',                'color' => '#64748B'], 
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

        // 2. Asignación de Permisos Reales

        // --- SCHOOL PRINCIPAL ---
        // Acceso total al contexto 'tenant'
        $principal = Role::where('name', 'School Principal')->first();
        $tenantPermissions = Permission::whereHas('group', function($q) {
            $q->where('context', 'tenant');
        })->get();
        $principal->syncPermissions($tenantPermissions);

        // --- ACADEMIC COORDINATOR (El Supervisor) ---
        $coordinator = Role::where('name', 'Academic Coordinator')->first();
        $coordinator->syncPermissions([
            'users.view',
            'roles.view',
            'students.view', 'students.edit',
            'teachers.view', 'teachers.assign_subjects',
            'attendance_plantel.view', 'attendance_plantel.open_session', 'attendance_plantel.close_session', 'attendance_plantel.reports', 'attendance_plantel.verify',
            'attendance_classroom.view', 'attendance_classroom.reports',
            'excuses.view', 'excuses.submit', 'excuses.approve', 'excuses.reject',
            'settings.view',
        ]);

        // --- TEACHER (Operativa de Aula) ---
        $teacher = Role::where('name', 'Teacher')->first();
        $teacher->syncPermissions([
            'users.view',
            'students.view',
            'attendance_classroom.view', 'attendance_classroom.record', 'attendance_classroom.edit', 'attendance_classroom.reports',
            'excuses.view', 'excuses.submit',
        ]);

        // --- SECRETARY (Operativa de Entrada/Salida) ---
        $secretary = Role::where('name', 'Secretary')->first();
        $secretary->syncPermissions([
            'users.view', 'users.create',
            'students.view', 'students.create', 'students.edit', 'students.import',
            'attendance_plantel.view', 'attendance_plantel.record', 'attendance_plantel.qr', 'attendance_plantel.reports',
            'excuses.view', 'excuses.submit',
        ]);

        // --- STUDENT ---
        $studentRole = Role::where('name', 'Student')->first();
        $studentRole->syncPermissions([
            'attendance_classroom.view',
            'excuses.view', 'excuses.submit',
        ]);
    }
}