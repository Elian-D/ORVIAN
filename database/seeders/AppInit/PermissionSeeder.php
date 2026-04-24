<?php

namespace Database\Seeders\AppInit;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // --- TENANT ---
            'usuarios' => [
                'users.view', 'users.create', 'users.edit', 'users.delete'
            ],
            'roles' => [
                'roles.view', 'roles.create', 'roles.edit', 'roles.delete'
            ],
            'configuracion' => [
                'settings.view', 'settings.update'
            ],
            // Nuevos permisos integrados
            'students' => [
                'students.view', 'students.create', 'students.edit', 'students.delete', 'students.import'
            ],
            'teachers' => [
                'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete', 'teachers.assign_subjects'
            ],
            'attendance_plantel' => [
                'attendance_plantel.view', 'attendance_plantel.open_session', 'attendance_plantel.close_session', 
                'attendance_plantel.record', 'attendance_plantel.qr', 'attendance_plantel.facial', 
                'attendance_plantel.verify', 'attendance_plantel.reports'
            ],
            'attendance_classroom' => [
                'attendance_classroom.view', 'attendance_classroom.record', 'attendance_classroom.edit', 'attendance_classroom.reports'
            ],
            'excuses' => [
                'excuses.view', 'excuses.submit', 'excuses.approve', 'excuses.reject'
            ],

            // --- GLOBAL ---
            'escuelas' => [
                'schools.view', 'schools.create', 'schools.edit', 'schools.delete'
            ],
            'planes' => [
                'plans.view', 'plans.manage'
            ],
            'usuarios_globales' => [
                'global_users.view', 'global_users.manage'
            ],
            'sistema' => [
                'admin.access', 'users.impersonate'
            ],
            'logs' => [
                'logs.activity', 'logs.auth'
            ],
            'roles_globales' => [
                'roles.inspect', 'roles.manage'
            ],
        ];

        foreach ($data as $slug => $permissions) {
            $group = PermissionGroup::where('slug', $slug)->first();

            if (!$group) {
                // Alerta amarilla en consola si el slug no existe
                $this->command->warn("⚠️  Grupo de permiso no encontrado: [{$slug}]. Saltando sus permisos...");
                continue;
            }

            foreach ($permissions as $permissionName) {
                Permission::updateOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['group_id' => $group->id]
                );
            }
        }
    }
}