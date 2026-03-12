<?php

namespace App\Livewire\Tenant;

use App\Actions\Tenant\CompleteOnboardingAction;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.wizard')]
#[Title('Configuración de Escuela')]
class SchoolWizard extends BaseSchoolWizard
{
    public int $totalSteps = 5;

    // ── Paso 5: Director (Solo Owner) ──────────────────────────────
    public string $principal_name        = '';
    public string $principal_email       = '';
    public string $password              = '';
    public string $password_confirmation = '';

    protected function validateStep(int $step): void
    {
        if ($step === 5) {
            $this->validate([
                'principal_name'  => ['required', 'string', 'max:255'],
                'principal_email' => ['required', 'email', Rule::unique('users', 'email')],
                'password'        => ['required', 'min:8', 'confirmed'],
            ], [
                'principal_email.unique' => 'Este correo ya está registrado en el sistema.',
                'password.confirmed'     => 'Las contraseñas no coinciden.',
            ]);
            return;
        }

        parent::validateStep($step);
    }

    public function finish(CompleteOnboardingAction $action): void
    {
        $this->validateStep(5);

        $action->execute([
            'school'         => $this->schoolPayload(),
            'academic'       => $this->academicPayload(),
            'principal'      => [
                'name'     => $this->principal_name,
                'email'    => $this->principal_email,
                'password' => $this->password,
            ],
            'plan_id'        => $this->plan_id,
            'billing_annual' => $this->billingAnnual,
        ]);

        $this->dispatch('notify-redirect', 
            type: 'success', 
            title: '¡Escuela Creada!', 
            message: '¡' . $this->name . ' ha sido configurada exitosamente! Bienvenido a ORVIAN.'
        );
        
        $this->isProcessing = true;
    }

    public function render()
    {
        return view('livewire.tenant.school-wizard');
    }
}