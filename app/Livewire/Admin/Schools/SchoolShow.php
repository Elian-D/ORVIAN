<?php

namespace App\Livewire\Admin\Schools;

use App\Models\Tenant\School;
use App\Models\User;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Scopes\SchoolScope;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use App\Models\Tenant\Academic\Grade;
use App\Models\Tenant\Academic\AcademicYear;

class SchoolShow extends Component
{
    public School $school;

    public string $activeTab = 'general';

    public function mount(School $school)
    {
        $this->school = $school->load([
            'plan', 
            'regional', 
            'educationalDistrict', 
            'municipality',
            'principal'
        ]);
    }

    // ── Acciones de Estado (Copiadas de SchoolIndex) ─────────────

    public function toggleSuspension(): void
    {
        if (!$this->school->is_active) {
            $this->dispatch('notify', 
                type: 'error', 
                message: "Acción denegada. Debe reactivar el centro antes de gestionar su estado de pago."
            );
            return;
        }

        $this->school->update([
            'is_suspended' => !$this->school->is_suspended
        ]);

        $status = $this->school->is_suspended ? 'suspendido por pagos' : 'restaurado (pagos al día)';
        $this->dispatch('notify', type: $this->school->is_suspended ? 'warning' : 'success', message: "El servicio para \"{$this->school->name}\" ha sido {$status}.");
    }

    public function toggleActiveStatus(): void
    {
        // Regla: Para desactivar, debe estar suspendida previamente.
        if ($this->school->is_active && !$this->school->is_suspended) {
            $this->dispatch('notify', 
                type: 'error', 
                message: "Acción denegada. El centro debe estar suspendido antes de poder inactivarlo."
            );
            return;
        }

        $this->school->update([
            'is_active' => !$this->school->is_active
        ]);

        $status = $this->school->is_active ? 'reactivado' : 'desactivado e inactivado';
        $this->dispatch('notify', type: $this->school->is_active ? 'success' : 'info', message: "El centro \"{$this->school->name}\" ha sido {$status}.");
    }


    public function saveLocation($lat = null, $lng = null): void
    {
        // Si se reciben parámetros, se asignan al modelo
        if ($lat !== null && $lng !== null) {
            $this->school->latitude = $lat;
            $this->school->longitude = $lng;
        }

        // Guardar los cambios en la base de datos
        $this->school->save();

        $this->dispatch('notify', 
            type: 'success', 
            message: "La ubicación de \"{$this->school->name}\" ha sido actualizada correctamente."
        );
    }

    // ── Métricas Computadas ─────────────────────────────────

    #[Computed]
    public function quotaStats(): array
    {
        $studentCount = User::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->school->id)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('roles.name', 'Student')
                    ->where('model_has_roles.school_id', $this->school->id);
            })->count();

        $limit = $this->school->plan->limit_students ?? 0;
        $percentage = $limit > 0 ? min(($studentCount / $limit) * 100, 100) : 0;

        return [
            'used' => $studentCount,
            'limit' => $limit,
            'percentage' => round($percentage),
            'remaining' => max($limit - $studentCount, 0),
        ];
    }

    #[Computed]
    public function staffCount(): int
    {
        return User::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->school->id)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('roles.name', 'Student')
                    ->where('model_has_roles.school_id', $this->school->id);
            })->count();
    }

    #[Computed]
    public function staffQuotaStats(): array
    {
        $staffCount = $this->staffCount;
        $limit = $this->school->plan->limit_users ?? 0;
        $percentage = $limit > 0 ? min(($staffCount / $limit) * 100, 100) : 0;

        $color = match(true) {
            $percentage >= 100 => 'text-state-error',
            $percentage >= 85  => 'text-state-warning',
            default            => 'text-state-success',
        };

        return [
            'used' => $staffCount,
            'limit' => $limit,
            'percentage' => round($percentage),
            'remaining' => max($limit - $staffCount, 0),
            'status_color' => $color,
        ];
    }

    #[Computed]
    public function sectionsCount(): int
    {
        return SchoolSection::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->school->id)
            ->count();
    }

    #[Computed]
    public function currentAcademicYear()
    {
        return AcademicYear::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->school->id)
            ->first();
    }

    // ── Estructura Académica (Cursos y Grados) ───────────────────

    #[Computed]
    public function academicStructure(): array
    {
        $levelIds = DB::table('school_levels')
            ->where('school_id', $this->school->id)
            ->pluck('level_id');

        $grades = Grade::whereIn('level_id', $levelIds)
            ->orderBy('level_id')
            ->orderBy('order')
            ->get();

        $sections = SchoolSection::withoutGlobalScope(SchoolScope::class)
            ->with(['technicalTitle.family', 'grade'])
            ->where('school_id', $this->school->id)
            ->get();

        $structure = [];

        foreach ($grades as $grade) {
            $nivel = str_contains(strtolower($grade->name), 'primaria') ? 'Primario' : 'Secundario';
            $ciclo = $grade->cycle;

            if (!isset($structure[$nivel])) $structure[$nivel] = [];
            if (!isset($structure[$nivel][$ciclo])) $structure[$nivel][$ciclo] = [];

            $gradeSections = $sections->where('grade_id', $grade->id);

            if ($nivel === 'Secundario' && $ciclo === 'Segundo Ciclo') {
                // Agrupamos por Familia Técnica para el Segundo Ciclo de Secundaria
                $sectionsByFamily = $gradeSections->groupBy(function($section) {
                    return $section->technicalTitle->technical_family_id ?? 'COMUN';
                });

                foreach ($sectionsByFamily as $familyId => $items) {
                    $familyName = $items->first()->technicalTitle->family->name ?? 'MODALIDAD ACADÉMICA';
                    
                    if (!isset($structure[$nivel][$ciclo][$familyName])) {
                        $structure[$nivel][$ciclo][$familyName] = [];
                    }

                    $structure[$nivel][$ciclo][$familyName][] = [
                        'title' => explode(' ', $grade->name)[0],
                        'subtitle' => mb_strtoupper($items->first()->technicalTitle->name ?? 'General'),
                        'is_technical' => $familyId !== 'COMUN',
                        'family_id' => $familyId,
                        'sections' => $items->sortBy('label')->values()->all()
                    ];
                }
            } else {
                // Estructura normal para Primaria y Primer Ciclo de Secundaria
                if (!isset($structure[$nivel][$ciclo]['General'])) {
                    $structure[$nivel][$ciclo]['General'] = [];
                }

                $structure[$nivel][$ciclo]['General'][] = [
                    'title' => explode(' ', $grade->name)[0],
                    'subtitle' => mb_strtoupper(explode(' ', $grade->name)[1] ?? $nivel),
                    'is_technical' => false,
                    'sections' => $gradeSections->sortBy('label')->values()->all()
                ];
            }
        }

        return $structure;
    }

    #[Layout('components.admin')]
    public function render()
    {
        return view('livewire.admin.schools.school-show');
    }
}