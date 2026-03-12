<?php

namespace App\Livewire\Tenant;

use App\Actions\Tenant\CompleteTenantOnboardingAction;
use App\Models\Tenant\School; // <--- IMPORTANTE
use Illuminate\Support\Facades\Auth; // <--- IMPORTANTE
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.wizard')]
#[Title('Configura tu Escuela')]
class TenantSetupWizard extends BaseSchoolWizard
{
    public int $totalSteps = 4;
    public int $schoolId;

    public function mount()
    {
        $user   = Auth::user();
        $school = $user->school;

        if (!$school || $school->is_configured) {
            return $this->redirect(route('app.dashboard'), navigate: false);
        }

        $this->schoolId    = $school->id;
        $this->name        = $school->name ?? '';
        $this->sigerd_code = $school->sigerd_code ?? '';
        $this->modalidad   = $school->modalidad ?? School::MODALITY_ACADEMIC;
        $this->needsTitles = $this->modalityNeedsTechnical(); // ← AGREGAR
    }

    protected function sigerdUniqueRule()
    {
        return Rule::unique('schools', 'sigerd_code')->ignore($this->schoolId);
    }

    public function finish(CompleteTenantOnboardingAction $action): void
    {
        $this->validateStep(4);

        $action->execute(
            $this->schoolId,
            [
                'school'         => $this->schoolPayload(),
                'academic'       => $this->academicPayload(),
                'plan_id'        => $this->plan_id,
                'billing_annual' => $this->billingAnnual,
            ],
            Auth::user() // Cambiado aquí también
        );

        $this->dispatch('notify-redirect', 
            type: 'success', 
            title: '¡Éxito!', 
            message: '¡Tu escuela ' . $this->name . ' ha sido configurada exitosamente!'
        );
        
        $this->isProcessing = true;
    }

    public function render()
    {
        return view('livewire.tenant.school-wizard');
    }
}