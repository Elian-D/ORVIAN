<?php

namespace App\Livewire\Admin\Users;

use App\Filters\Admin\Users\AdminUserFilters;
use App\Livewire\Base\DataTable;
use App\Models\User;
use App\Tables\Admin\AdminUserTableConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Usuarios del Sistema')]
#[Layout('components.admin')]
class UserIndex extends DataTable
{
    // ── Filtros ────────────────────────────────────────────

    #[Url]
    public array $filters = [
        'search'  => '',
        'role'    => '',
        'status'  => '',
        'trashed' => '',   // '' → activos | 'only' → eliminados
    ];

    // ── Estado del formulario ──────────────────────────────

    public bool    $isEditing = false;
    public ?int    $editingId = null;

    public ?int    $deletingId = null;

    public string  $name     = '';
    public string  $email    = '';
    public string  $password = '';
    public string  $role     = '';
    public string  $position = '';

    // ── DataTable contract ─────────────────────────────────

    protected function getTableDefinition(): string
    {
        return AdminUserTableConfig::class;
    }

    // ── Render ─────────────────────────────────────────────

    public function render()
    {
        $query = User::whereNull('school_id')->withIndexRelations();

        if ($this->filters['trashed'] === 'only') {
            $query->onlyTrashed();
        }

        $users = (new AdminUserFilters($this->filters))
            ->apply($query)
            ->orderBy('id')
            ->paginate($this->perPage);

        // 1. Roles que son plantillas (excluir de la administración global)
        $templateRoles = ['School Principal', 'Teacher', 'Secretary', 'Student', 'Staff'];

        // 2. Obtenemos los roles y generamos el array directamente ['Admin' => 'Admin', ...]
        $roleOptions = \App\Models\Role::whereNull('school_id')
            ->whereNotIn('name', $templateRoles)
            ->orderBy('id')
            ->pluck('name', 'name') // Llave = nombre, Valor = nombre
            ->toArray();

        return view('livewire.admin.users.index', [
            'users'       => $users,
            'globalRoles' => $roleOptions, // Colección de nombres técnicos para modales
            'roleOptions' => $roleOptions,
        ]);
    }

    // ── Formulario: abrir / cerrar ─────────────────────────

    public function create(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->dispatch('open-modal', 'user-form');
    }

    public function edit(int $userId): void
    {
        /** @var User $user */
        $user = User::whereNull('school_id')->findOrFail($userId);

        $this->editingId = $user->id;
        $this->name      = $user->name;
        $this->email     = $user->email;
        $this->position  = $user->position ?? '';
        $this->role      = $user->getRoleNames()->first() ?? '';
        $this->password  = '';
        $this->isEditing = true;

        $this->dispatch('open-modal', 'user-form');
    }

    public function closeForm(): void
    {
        $this->dispatch('close-modal', 'user-form');
        $this->resetForm();
    }

    // ── CRUD ───────────────────────────────────────────────

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
            'role'     => ['required', 'string', Rule::exists('roles', 'name')],
            'position' => ['nullable', 'string', 'max:80'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'position' => $data['position'] ?: null,
            // school_id queda null → usuario global
        ]);

        // Asignar rol en contexto global (sin school_id)
        setPermissionsTeamId(null);
        $user->assignRole($data['role']);

        $this->closeForm();
        $this->dispatch('notify', type: 'success', message: 'Usuario creado correctamente.');
    }

    private function update(): void
    {
        /** @var User $user */
        $user = User::whereNull('school_id')->findOrFail($this->editingId);

        $data = $this->validate([
            'name'     => ['required', 'string', 'min:3', 'max:100'],
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
            'role'     => ['required', 'string', Rule::exists('roles', 'name')],
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

        setPermissionsTeamId(null);
        $user->syncRoles([$data['role']]);

        $this->closeForm();
        $this->dispatch('notify', type: 'success', message: 'Usuario actualizado correctamente.');
    }

    public function confirmDelete(int $userId): void
    {
        $this->deletingId = $userId;
        $this->dispatch('open-modal', 'confirm-delete');
    }

    public function delete(int $userId): void
    {
        /** @var User $user */
        $user = User::whereNull('school_id')->findOrFail($userId);

        if ($user->id === Auth::id()) {
            $this->dispatch('notify', type: 'error', message: 'No puedes eliminar tu propia cuenta.');
            return;
        }

        $user->delete();
        $this->dispatch('notify', type: 'success', message: "Usuario \"{$user->name}\" eliminado.");
    }

    public function restore(int $userId): void
    {
        /** @var User $user */
        $user = User::onlyTrashed()->whereNull('school_id')->findOrFail($userId);
        $user->restore();

        $this->dispatch('notify', type: 'success', message: "Usuario \"{$user->name}\" restaurado.");
    }

    // ── Helpers estáticos (usados en la vista) ─────────────

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