<?php

namespace Database\Seeders\AppInit;

use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            // Context: TENANT (Existentes)
            ['order' => 1, 'slug' => 'usuarios',      'name' => 'Gestión de Usuarios',       'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 2, 'slug' => 'roles',         'name' => 'Roles y Seguridad',         'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 3, 'slug' => 'configuracion', 'name' => 'Configuración del Sistema', 'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 4, 'slug' => 'academico',     'name' => 'Gestión Académica',         'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 5, 'slug' => 'asistencia',    'name' => 'Control de Asistencia',     'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 6, 'slug' => 'reportes',      'name' => 'Reportes y Estadísticas',   'context' => PermissionGroup::CONTEXT_TENANT],

            // Context: TENANT (Nuevos grupos específicos para el Módulo de Asistencia)
            ['order' => 7, 'slug' => 'students',      'name' => 'Gestión de Estudiantes',    'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 8, 'slug' => 'teachers',      'name' => 'Gestión de Docentes',       'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 9, 'slug' => 'attendance_plantel', 'name' => 'Asistencia Plantel',   'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 10, 'slug' => 'attendance_classroom', 'name' => 'Asistencia Aula',   'context' => PermissionGroup::CONTEXT_TENANT],
            ['order' => 11, 'slug' => 'excuses',      'name' => 'Gestión de Excusas',        'context' => PermissionGroup::CONTEXT_TENANT],

            // Context: GLOBAL
            ['order' => 1, 'slug' => 'escuelas',          'name' => 'Gestión de Escuelas',    'context' => PermissionGroup::CONTEXT_GLOBAL],
            ['order' => 2, 'slug' => 'planes',            'name' => 'Planes y Facturación',   'context' => PermissionGroup::CONTEXT_GLOBAL],
            ['order' => 3, 'slug' => 'usuarios_globales', 'name' => 'Usuarios del Sistema',   'context' => PermissionGroup::CONTEXT_GLOBAL],
            ['order' => 4, 'slug' => 'sistema',           'name' => 'Sistema y Acceso',       'context' => PermissionGroup::CONTEXT_GLOBAL],
            ['order' => 5, 'slug' => 'logs',              'name' => 'Logs y Observabilidad',  'context' => PermissionGroup::CONTEXT_GLOBAL],
            ['order' => 6, 'slug' => 'roles_globales',    'name' => 'Seguridad Global',       'context' => PermissionGroup::CONTEXT_GLOBAL],
        ];

        foreach ($groups as $group) {
            // Usamos updateOrCreate para mantener consistencia y permitir actualizaciones de nombre/orden
            PermissionGroup::updateOrCreate(
                ['slug' => $group['slug']], 
                $group
            );
        }
    }
}