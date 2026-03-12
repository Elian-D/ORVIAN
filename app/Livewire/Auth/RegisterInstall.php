<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.install')]

class RegisterInstall extends Component
{
    public int $step = 1;

    // Paso 2
    public string $name  = '';
    public string $email = '';

    // Paso 3
    public string $password              = '';
    public string $password_confirmation = '';

    // ── Navegación ──────────────────────────────────────────
    public function goNext(): void
    {
        if ($this->step === 2) {
            $this->validateStep2();
        }

        $this->step++;
    }

    public function goPrev(): void
    {
        $this->step--;
    }

    // ── Validación parcial paso 2 ────────────────────────────
    protected function validateStep2(): void
    {
        $this->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
        ], [
            'name.required'  => 'El nombre es obligatorio.',
            'email.required' => 'El email es obligatorio.',
            'email.email'    => 'Ingresa un email válido.',
            'email.unique'   => 'Este email ya está en uso.',
        ]);
    }

    // ── Submit final ─────────────────────────────────────────
    public function register(): void
    {
        $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.required'  => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $isFirstUser = User::count() === 0;

        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
        ]);

        if ($isFirstUser) {
            setPermissionsTeamId(null);
            $user->assignRole('Owner');
        }

        event(new Registered($user));
        Auth::login($user);

        session()->flash('success', 'Tu cuenta de Owner ha sido creada con éxito. Bienvenido a ORVIAN.');

        $this->redirect(
            $user->redirectPath(),
            navigate: false
        );

        
    }

    public function render()
    {
        return view('livewire.auth.register-install');
    }
}