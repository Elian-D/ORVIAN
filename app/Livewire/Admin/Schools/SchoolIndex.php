<?php

namespace App\Livewire\Admin\Schools;

use App\Filters\Admin\Schools\AdminSchoolFilters;
use App\Livewire\Base\DataTable;
use App\Models\Tenant\School;
use App\Models\Tenant\Plan;
use App\Tables\Admin\SchoolTableConfig;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Gestión de Centros Educativos')]
#[Layout('components.admin')]
class SchoolIndex extends DataTable
{
    // ── Filtros ────────────────────────────────────────────

    #[Url]
    public array $filters = [
        'search' => '',
        'plan'   => '',
        'status' => '', // '' -> todos | 'active' -> operativos | 'suspended' -> morosos | 'inactive' -> archivados
        'suspended' => '',
        'regional'  => '', // Nueva clave
        'district'  => '', // Nueva clave
    ];

    // ── Estado para Modales ────────────────────────────────

    public ?int $selectedSchoolId = null;
    public ?int $newPlanId = null;

    // ── DataTable Contract ─────────────────────────────────

    protected function getTableDefinition(): string
    {
        return SchoolTableConfig::class;
    }

    
    /**
     * Hook de actualización para manejar la dependencia dinámica
     */
    public function updatedFiltersRegional($value)
    {
        // Si cambia la regional, reseteamos el distrito seleccionado
        $this->filters['district'] = '';
        $this->resetPage();
    }

    /**
     * Propiedad computada para obtener los distritos según la regional elegida
     */
    public function getDistrictsProperty()
    {
        if (empty($this->filters['regional'])) {
            return [];
        }

        return \App\Models\Geo\EducationalDistrict::where('regional_education_id', $this->filters['regional'])
            ->pluck('name', 'id')
            ->toArray();
    }

    // ── Render ─────────────────────────────────────────────

