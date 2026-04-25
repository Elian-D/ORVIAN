<?php

namespace App\Livewire\Shared;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileModal extends Component
{
    use WithFileUploads;

    public string $activeTab = 'personal';

    // Datos personales
    public string $name     = '';
    public string $email    = '';
    public string $phone    = '';
    public string $position = '';

    // Foto
    public $photo = null;

    // Contraseña
    public string $current_password      = '';
    public string $password              = '';
    public string $password_confirmation = '';

    // Preferencias
    public string $theme = 'system';
    public string $roleName = '';
    public string $roleColor = '#64748b'; // Color por defecto (slate-500)
    public string $loginVersion = 'v2';

    protected $listeners = ['open-profile-modal' => 'loadUserData'];

    public function mount(): void
    {
        $this->loadUserData();

        // Si venimos de un refresh que solicita reabrir el modal
        if (session()->has('reopen-profile')) {
            $this->activeTab = session('profile-tab', 'personal');
            $this->dispatch('open-modal', 'profile-modal');
        }
    }

    public function loadUserData(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->name     = $user->name;
        $this->email    = $user->email;
        $this->phone    = $user->phone    ?? '';
        $this->position = $user->position ?? '';
        $this->theme    = $user->preference('theme', 'system');
        $this->loginVersion = $user->preference('login_version', 'v2');
        
        // Obtener el primer rol y su color
        $role = $user->roles->first();
        if ($role) {
            $this->roleName = $role->name;
            $this->roleColor = $role->color ?? '#64748b'; 
        }
        
        $this->photo = null;
        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->resetValidation();
    }

    /**
     * Helper para recargar la página manteniendo el modal abierto
     */
    private function refreshWithModal(string $message): void
    {
        $this->dispatch('notify-redirect', 
            type: 'success', 
            message: $message
        );

        session()->flash('reopen-profile', true);
        session()->flash('profile-tab', $this->activeTab);

        $this->redirect(request()->header('Referer') ?? url()->current(), navigate: false);
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['image', 'max:1024']]);

        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $this->photo->store('avatars', 'public');
        $user->update(['avatar_path' => $path]);

        $this->refreshWithModal('Foto de perfil actualizada.');
    }

    public function removePhoto(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            
            $user->update([
                'avatar_path' => null
            ]);

            $this->refreshWithModal('Foto de perfil eliminada correctamente.');
        }
    }

    public function savePersonal(): void
    {
        $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:100'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'name'     => $this->name,
            'phone'    => $this->phone ?: null,
            'position' => $this->position ?: null,
        ]);

        $this->refreshWithModal('Datos personales actualizados.');
    }

    public function savePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->refreshWithModal('Contraseña actualizada con éxito.');
    }

    public function savePreferences(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $preferences = $user->preferences ?? [];
        
        $preferences['theme'] = $this->theme;
        $preferences['login_version'] = $this->loginVersion; // Nuevo

        $user->update(['preferences' => $preferences]);

        // Sincronizar Cookie para la pre-autenticación (1 año)
        \Illuminate\Support\Facades\Cookie::queue(
            'orvian_login_version', 
            $this->loginVersion, 
            60 * 24 * 365
        );

        $this->refreshWithModal('Preferencias aplicadas. El login cambiará en tu próxima sesión.');
    }

    public function render()
    {
        return view('livewire.shared.profile-modal');
    }
}