<?php

namespace App\Livewire\App\Users;

use App\Filters\App\Users\TenantUserFilters;
use App\Livewire\Base\DataTable;
use App\Models\User;
use App\Models\Tenant\School;
use App\Tables\App\TenantUserTableConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Usuarios')]
class UserIndex extends DataTable
{
    // ── Estado del tenant ──────────────────────────────────────────────────

    public int $schoolId = 0;

    // ── Filtros ────────────────────────────────────────────────────────────

    #[Url]
    public array $filters = [
        'search' => '',
        'role'   => '',
        'status' => '',
    ];

    // ── Estado formulario ──────────────────────────────────────────────────

    public bool   $isEditing  = false;
    public ?int   $editingId  = null;
    public ?int   $deletingId = null;

    public string $name     = '';
    public string $email    = '';
    public string $password = '';
    public string $role     = '';
    public string $position = '';

    // ── DataTable ──────────────────────────────────────────────────────────

    protected function getTableDefinition(): string
    {
        return TenantUserTableConfig::class;
    }

    // ── Mount ──────────────────────────────────────────────────────────────

    public function mount(): void
    {
        parent::mount();

        /** @var User $user */
        $user = Auth::user();

        // Guard: debe tener escuela configurada
        if (!$user->school_id) {
            $this->redirect(route('wizard'));
            return;
        }

        $this->schoolId = $user->school_id;
    }

    // ── Render ─────────────────────────────────────────────────────────────

    public function render()
    {
        $school  = School::with('plan')->find($this->schoolId);
        
        // 1. Ajustamos el conteo total para que coincida con lo que se ve (Usuarios que no son Student)
        $total   = User::where('school_id', $this->schoolId)
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Student');
            })->count();

        $limit   = $school?->plan?->limit_users ?? 0;
        $pct     = $limit > 0 ? ($total / $limit) * 100 : 0;
        $atLimit = $limit > 0 && $total >= $limit;

        // 2. Filtramos la Query principal para excluir el rol 'Student'
        $query = User::where('school_id', $this->schoolId)
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Student');
            })
            ->withIndexRelations();

        $users = (new TenantUserFilters($this->filters))
            ->apply($query)
            ->orderBy('id')
            ->paginate($this->perPage);

        // 3. Filtramos las opciones de roles para que no se pueda asignar o filtrar por 'Student' aquí
        $roleOptions = \App\Models\Role::where('school_id', $this->schoolId)
            ->where('name', '!=', 'Student') // Excluir de la lista de selección
            ->orderBy('id')
            ->pluck('name', 'name')
            ->toArray();

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.users.index', [
            'users'       => $users,
            'globalRoles' => $roleOptions, 
            'roleOptions' => $roleOptions, 
            'total'       => $total,
            'limit'       => $limit,
            'pct'         => $pct,
            'atLimit'     => $atLimit,
        ]);

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }

    // ── Formulario ─────────────────────────────────────────────────────────

    public function create(): void
    {
        // Guard: no crear si se alcanzó el límite del plan
        $total   = User::where('school_id', $this->schoolId)
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Student');
            })->count();
        $school  = School::with('plan')->find($this->schoolId);
        $limit   = $school?->plan?->limit_users ?? 0;

        if ($limit > 0 && $total >= $limit) {
            $this->dispatch('notify',
                type:    'warning',
                title:   'Límite alcanzado',
                message: "Tu plan permite máximo {$limit} usuarios."
            );
            return;
        }

        $this->resetForm();
        $this->isEditing = false;
        $this->dispatch('open-modal', 'user-form');
    }

    public function edit(int $userId): void
    {
        $user = User::where('school_id', $this->schoolId)->findOrFail($userId);

        $this->editingId = $user->id;
        $this->name      = $user->name;
        $this->email     = $user->email;
        $this->position  = $user->position ?? '';
        $this->password  = '';
        $this->isEditing = true;

        // Rol en contexto del tenant
        setPermissionsTeamId($this->schoolId);
        $this->role = $user->getRoleNames()->first() ?? '';

        $this->dispatch('open-modal', 'user-form');
    }

    public function closeForm(): void
    {
        $this->dispatch('close-modal', 'user-form');
        $this->resetForm();
    }

    // ── CRUD ───────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->isEditing ? $this->update() : $this->store();
    }

    private function store(): void
    {
        $data = $this->validate([
            'name'     => ['required', 'string', 'min:3', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role'     => ['required', 'string'],
            'position' => ['nullable', 'string', 'max:80'],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'school_id' => $this->schoolId,
            'position'  => $data['position'] ?: null,
        ]);

        setPermissionsTeamId($this->schoolId);
        $user->assignRole($data['role']);

        $this->closeForm();
        $this->dispatch('notify',
            type:    'success',
            title:   'Usuario creado',
            message: "{$data['name']} fue agregado al centro."
        );
    }

    private function update(): void
    {
        $user = User::where('school_id', $this->schoolId)->findOrFail($this->editingId);

        $data = $this->validate([
            'name'     => ['required', 'string', 'min:3', 'max:100'],
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
            'role'     => ['required', 'string'],
            'position' => ['nullable', 'string', 'max:80'],
        ]);

        $payload = [
            'name'     => $data['name'],
            'email'    => $data['email'],
            'position' => $data['position'] ?: null,
        ];

        if (filled($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        setPermissionsTeamId($this->schoolId);
        $user->syncRoles([$data['role']]);

        $this->closeForm();
        $this->dispatch('notify',
            type:    'success',
            title:   'Usuario actualizado',
            message: "{$data['name']} fue actualizado correctamente."
        );
    }

    public function confirmDelete(int $userId): void
    {
        // Guard: no puede eliminarse a sí mismo
        if ($userId === Auth::id()) {
            $this->dispatch('notify',
                type:    'error',
                title:   'Acción no permitida',
                message: 'No puedes eliminar tu propia cuenta.'
            );
            return;
        }

        $this->deletingId = $userId;
        $this->dispatch('open-modal', 'confirm-delete');
    }

    public function delete(int $userId): void
    {
        if ($userId === Auth::id()) return;

        $user = User::where('school_id', $this->schoolId)->findOrFail($userId);
        $user->delete();

        $this->dispatch('notify',
            type:    'success',
            title:   'Usuario eliminado',
            message: "\"{$user->name}\" fue eliminado del centro."
        );
    }

    // ── Helpers estáticos ──────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name      = '';
        $this->email     = '';
        $this->password  = '';
        $this->role      = '';
        $this->position  = '';
        $this->resetErrorBag();
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'online' => 'bg-green-500',
            'away'   => 'bg-amber-400',
            'busy'   => 'bg-red-500',
            default  => 'bg-slate-400',
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'online' => 'En línea',
            'away'   => 'Ausente',
            'busy'   => 'Ocupado',
            default  => 'Desconectado',
        };
    }
}