    public function render()
    {
        $query = School::query()
            ->with([
                'plan:id,name,text_color',
                'principal' => function($q) {
                    $q->select('id', 'school_id', 'name', 'email');
                }
            ])
            // 1. Conteo de Estudiantes (Manual para evitar el Scope de Spatie)
            ->withCount(['users as students_count' => function($q) {
                $q->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->whereColumn('model_has_roles.model_id', 'users.id')
                        ->where('roles.name', 'Student')
                        // Importante: que el rol pertenezca a la misma escuela que el usuario
                        ->whereColumn('model_has_roles.school_id', 'users.school_id');
                });
            }])
            // 2. Conteo de Staff (Cualquier usuario que NO tenga el rol Student)
            ->withCount(['users as staff_count' => function($q) {
                $q->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->whereColumn('model_has_roles.model_id', 'users.id')
                        ->where('roles.name', 'Student')
                        ->whereColumn('model_has_roles.school_id', 'users.school_id');
                });
            }])
            ->withMax('users as last_activity', 'last_login_at');


        $schools = (new \App\Filters\Admin\Schools\AdminSchoolFilters($this->filters))
            ->apply($query)
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.schools.index', [
            'schools' => $schools,
            'plans'   => \App\Models\Tenant\Plan::orderBy('price')->where('is_active', true)->get(['id', 'name', 'price', 'text_color']),
            'regionals' => \App\Models\Geo\RegionalEducation::pluck('name', 'id')->toArray(),
            'districts' => $this->districts,
        ]);
    }

    /**
     * Traduce los valores crudos de los filtros a texto legible para los Chips.
     */
    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            // Buscamos el nombre de la Regional Educativa
            'regional' => \App\Models\Geo\RegionalEducation::find($value)?->name ?? $value,
            
            // Buscamos el nombre del Distrito Educativo
            'district' => \App\Models\Geo\EducationalDistrict::find($value)?->name ?? $value,
            
            // Buscamos el nombre del Plan
            'plan'     => \App\Models\Tenant\Plan::find($value)?->name ?? $value,
            
            // Textos estáticos para el estado
            'status'   => $value === '1' ? 'Activos / Habilitados' : 'Inactivos / Deshabilitados',
            
            // Comportamiento por defecto para cualquier otro filtro (ej. search, suspended)
            default    => parent::formatFilterValue($key, $value),
        };
    }

    // ── Acciones Rápidas (Estado y Suspensión) ─────────────

    /**
     * Alterna el estado de suspensión por pagos (is_suspended).
     */
    public function toggleSuspension(int $id): void
    {
        $school = School::findOrFail($id);

        if (!$school->is_active) {
            $this->dispatch('notify', 
                type: 'error', 
                message: "Acción denegada. Debe reactivar el centro antes de gestionar su estado de pago."
            );
            return;
        }

        $school->update([
            'is_suspended' => !$school->is_suspended
        ]);

        $status = $school->is_suspended ? 'suspendido por pagos' : 'restaurado (pagos al día)';
        $type = $school->is_suspended ? 'warning' : 'success';

        $this->dispatch('notify', 
            type: $type, 
            message: "El servicio para \"{$school->name}\" ha sido {$status}."
        );
    }

    /**
     * Alterna el estado de actividad en el sistema (is_active).
     */
    public function toggleActiveStatus(int $id): void
    {
        $school = School::findOrFail($id);

        // Regla de Negocio: Para desactivar, debe estar suspendida previamente.
        if ($school->is_active && !$school->is_suspended) {
            $this->dispatch('notify', 
                type: 'error', 
                message: "Acción denegada. El centro debe estar suspendido antes de poder inactivarlo."
            );
            return;
        }

        $school->update([
            'is_active' => !$school->is_active
        ]);

        $status = $school->is_active ? 'reactivado' : 'desactivado e inactivado';
        $type = $school->is_active ? 'success' : 'info'; // Slate/Info para representar inactividad profunda

        $this->dispatch('notify', 
            type: $type, 
            message: "El centro \"{$school->name}\" ha sido {$status}."
        );
    }

    // ── Gestión de Planes ──────────────────────────────────

    public function confirmPlanChange(int $id): void
    {
        $school = School::findOrFail($id);
        $this->selectedSchoolId = $school->id;
        $this->newPlanId = $school->plan_id;

        $this->dispatch('open-modal', 'change-plan-modal');
    }

    public function updatePlan(): void
    {
        $this->validate([
            'newPlanId' => ['required', 'exists:plans,id']
        ]);

        $school = School::findOrFail($this->selectedSchoolId);
        $oldPlanName = $school->plan->name;
        
        $school->update([
            'plan_id' => $this->newPlanId
        ]);

        $newPlan = Plan::find($this->newPlanId);

        $this->dispatch('close-modal', 'change-plan-modal');
        $this->dispatch('notify', 
            type: 'success', 
            message: "Plan de \"{$school->name}\" actualizado: {$oldPlanName} -> {$newPlan->name}."
        );

        $this->reset(['selectedSchoolId', 'newPlanId']);
    }

    public function resetFilters(): void
    {
        $this->reset('filters');
        $this->resetPage();
    }


    /**
     * Calcula las estadísticas globales o filtradas para las cabeceras.
     */
    public function getStatsProperty(): array
    {
        // 1. Clonamos la consulta base con los filtros aplicados
        $baseQuery = School::query();
        (new \App\Filters\Admin\Schools\AdminSchoolFilters($this->filters))->apply($baseQuery);

        // 2. Cálculo de Centros Totales
        $totalSchools = (clone $baseQuery)->count();

        // 3. Cálculo de Usuarios Totales
        $totalUsers = \App\Models\User::whereIn('school_id', (clone $baseQuery)->select('id'))->count();

        // 4. Cálculo de MRR (Resolviendo ambigüedad con el prefijo 'schools.')
        $mrr = (clone $baseQuery)
            ->where('schools.is_active', true)      // <--- Prefijo añadido
            ->where('schools.is_suspended', false) // <--- Prefijo añadido
            ->join('plans', 'schools.plan_id', '=', 'plans.id')
            ->sum('plans.price');

        return [
            'total_schools' => number_format($totalSchools),
            'total_users'   => $this->formatLargeNumber($totalUsers),
            'mrr'           => 'RD$ ' . number_format($mrr, 0),
        ];
    }

    /**
     * Helper para formatear números grandes (ej: 18500 -> 18.5k)
     */
    private function formatLargeNumber(int $number): string
    {
        if ($number >= 1000) {
            return number_format($number / 1000, 1) . 'k';
        }
        return (string) $number;
    }
}