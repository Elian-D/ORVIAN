<?php

namespace App\Livewire\Admin\Roles;

use App\Filters\Admin\Roles\AdminRoleFilters;
use App\Livewire\Base\DataTable;
use App\Models\Role;
use App\Tables\Admin\RoleTableConfig;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Roles Globales')]
#[Layout('components.admin')]
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

    public function duplicate(int $roleId)
    {
        $original = Role::withoutGlobalScopes()->findOrFail($roleId);

        $duplicate = $original->replicate([
            'permissions',
        ]);

        $duplicate->name = $original->name . ' (Copia)';
        $duplicate->is_system = false;
        $duplicate->save();

        // Al haber redirección, usamos notify-redirect (si tu componente lo soporta) o flash normal
        session()->flash('success', 'Rol duplicado exitosamente. Asigna permisos en la matriz.');

        return redirect()->route('admin.roles.edit', $duplicate);
    }

    public function confirmDelete(int $roleId): void
    {
        $role = Role::withoutGlobalScopes()->findOrFail($roleId);

        if ($role->is_system) {
            $this->dispatch('notify', 
                type: 'error', 
                title: 'Acción no permitida', 
                message: 'No puedes eliminar un rol del sistema.'
            );
            return;
        }

        if ($role->users()->count() > 0) {
            $this->dispatch('notify', 
                type: 'error', 
                title: 'Rol en uso', 
                message: 'No puedes eliminar un rol con usuarios asignados.'
            );
            return;
        }

        $this->roleToDelete = $roleId;
        // Opcional: despachar evento para abrir el modal si no lo haces por Alpine directamente
        $this->dispatch('open-modal', 'confirm-delete-role');
    }

    public function delete(): void
    {
        if (!$this->roleToDelete) {
            return;
        }

        $role = Role::withoutGlobalScopes()->findOrFail($this->roleToDelete);

        if ($role->is_system) {
            $this->dispatch('notify', type: 'error', title: 'Error', message: 'No puedes eliminar un rol del sistema.');
            $this->roleToDelete = null;
            return;
        }

        if ($role->users()->count() > 0) {
            $this->dispatch('notify', type: 'error', title: 'Error', message: 'No puedes eliminar un rol con usuarios asignados.');
            $this->roleToDelete = null;
            return;
        }

        $role->delete();

        // Despacho inmediato del toast de éxito
        $this->dispatch('notify',
            type:    'success',
            title:   'Rol eliminado',
            message: 'El rol ha sido removido del sistema correctamente.'
        );

        $this->roleToDelete = null;
    }

    public function render()
    {
        $query = Role::withoutGlobalScopes()
            ->whereNull('school_id')
            ->withCount('users')
            ->with(['users' => function($q) {
                $q->latest()->limit(3);
            }]);

        $roles = (new AdminRoleFilters($this->filters))
            ->apply($query)
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.roles.role-index', [
            'roles' => $roles,
        ]);
    }
}