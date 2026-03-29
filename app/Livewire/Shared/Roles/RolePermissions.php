<?php

namespace App\Livewire\Shared\Roles;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Asignar permisos al rol')]
class RolePermissions extends Component
{
    public Role $role;
    public bool $isGlobal;
    
    // Estado diferido: usamos IDs numéricos como claves para evitar problemas con puntos en nombres
    public array $originalPermissions = []; // [id => true/false]
    public array $selectedPermissions = []; // [id => true/false]
    
    // Permisos agrupados por PermissionGroup
    public $groupedPermissions;
    
    // Cache de todos los permisos para evitar queries repetidas
    public $allPermissions;
    
    public function mount(Role $role): void
    {
        $this->isGlobal = request()->is('admin/*');
        
        // Buscamos el rol manualmente ignorando los scopes para evitar el 404
        // Si $role ya es un objeto (por el binding), extraemos el ID, si no, usamos el valor directo.
        $roleId = $role instanceof Role ? $role->id : $role;

        $this->role = Role::withoutGlobalScopes()->findOrFail($roleId);
        
        // Determinar contexto
        $context = $this->isGlobal ? 'global' : 'tenant';
        
        // Cargar TODOS los permisos del contexto
        $this->allPermissions = Permission::query()
            ->with('group')
            ->whereHas('group', fn($q) => $q->where('context', $context))
            ->get();
        
        // Obtener IDs de permisos actuales del rol
        $currentPermissionIds = $this->role->permissions->pluck('id')->toArray();
        
        // Inicializar selectedPermissions con TODOS los permisos (true/false)
        foreach ($this->allPermissions as $permission) {
            $isSelected = in_array($permission->id, $currentPermissionIds);
            $this->selectedPermissions[$permission->id] = $isSelected;
            $this->originalPermissions[$permission->id] = $isSelected;
        }
        
        // Agrupar permisos por slug del grupo
        $this->groupedPermissions = $this->allPermissions
            ->groupBy(fn($p) => $p->group->slug)
            ->map(fn($permissions) => $permissions->sortBy('name'));
    }
    
    /**
     * Calcula cuántos permisos han cambiado en un grupo específico.
     */
    public function getChangesCount(string $groupSlug): int
    {
        if (!isset($this->groupedPermissions[$groupSlug])) {
            return 0;
        }
        
        $count = 0;
        foreach ($this->groupedPermissions[$groupSlug] as $permission) {
            $original = $this->originalPermissions[$permission->id] ?? false;
            $current = $this->selectedPermissions[$permission->id] ?? false;
            
            if ($original !== $current) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Marca todos los permisos de un grupo.
     */
    public function selectAll(string $groupSlug): void
    {
        if ($this->role->is_system) {
            session()->flash('error', 'No puedes modificar permisos de un rol del sistema.');
            return;
        }
        
        if (!isset($this->groupedPermissions[$groupSlug])) {
            return;
        }
        
        foreach ($this->groupedPermissions[$groupSlug] as $permission) {
            $this->selectedPermissions[$permission->id] = true;
        }
    }
    
    /**
     * Desmarca todos los permisos de un grupo.
     */
    public function deselectAll(string $groupSlug): void
    {
        if ($this->role->is_system) {
            session()->flash('error', 'No puedes modificar permisos de un rol del sistema.');
            return;
        }
        
        if (!isset($this->groupedPermissions[$groupSlug])) {
            return;
        }
        
        foreach ($this->groupedPermissions[$groupSlug] as $permission) {
            $this->selectedPermissions[$permission->id] = false;
        }
    }
    
    /**
     * Guarda los cambios en la base de datos.
     */
    public function save()
    {
        if ($this->role->is_system) {
            session()->flash('error', 'No puedes modificar permisos de un rol del sistema.');
            return;
        }
        
        // Configurar contexto de equipo si es tenant
        if (!$this->isGlobal) {
            setPermissionsTeamId(Auth::user()->current_school_id);
        }
        
        // Filtrar solo los permisos seleccionados (true) y obtener sus IDs
        $selectedIds = array_keys(array_filter($this->selectedPermissions, fn($value) => $value === true));
        
        // Sincronizar con Spatie usando IDs
        $this->role->syncPermissions($selectedIds);
        
        session()->flash('success', 'Permisos actualizados exitosamente.');
        
        // Redirigir al index
        $route = $this->isGlobal ? 'admin.roles.index' : 'app.roles.index';
        return redirect()->route($route);
    }
    
    public function render()
    {
        $layout = $this->isGlobal ? 'components.admin' : 'layouts.app-module';

        $layoutProps = $this->isGlobal
            ? []
            : config('modules.configuracion');

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.shared.roles.role-permissions');

        return $view->layout($layout, $layoutProps ?? []);
    }
}