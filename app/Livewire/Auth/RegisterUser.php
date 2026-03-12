<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\Tenant\School;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.install')] // Reutilizamos el layout de instalación
#[Title('Crear Cuenta')]
class RegisterUser extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function register()
    {
        $this->validate();

        // 1. Crear la Escuela Stub (ahora sí pasará el insert)
        $school = School::create([
            'name'            => 'Escuela de ' . $this->name,
            'is_configured'   => false,
            'is_active'       => true,
            'stub_expires_at' => now()->addDay(),
            'plan_id'         => 1, // Asegúrate de que el ID 1 exista en tu tabla plans
        ]);

        // 2. Crear el Usuario vinculado
        $user = User::create([
            'name'      => $this->name,
            'email'     => $this->email,
            'password'  => Hash::make($this->password),
            'school_id' => $school->id,
        ]);

        Auth::login($user);

        session()->flash('success', '¡Bienvenido! Tu cuenta ha sido creada. Comencemos con la configuración.');

        // Redirigir al setup del tenant
        return redirect()->route('wizard');
    }

    public function render()
    {
        return view('livewire.auth.register-user');
    }
}