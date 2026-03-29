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

            foreach ($permissions as $permissionName) {
                Permission::updateOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['group_id' => $group?->id]
                );
            }
        }
    }
}