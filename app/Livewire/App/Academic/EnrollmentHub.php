<?php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Student;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class EnrollmentHub extends Component
{
    use WithPagination;

    // Panel izquierdo — filtros de la Sala de Espera
    public string $searchUnassigned    = '';
    public string $filterSigerdSection = '';  // Filtrar por sigerd_section del metadata

    // Selección de estudiantes
    public array $selectedStudentIds = [];
    public bool  $selectAll          = false;

    // Panel derecho — destino de la asignación
    public ?int $targetSectionId = null;
    public ?int $targetShiftId   = null;   // Para filtrar secciones en el árbol derecho

    // (Modal controlado via dispatch open-modal/close-modal — sin estado Livewire)

    #[Computed]
    public function unassignedStudents(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Student::query()
            ->whereNull('school_section_id')
            ->where('is_active', true)
            ->when($this->searchUnassigned, fn ($q) =>
                $q->where(fn ($sq) =>
                    $sq->where('first_name', 'like', "%{$this->searchUnassigned}%")
                       ->orWhere('last_name',  'like', "%{$this->searchUnassigned}%")
                       ->orWhere('rnc', 'like', "%{$this->searchUnassigned}%")
                )
            )
            ->when($this->filterSigerdSection, fn ($q) =>
                $q->whereJsonContains('metadata->sigerd_section', $this->filterSigerdSection)
            )
            ->orderBy('last_name')
            ->paginate();
    }

    /**
     * Grupos únicos de sigerd_section para el filtro rápido del panel izquierdo.
     * Permite filtrar "todos los que venían del curso 4TO A en SIGERD".
     */
    #[Computed]
    public function sigerdSectionGroups(): Collection
    {
        return Student::query()
            ->whereNull('school_section_id')
            ->where('is_active', true)
            ->whereNotNull('metadata->sigerd_section')
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.sigerd_section')) as sigerd_section, COUNT(*) as total")
            ->groupBy('sigerd_section')
            ->orderBy('total', 'desc')
            ->get();
    }

    #[Computed]
    public function sectionTree(): Collection
    {
        return SchoolSection::with(['grade.level', 'shift', 'technicalTitle'])
            ->withCount('students')
            ->where('school_id', Auth::user()->school_id)
            ->where('is_active', true)
            ->when($this->targetShiftId, fn ($q) => 
                $q->where('school_shift_id', $this->targetShiftId)
            )
            ->get()
            ->groupBy(fn ($s) => $s->grade->level->name);
    }

    #[Computed]
    public function shifts(): Collection
    {
        return SchoolShift::where('school_id', Auth::user()->school_id)->get();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selectedStudentIds = $value
            ? Student::whereNull('school_section_id')
                ->where('is_active', true)
                ->pluck('id')
                ->toArray(30)
            : [];
    }

    #[Computed]
    public function targetSection()
    {
        if (! $this->targetSectionId) return null;
        return SchoolSection::find($this->targetSectionId);
    }

    public function toggleStudent(int $id): void
    {
        if (in_array($id, $this->selectedStudentIds)) {
            $this->selectedStudentIds = array_values(
                array_filter($this->selectedStudentIds, fn ($i) => $i !== $id)
            );
        } else {
            $this->selectedStudentIds[] = $id;
        }
        $this->selectAll = false;
    }

    public function selectBySigerdSection(string $sigerdSection): void
    {
        $ids = Student::whereNull('school_section_id')
            ->where('is_active', true)
            ->whereJsonContains('metadata->sigerd_section', $sigerdSection)
            ->pluck('id')
            ->toArray();

        $this->selectedStudentIds = array_unique(
            array_merge($this->selectedStudentIds, $ids)
        );
    }

    public function confirmAssign(): void
    {
        if (empty($this->selectedStudentIds)) {
            $this->dispatch('notify', type: 'warning', message: 'Selecciona al menos un estudiante.');
            return;
        }

        if (! $this->targetSectionId) {
            $this->dispatch('notify', type: 'warning', message: 'Selecciona la sección de destino.');
            return;
        }

        $this->dispatch('open-modal', 'confirm-assign');
    }

    public function executeAssignment(): void
    {
        $this->authorize('students.edit');

        $section = SchoolSection::findOrFail($this->targetSectionId);
        $count   = count($this->selectedStudentIds);

        // Asignación masiva en una sola query para performance
        Student::whereIn('id', $this->selectedStudentIds)
            ->whereNull('school_section_id')  // Guard: solo mover los que están en Sala de Espera
            ->update([
                'school_section_id' => $this->targetSectionId,
                // Limpiar sigerd_section del metadata tras asignación exitosa
                // usando JSON_REMOVE para no perder otros campos del metadata
                'metadata' => DB::raw(
                    "JSON_SET(metadata, '$.assigned_from_waiting_room', true, " .
                    "'$.assigned_at', NOW(), " .
                    "'$.assigned_section_id', {$this->targetSectionId})"
                ),
            ]);

        $this->dispatch('close-modal', 'confirm-assign');
        $this->reset(['selectedStudentIds', 'selectAll', 'targetSectionId']);
        unset($this->unassignedStudents, $this->sigerdSectionGroups);

        $this->dispatch('notify', type: 'success',
            message: "{$count} estudiante(s) asignados a {$section->full_label}.");
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.academic.enrollment-hub');

        return $view->layout('layouts.app-module', config('modules.academico'));
    }
}