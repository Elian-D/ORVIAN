<?php

namespace App\Livewire\Admin\Plans;

use App\Models\Tenant\Plan;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Title('Planes y Suscripciones')]
#[Layout('components.admin')]
class PlanIndex extends Component
{
    public bool $showPanel = false;
    public bool $isEditing = false;
    public ?int $planId = null;

    public $name, $slug, $const_name, $price;
    public $limit_students = 150;
    public $limit_users = 10;
    public $bg_color = '#F3F4F6';
    public $text_color = '#4B5563';
    public $is_featured = false;
    public $is_active = true;

    

    public function updatedName($value)
    {
        if (!$this->isEditing) {
            $this->slug = Str::slug($value);
            $this->const_name = strtoupper(Str::snake($value));
        }
    }

    // NUEVO: Normalizar el valor del toggle inmediatamente
    public function updatedIsFeatured($value)
    {
        $this->is_featured = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function updatedIsActive($value)
    {
        $this->is_active = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showPanel = true;
    }

    public function edit(Plan $plan)
    {
        $this->resetValidation();
        $this->isEditing = true;
        $this->planId = $plan->id;
        
        $this->name = $plan->name;
        $this->slug = $plan->slug;
        $this->const_name = $plan->const_name;
        $this->price = $plan->price;
        $this->limit_students = $plan->limit_students;
        $this->limit_users = $plan->limit_users;
        $this->bg_color = $plan->bg_color;
        $this->text_color = $plan->text_color;
        
        // IMPORTANTE: Cast explícito a boolean
        $this->is_featured = (bool) $plan->is_featured;
        $this->is_active = (bool) $plan->is_active;
        
        $this->showPanel = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:50',
            'slug' => 'required|unique:plans,slug,' . $this->planId,
            'price' => 'required|numeric|min:0',
            'limit_students' => 'required|integer|min:1',
            'limit_users' => 'required|integer|min:1',
            'bg_color' => 'required|string|hex_color',
            'text_color' => 'required|string|hex_color',
        ]);

        // VALIDACIÓN: No permitir desactivar si es el último activo
        if (!$this->is_active && $this->isEditing) {
            $activeCount = Plan::where('is_active', true)
                ->where('id', '!=', $this->planId)
                ->count();
            
            if ($activeCount === 0) {
                $this->addError('is_active', 'Debe haber al menos un plan activo en el sistema.');
                return;
            }
        }

        try {
            DB::transaction(function () {
                // CAMBIO: Normalizar booleanos explícitamente
                $isFeatured = filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN);
                $isActive = filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN);

                // LÓGICA DE "SOLO UN FEATURED" MOVIDA AQUÍ (fuera del Observer)
                if ($isFeatured) {
                    // Desmarcar cualquier otro plan destacado ANTES de guardar
                    Plan::where('id', '!=', $this->planId)
                        ->where('is_featured', true)
                        ->update(['is_featured' => false]);
                }

                $plan = Plan::updateOrCreate(
                    ['id' => $this->planId],
                    [
                        'name' => $this->name,
                        'slug' => $this->slug,
                        'const_name' => $this->const_name,
                        'price' => $this->price,
                        'limit_students' => $this->limit_students,
                        'limit_users' => $this->limit_users,
                        'bg_color' => $this->bg_color,
                        'text_color' => $this->text_color,
                        'is_featured' => $isFeatured,
                        'is_active' => $isActive,
                    ]
                );

                $message = $this->isEditing ? 'Plan actualizado correctamente.' : 'Plan creado exitosamente.';
                
                $this->dispatch('notify', 
                    type: 'success', 
                    message: $message
                );
                
                $this->showPanel = false;
                $this->resetForm();
            });
        } catch (\Exception $e) {
            Log::error('Error al guardar plan', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => [
                    'is_featured' => $this->is_featured,
                    'is_active' => $this->is_active,
                ]
            ]);
            
            $this->dispatch('notify', 
                type: 'error', 
                message: "Error al guardar: " . $e->getMessage()
            );
        }
    }

    /**
     * ELIMINACIÓN SEGURA
     */
    public function deletePlan(int $id)
    {
        $plan = Plan::withCount('schools')->findOrFail($id);

        if ($plan->schools_count > 0) {
            $this->dispatch('notify', 
                type: 'error', 
                message: "No puedes eliminar un plan que tiene escuelas vinculadas."
            );
            return;
        }

        $plan->delete();
        
        $this->dispatch('notify', 
            type: 'success', 
            message: "Plan eliminado permanentemente."
        );
    }

    private function resetForm()
    {
        $this->reset([
            'name', 
            'slug', 
            'const_name', 
            'price', 
            'planId', 
            'is_featured',
            'is_active'
        ]);
        
        $this->limit_students = 150;
        $this->limit_users = 10;
        $this->bg_color = '#F3F4F6';
        $this->text_color = '#4B5563';
        $this->is_active = true;
        $this->is_featured = false;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.plans.plan-index', [
            'plans' => Plan::withCount('schools')->orderBy('price')->get()
        ]);
    }
}