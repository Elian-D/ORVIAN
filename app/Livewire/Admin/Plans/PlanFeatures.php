<?php

namespace App\Livewire\Admin\Plans;

use App\Models\Tenant\Plan;
use App\Models\Tenant\Feature;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

#[Title('Configurar Plan & Features')]
#[Layout('components.admin')]
class PlanFeatures extends Component
{
    public Plan $plan;
    
    // Estado de los toggles: [feature_id => true/false]
    public array $selectedFeatures = [];
    
    public function mount(Plan $plan)
    {
        $this->plan = $plan;

        // Cargamos los IDs actuales
        $currentFeatures = $this->plan->features()->pluck('features.id')->toArray();

        foreach (Feature::all() as $feature) {
            $this->selectedFeatures[$feature->id] = in_array($feature->id, $currentFeatures);
        }
    }

    /**
     * Propiedad computada para la previsualización
     * Esto permite que la Card se actualice en tiempo real en la vista
     */
    public function getPreviewFeaturesProperty()
    {
        $ids = array_keys(array_filter($this->selectedFeatures));
        return Feature::whereIn('id', $ids)->get();
    }

    public function save()
    {
        try {
            $featureIds = array_keys(array_filter($this->selectedFeatures));

            DB::transaction(function () use ($featureIds) {
                $this->plan->features()->sync($featureIds);
            });

            session()->flash('success', "Plan '{$this->plan->name}' actualizado con éxito.");
            return redirect()->route('admin.plans.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al sincronizar: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $groupedFeatures = Feature::where('is_active', true)
            ->get()
            ->groupBy('module');

        return view('livewire.admin.plans.plan-features', [
            'groupedFeatures' => $groupedFeatures,
            'previewFeatures' => $this->previewFeatures // Pasamos las seleccionadas a la card
        ]);
    }
}