<?php

namespace Database\Seeders\AppInit;

use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            // Context: TENANT
            ['order' => 1, 'slug' => 'usuarios',      'name' => 'Gestión de Usuarios',       'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 2, 'slug' => 'roles',         'name' => 'Roles y Seguridad',         'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 3, 'slug' => 'configuracion', 'name' => 'Configuración del Sistema', 'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 4, 'slug' => 'academico',     'name' => 'Gestión Académica',         'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 5, 'slug' => 'asistencia',    'name' => 'Control de Asistencia',     'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 6, 'slug' => 'reportes',      'name' => 'Reportes y Estadísticas',   'context' => PermissionGroup::CONTEXT_TENANT],

            // Context: GLOBAL
            ['order' => 1, 'slug' => 'escuelas',          'name' => 'Gestión de Escuelas',    'context' => PermissionGroup::CONTEXT_GLOBAL],
            ['order' => 2, 'slug' => 'planes',            'name' => 'Planes y Facturación',   'context' => PermissionGroup::CONTEXT_GLOBAL],
            ['order' => 3, 'slug' => 'usuarios_globales', 'name' => 'Usuarios del Sistema',   'context' => PermissionGroup::CONTEXT_GLOBAL],
            ['order' => 4, 'slug' => 'sistema',           'name' => 'Sistema y Acceso',       'context' => PermissionGroup::CONTEXT_GLOBAL],
            ['order' => 5, 'slug' => 'logs',              'name' => 'Logs y Observabilidad',  'context' => PermissionGroup::CONTEXT_GLOBAL],
            ['order' => 6, 'slug' => 'roles_globales',    'name' => 'Seguridad Global',       'context' => PermissionGroup::CONTEXT_GLOBAL],
        ];

        foreach ($groups as $group) {
            PermissionGroup::updateOrCreate(['slug' => $group['slug']], $group);
        }
    }
}