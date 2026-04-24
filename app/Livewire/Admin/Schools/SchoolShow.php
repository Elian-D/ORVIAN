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
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class SchoolShow extends Component
{
    use WithFileUploads;

    public School $school;
    public $newLogo;

    public string $activeTab = 'general';

    public function mount(School $school)
    {
        $this->school = $school->load([
            'plan', 
            'regional', 
            'educationalDistrict', 
            'municipality',
            'province',
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

    public function updatedNewLogo()
    {
        $this->validate([
            'newLogo' => 'image|max:2048', // 2MB Max
        ]);

        // 1. Borrar logo anterior si existe
        if ($this->school->logo_path) {
            Storage::disk('public')->delete($this->school->logo_path);
        }

        // 2. Guardar el nuevo (Carpeta por ID de escuela)
        $path = $this->newLogo->store("schools/{$this->school->id}/branding", 'public');

        // 3. Actualizar modelo
        $this->school->update(['logo_path' => $path]);

        $this->dispatch('notify', 
            type: 'success', 
            message: "El logo institucional de \"{$this->school->name}\" ha sido actualizado."
        );
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
        // 1. Contamos directamente desde el modelo Student filtrando por activos
        $studentCount = \App\Models\Tenant\Student::where('school_id', $this->school->id)
            ->active() // Usamos el scope que ya tienes definido
            ->count();

        $limit = $this->school->plan->limit_students ?? 0;
        
        // 2. Calculamos el porcentaje
        $percentage = $limit > 0 ? ($studentCount / $limit) * 100 : 0;

        return [
            'used'       => $studentCount,
            'limit'      => $limit,
            'percentage' => round(min($percentage, 100)),
            'remaining'  => max($limit - $studentCount, 0),
            'atLimit'    => $limit > 0 && $studentCount >= $limit, // Útil para deshabilitar botones
        ];
    }

    #[Computed]
    public function staffCount(): int
    {
        return User::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->school->id)
            // 1. Mantenemos la exclusión de estudiantes sin modificar
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('roles.name', 'Student')
                    ->where('model_has_roles.school_id', $this->school->id);
            })
            // 2. Nueva lógica: Si es Teacher, la entidad Teacher DEBE estar activa.
            // Los usuarios que no son Teachers (Administradores, etc.) se cuentan normal.
            ->where(function ($query) {
                $query->whereDoesntHave('teacher') // Si no tiene relación teacher, es administrativo y cuenta.
                    ->orWhereHas('teacher', function ($q) {
                        $q->where('is_active', true); // Si tiene relación, debe estar activo.
                    });
            })
            ->count();
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

      /**
       * Estructura académica agrupada por Tanda > Nivel > Ciclo > Familia > Grado
       */
    #[Computed]
    public function academicStructure(): array
    {
        // 1. Obtener todas las secciones con relaciones completas
        $sections = SchoolSection::withoutGlobalScope(SchoolScope::class)
            ->with([
                'shift:id,type,start_time,end_time',
                'grade:id,name,level_id,cycle,allows_technical',
                'grade.level:id,name',
                'technicalTitle:id,name,code,technical_family_id',
                'technicalTitle.family:id,name',
            ])
            ->where('school_id', $this->school->id)
            ->get();

        if ($sections->isEmpty()) {
            return [];
        }

        $structure = [];

        // 2. Agrupar primero por Tanda
        $sectionsByShift = $sections->groupBy('school_shift_id');

        foreach ($sectionsByShift as $shiftId => $shiftSections) {
            $shift = $shiftSections->first()->shift;
            $shiftType = $shift->type;

            $structure[$shiftType] = [
                'shift' => $shift,
                'sections_count' => $shiftSections->count(),
                'levels' => [],
            ];

            // 3. Dentro de cada tanda, agrupar por Nivel (Primario/Secundario)
            foreach ($shiftSections as $section) {
                $grade = $section->grade;
                $level = $grade->level;
                $nivel = $level->name; // "Nivel Inicial", "Nivel Primario", etc.
                $ciclo = $grade->cycle;

                // Inicializar estructura si no existe
                if (!isset($structure[$shiftType]['levels'][$nivel])) {
                    $structure[$shiftType]['levels'][$nivel] = [];
                }

                if (!isset($structure[$shiftType]['levels'][$nivel][$ciclo])) {
                    $structure[$shiftType]['levels'][$nivel][$ciclo] = [];
                }

                // 4. Lógica de agrupación por Familia Técnica (solo Segundo Ciclo Secundaria)
                if ($grade->allows_technical && $section->technical_title_id) {
                    $familyName = $section->technicalTitle->family->name;
                    
                    if (!isset($structure[$shiftType]['levels'][$nivel][$ciclo][$familyName])) {
                        $structure[$shiftType]['levels'][$nivel][$ciclo][$familyName] = [];
                    }

                    // Buscar o crear entrada para este grado específico
                    $gradeKey = $grade->id . '-' . $section->technicalTitle->id;
                    
                    if (!isset($structure[$shiftType]['levels'][$nivel][$ciclo][$familyName][$gradeKey])) {
                        $structure[$shiftType]['levels'][$nivel][$ciclo][$familyName][$gradeKey] = [
                            'title' => explode(' ', $grade->name)[0], // "Cuarto", "Quinto", etc.
                            'subtitle' => mb_strtoupper($section->technicalTitle->name),
                            'is_technical' => true,
                            'family_id' => $section->technicalTitle->technical_family_id,
                            'sections' => collect([]),
                        ];
                    }

                    $structure[$shiftType]['levels'][$nivel][$ciclo][$familyName][$gradeKey]['sections']->push($section);
                } else {
                    // 5. Grados estándar (sin título técnico)
                    if (!isset($structure[$shiftType]['levels'][$nivel][$ciclo]['General'])) {
                        $structure[$shiftType]['levels'][$nivel][$ciclo]['General'] = [];
                    }

                    $gradeKey = $grade->id;

                    if (!isset($structure[$shiftType]['levels'][$nivel][$ciclo]['General'][$gradeKey])) {
                        $structure[$shiftType]['levels'][$nivel][$ciclo]['General'][$gradeKey] = [
                            'title' => explode(' ', $grade->name)[0],
                            'subtitle' => mb_strtoupper(explode(' ', $grade->name)[1] ?? $nivel),
                            'is_technical' => false,
                            'sections' => collect([]),
                        ];
                    }

                    $structure[$shiftType]['levels'][$nivel][$ciclo]['General'][$gradeKey]['sections']->push($section);
                }
            }

            // 6. Ordenar secciones dentro de cada grado
            foreach ($structure[$shiftType]['levels'] as $nivel => $ciclos) {
                foreach ($ciclos as $ciclo => $familias) {
                    foreach ($familias as $familia => $grados) {
                        foreach ($grados as $gradeKey => $grado) {
                            $structure[$shiftType]['levels'][$nivel][$ciclo][$familia][$gradeKey]['sections'] = 
                                $grado['sections']->sortBy('label')->values();
                        }
                    }
                }
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