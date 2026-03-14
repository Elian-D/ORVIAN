<?php

namespace App\Livewire\Shared;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Mi Perfil')]
class Profile extends Component
{
    use WithFileUploads;

    // Contexto detectado en mount — disponible en la vista
    public bool   $isAdmin  = false;
    public string $activeTab = 'personal';

    // Datos personales
    public string $name     = '';
    public string $phone    = '';
    public string $position = '';
    public string $email    = '';      // solo editable en contexto admin

    // Foto
    public $photo = null;

    // Contraseña
    public string $current_password      = '';
    public string $password              = '';
    public string $password_confirmation = '';

    // Preferencias
    public string $theme = 'system';
    public bool $sidebar_collapsed = false;

    public function mount(): void
    {
        // Detecta si se accede desde /admin/*
        $this->isAdmin = request()->routeIs('admin.profile');

        /** @var User $user */
        $user = Auth::user();

        $this->name     = $user->name;
        $this->phone    = $user->phone    ?? '';
        $this->position = $user->position ?? '';
        $this->email    = $user->email;

        // Cargar preferencias del JSON (usando el helper que creaste en el modelo)
        $this->theme             = $user->preference('theme', 'system');
        $this->sidebar_collapsed = (bool) $user->preference('sidebar_collapsed', false);
    }
    // ── Información personal ───────────────────────────────

    public function savePersonal(): void
    {
        $rules = [
            'name'     => ['required', 'string', 'min:3', 'max:100'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:80'],
        ];

        // Solo admin puede cambiar email
        if ($this->isAdmin) {
            $rules['email'] = ['required', 'email', 'unique:users,email,' . Auth::id()];
        }

        $this->validate($rules);

        /** @var User $user */
        $user = Auth::user();

        $payload = [
            'name'     => $this->name,
            'phone'    => $this->phone    ?: null,
            'position' => $this->position ?: null,
        ];

        if ($this->isAdmin) {
            $payload['email'] = $this->email;
        }

        $user->update($payload);

        $this->dispatch('notify', type: 'success', message: 'Perfil actualizado correctamente.');
    }

    // ── Foto de perfil ─────────────────────────────────────

    public function savePhoto(): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $this->photo->store("avatars/users/{$user->id}", 'public');

        $user->update(['avatar_path' => $path]);

        $this->photo = null;

        $this->dispatch('notify', type: 'success', message: 'Foto actualizada.');
    }

    public function removePhoto(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        $this->dispatch('notify', type: 'success', message: 'Foto eliminada.');
    }

    // ── Contraseña ─────────────────────────────────────────

    public function savePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.min'                      => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.letters'                  => 'Debe contener al menos una letra.',
            'password.numbers'                  => 'Debe contener al menos un número.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->update(['password' => Hash::make($this->password)]);

        $this->current_password      = '';
        $this->password              = '';
        $this->password_confirmation = '';

        $this->dispatch('notify', type: 'success', message: 'Contraseña actualizada correctamente.');
    }

    // ── Preferencias de Interfaz ───────────────────────────

    public function savePreferences(): void
    {
        $this->validate([
            'theme'             => ['required', 'in:light,dark,system'],
            'sidebar_collapsed' => ['boolean'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $preferences = $user->preferences ?? [];
        $preferences['theme']             = $this->theme;
        $preferences['sidebar_collapsed'] = $this->isAdmin
            ? $this->sidebar_collapsed
            : ($user->preference('sidebar_collapsed', false)); // mantiene el valor anterior sin tocarlo

        $user->update(['preferences' => $preferences]);

        $this->dispatch('notify-redirect',
            type:    'success',
            title:   'Preferencias guardadas',
            message: 'El tema se ha aplicado correctamente.',
        );

        $this->redirect(request()->header('Referer') ?? url()->current());
    }

    // ── Render con layout dinámico ─────────────────────────

    /**
     * Render the component.
     *
     */
    public function render()
    {
        $layout = $this->isAdmin ? 'components.admin' : 'layouts.app';

        /** @var \Illuminate\View\View $view */
        $view = view('livewire.shared.profile');

        return $view->layout($layout);
    }
}