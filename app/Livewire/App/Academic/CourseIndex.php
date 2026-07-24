<?php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Academic\AcademicYear;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Student;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Tenant\Academic\SchoolShift;

#[Layout('layouts.app')]
class CourseIndex extends Component
{
    // ── Confirmación de eliminación ────────────────────────────────
    public ?int $selectedShiftId = null; // Propiedad para el filtro
    public ?int  $deletingSectionId   = null;
    public bool  $showDeleteConfirm   = false;

    public function mount()
    {
        // Inicializar con la primera tanda disponible de la escuela
        $this->selectedShiftId = $this->shifts->first()?->id;
    }

    #[Computed]
    public function shifts()
    {
        // Obtener solo las tandas que tienen la escuela actual
        return SchoolShift::where('school_id', Auth::user()->school_id)->get();
    }

    // ── Estructura computada ───────────────────────────────────────
    #[Computed]
    public function structure(): array
    {
        // Traemos TODAS (activas e inactivas) para que el Director
        // pueda ver qué desactivar. Solo excluimos las soft-deleted.   
        $sections = SchoolSection::with([
            'grade.level',
            'shift',
            'technicalTitle.family',
            'students' => fn ($q) => $q->active()->select('id', 'school_section_id'),
        ])
        ->where('school_id', Auth::user()->school_id)
        // FILTRO DINÁMICO:
        ->when($this->selectedShiftId, fn($q) => $q->where('school_shift_id', $this->selectedShiftId))
        ->get();

        return $sections
            ->groupBy(fn ($s) => $s->grade->level->name)
            ->map(fn ($byLevel, $levelName) => [
                'name'   => $levelName,
                'grades' => $byLevel
                    ->groupBy(fn ($s) => $s->grade->id)
                    ->map(fn ($byGrade) => [
                        'id'       => $byGrade->first()->grade->id,
                        'name'     => $byGrade->first()->grade->name,
                        'academic' => $byGrade
                            ->filter(fn ($s) => is_null($s->technical_title_id))
                            ->sortBy('label')
                            ->values(),
                        'technical_groups' => $byGrade
                            ->filter(fn ($s) => ! is_null($s->technical_title_id))
                            ->groupBy(fn ($s) => $s->technicalTitle->name ?? 'Técnico')
                            ->map(fn ($group, $titleName) => [
                                'title'    => $titleName,
                                'family'   => $group->first()->technicalTitle->family->name ?? null,
                                'sections' => $group->sortBy('label')->values(),
                            ])
                            ->values(),
                    ])
                    ->values(),
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function stats(): array
    {
        $sections = SchoolSection::where('school_id', Auth::user()->school_id)->get();
        return [
            'total_active'   => $sections->where('is_active', true)->count(),
            'total_inactive' => $sections->where('is_active', false)->count(),
            'total_students' => Student::where('school_id', Auth::user()->school_id)
                ->where('is_active', true)->count(),
        ];
    }

    // ── Toggle activo / inactivo ───────────────────────────────────
    public function toggleSectionStatus(int $sectionId): void
    {
        $section = SchoolSection::with('students')->findOrFail($sectionId);

        if ($section->is_active && $section->students()->where('is_active', true)->exists()) {
            $this->dispatch('notify', type: 'error',
                message: 'No se puede desactivar: la sección tiene estudiantes activos.');
            return;
        }

        $section->update(['is_active' => ! $section->is_active]);
        unset($this->structure, $this->stats);

        $msg = ! $section->is_active ? 'Sección reactivada.' : 'Sección desactivada.';
        $this->dispatch('notify', type: 'info', message: $msg);
    }

    // ── Eliminar (soft delete — solo secciones vacías del wizard) ──
    public function confirmDelete(int $sectionId): void
    {
        $section = SchoolSection::findOrFail($sectionId);

        if ($section->students()->withTrashed()->exists()) {
            $this->dispatch('notify', type: 'error',
                message: 'Esta sección tiene historial de estudiantes y no puede eliminarse. Desactívala.');
            return;
        }

        $this->deletingSectionId = $sectionId;
        
        // 1. Disparamos el evento para que Alpine.js abra el modal
        $this->dispatch('open-modal', 'delete-section-confirm');
    }

    public function executeDelete(): void
    {
        if (! $this->deletingSectionId) return;

        $section = SchoolSection::findOrFail($this->deletingSectionId);

        if ($section->students()->withTrashed()->exists()) {
            $this->dispatch('notify', type: 'error', message: 'No se puede eliminar.');
            // 2. Cerramos el modal si falla el guard
            $this->dispatch('close-modal', 'delete-section-confirm'); 
            $this->reset(['deletingSectionId']);
            return;
        }

        $section->delete();
        
        // 3. Cerramos el modal tras éxito
        $this->dispatch('close-modal', 'delete-section-confirm');
        $this->reset(['deletingSectionId']);
        unset($this->structure, $this->stats);
        $this->dispatch('notify', type: 'success', message: 'Sección eliminada.');
    }

    public function render()
    {
        return view('livewire.app.academic.course-index');
    }
}