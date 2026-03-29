<?php

namespace App\Livewire\Shared\Roles;

use App\Models\Role;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

#[Title('Formulario de Rol')]
class RoleForm extends Component
{
    public ?Role $role = null;
    public bool $isGlobal;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|regex:/^#[0-9A-Fa-f]{6}$/')]
    public string $color = '#64748B';

    public function mount(?Role $role = null): void
    {
        $this->isGlobal = request()->is('admin/*');

        if ($role) {
            // Modo edición
            if ($this->isGlobal) {
                $role = Role::withoutGlobalScopes()->findOrFail($role->id);
            }

            $this->role = $role;
            $this->name = $role->name;
            $this->color = $role->color ?? '#64748B';
        }
    }

    public function save()
    {
        $this->validate();

        if ($this->role) {
            // Edición: solo actualizar color si no es sistema
            if (!$this->role->is_system) {
                $this->role->name = $this->name;
            }

            $this->role->color = $this->color;
            $this->role->save();

            session()->flash('success', 'Rol actualizado exitosamente.');
        } else {
            // Creación
            $role = Role::create([
                'name' => $this->name,
                'color' => $this->color,
                'guard_name' => 'web',
                // Asegúrate de usar la misma fuente que el SchoolScope (Auth::user()->school_id)
                'school_id' => $this->isGlobal ? null : Auth::user()->school_id, 
                'is_system' => false,
            ]);

            session()->flash('success', 'Rol creado exitosamente. Ahora asigna permisos.');

            $route = $this->isGlobal ? 'admin.roles.permissions' : 'app.roles.permissions';
            
            // Pasamos el ID explícitamente para evitar que el binding falle prematuramente
            return redirect()->route($route, ['role' => $role->id]);
        }

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
        $view = view('livewire.shared.roles.role-form');

        return $view->layout($layout, $layoutProps ?? []);
    }
}
