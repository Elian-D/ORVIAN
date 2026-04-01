<?php

namespace App\Services\School;

use App\Models\Tenant\School;
use Spatie\Permission\Models\Role;

/**
 * Clona los roles de sistema (school_id = null) como roles propios de la escuela.
 * * Ahora también hereda atributos visuales como el 'color' para asegurar 
 * consistencia en la UI (badges, gráficos, etc.) a través de los tenants.
 *
 * Por qué clonar en lugar de reutilizar los globales:
 * Con Spatie Teams activado, los roles se buscan por school_id.
 * Clonarlos permite además personalizar permisos y colores por tenant 
 * independientemente en el futuro sin afectar al resto del sistema.
 */
class SchoolRoleService
{
    /**
     * Roles base que toda escuela debe tener.
     */
    private const BASE_ROLES = [
        'School Principal',
        'Teacher',
        'Secretary',
        'Student',
        'Staff',
    ];

    /**
     * Crea los roles base para la escuela, copiando permisos y atributos (color)
     * del rol global de referencia.
     */
    public function seedDefaultRoles(School $school): void
    {
        // 1. FASE DE LECTURA: Apagamos el team_id para que Spatie nos deje ver los roles globales
        setPermissionsTeamId(null);

        // Pre-cargamos todos los roles globales y sus permisos en memoria de una sola vez (Optimizado)
        $globalRoles = Role::withoutGlobalScope(\App\Models\Scopes\SchoolScope::class)
            ->whereIn('name', self::BASE_ROLES)
            ->whereNull('school_id')
            ->with('permissions')
            ->get();

        // 2. FASE DE ESCRITURA: Activamos el team_id de la escuela para crear sus clones
        setPermissionsTeamId($school->id);

        foreach (self::BASE_ROLES as $roleName) {
            // Buscamos el rol en la colección que ya tenemos en memoria
            $globalRole = $globalRoles->firstWhere('name', $roleName);

            // Determinar el color: Usar el del global o un fallback neutral
            $color = $globalRole ? $globalRole->color : '#64748B';

            // Crear (o recuperar) el rol en el scope de esta escuela
            $tenantRole = Role::firstOrCreate(
                [
                    'name'       => $roleName,
                    'guard_name' => 'web',
                    'school_id'  => $school->id,
                ],
                [
                    'color'      => $color,
                    'is_system'  => true,
                ]
            );

            // Copiar permisos del rol global si existe y el rol de la escuela es nuevo
            if ($globalRole && $tenantRole->wasRecentlyCreated) {
                // Usamos los permisos que ya pre-cargamos, evitando consultas extra
                $this->clonePermissions($globalRole, $tenantRole);
            }
        }

        // Restaurar scope global para no afectar operaciones posteriores
        // setPermissionsTeamId(null);
    }

    /**
     * Copia los permisos de un rol origen a un rol destino.
     * Útil para resetear permisos de un rol tenant a sus valores de referencia.
     */
    public function clonePermissions(Role $source, Role $target): void
    {
        $target->syncPermissions($source->permissions);
    }
}