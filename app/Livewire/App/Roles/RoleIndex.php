<?php

namespace App\Livewire\App\Roles;

use App\Filters\App\Roles\TenantRoleFilters;
use App\Livewire\Base\DataTable;
use App\Models\Role;
use App\Tables\App\RoleTableConfig;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Roles y Permisos')]
class RoleIndex extends DataTable
{
    #[Url]
    public array $filters = [
        'search'    => '',
        'is_system' => '',
    ];

    public ?int $roleToDelete = null;

    protected function getTableDefinition(): string
    {
        return RoleTableConfig::class;
    }

    /**
     * Duplica un rol existente dentro de la escuela.
     */
    public function duplicate(int $roleId)
    {
        // El SchoolScope se encarga de que solo busquemos en la escuela actual
        $original = Role::findOrFail($roleId);

        $duplicate = $original->replicate([
            'permissions', 
        ]);

        $duplicate->name = $original->name . ' (Copia)';
        $duplicate->is_system = false;
        // Asignamos explícitamente el school_id para evitar cualquier desvío del scope
        $duplicate->school_id = Auth::user()->current_school_id;
        $duplicate->save();

        session()->flash('success', 'Rol duplicado exitosamente. Ahora puedes ajustar sus permisos.');

        return redirect()->route('app.roles.edit', $duplicate);
    }

    /**
     * Valida si el rol se puede eliminar antes de mostrar el modal.
     */
    public function confirmDelete(int $roleId): void
    {
        $role = Role::findOrFail($roleId);

        if ($role->is_system) {
            $this->dispatch('notify', 
                type: 'error', 
                title: 'Acción denegada',
                message: 'Los roles base del sistema no pueden eliminarse.'
            );
            return;
        }

        if ($role->users()->count() > 0) {
            $this->dispatch('notify', 
                type: 'error', 
                title: 'Rol en uso',
                message: 'No puedes eliminar un rol que tiene usuarios asignados.'
            );
            return;
        }

        $this->roleToDelete = $roleId;
        $this->dispatch('open-modal', 'confirm-delete-role');
    }

    /**
     * Ejecuta la eliminación del rol.
     */
    public function delete(): void
    {
        if (!$this->roleToDelete) return;

        $role = Role::findOrFail($this->roleToDelete);

        // Doble verificación de seguridad
        if ($role->is_system) {
            $this->dispatch('notify', type: 'error', title: 'Error', message: 'No se puede eliminar un rol de sistema.');
            $this->roleToDelete = null;
            return;
        }

        if ($role->users()->count() > 0) {
            $this->dispatch('notify', type: 'error', title: 'Error', message: 'El rol tiene usuarios vinculados.');
            $this->roleToDelete = null;
            return;
        }

        $role->delete();
        
        $this->dispatch('notify', 
            type: 'success', 
            title: 'Rol eliminado',
            message: 'El rol ha sido removido correctamente.'
        );

        $this->roleToDelete = null;
    }

    /**
     * Propiedad computada para las estadísticas de la vista.
     */
    public function getStatsProperty()
    {
        $schoolId = Auth::user()->school_id;

        // 2. Roles Propios: El SchoolScope filtrará automáticamente por school_id.
        $customRolesCount = Role::where('is_system', false)->count();

        // 3. Usuarios con Acceso Total (Tenant Super Users)
        // Usamos tu modelo de Permission y el scope del PermissionGroup.
        $totalTenantPermissionsCount = \App\Models\Permission::whereHas('group', function($q) {
                $q->where('context', \App\Models\PermissionGroup::CONTEXT_TENANT);
            })->count();

        // Buscamos usuarios que, en el equipo (escuela) actual, sumen todos los permisos.
        // Spatie almacena esto en la tabla intermedia model_has_permissions o vía roles.
        // La forma más segura es comparar la cuenta de permisos efectivos.
        $privilegedUsers = \App\Models\User::where('school_id', $schoolId)
            ->whereHas('roles', function($q) use ($totalTenantPermissionsCount) {
                $q->withCount('permissions')
                ->having('permissions_count', '=', $totalTenantPermissionsCount);
            })
            ->count();

        // 4. Último cambio (con el scope activo traerá el último de la escuela)
        $lastModified = Role::latest('updated_at')->first();

        return [
            'custom_roles'     => $customRolesCount,
            'privileged_users' => $privilegedUsers,
            'last_name'        => $lastModified?->name ?? 'Ninguno',
            'last_time'        => $lastModified?->updated_at?->diffForHumans() ?? '-',
        ];
    }

    public function render()
    {
        $query = Role::query()
            ->withCount('users')
            ->with(['users' => function($q) {
                $q->latest()->limit(3);
            }]);

        $roles = (new TenantRoleFilters($this->filters))
            ->apply($query)
            ->latest()
            ->paginate($this->perPage);

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.roles.role-index', [
            'roles' => $roles,
        ]);

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }
}