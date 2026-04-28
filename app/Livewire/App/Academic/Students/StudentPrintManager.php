<?php

namespace App\Livewire\App\Academic\Students;

use App\Models\Tenant\Student;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

#[Title('Gestión de Carnets')]
class StudentPrintManager extends Component
{
    use WithPagination;

    // Filtros simplificados
    public $search = '';
    public $selectedSection = null;
    public $selectedShift = null;

    // Selección masiva
    public array $selectedStudents = [];
    public bool $selectAll = false;

    // Paginación
    public int $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedSection' => ['except' => null],
        'selectedShift' => ['except' => null],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingSelectedSection() { $this->resetPage(); }
    public function updatingSelectedShift() { $this->resetPage(); }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedStudents = $this->getFilteredStudents()
                ->pluck('id')
                ->map(fn($id) => (int)$id)
                ->toArray();
        } else {
            $this->selectedStudents = [];
        }
    }

    public function toggleStudent(int $studentId)
    {
        if (in_array($studentId, $this->selectedStudents)) {
            $this->selectedStudents = array_diff($this->selectedStudents, [$studentId]);
        } else {
            $this->selectedStudents[] = $studentId;
        }
    }

    public function clearSelection()
    {
        // Reseteamos tanto el array de IDs como el toggle de "Seleccionar Todo"
        $this->selectedStudents = [];
        $this->selectAll = false;
        
        // Opcional: Notificar al usuario
        $this->dispatch('notify', 
            type: 'info', 
            title: 'Selección limpia', 
            message: 'Se han deseleccionado todos los estudiantes.'
        );
    }

    protected function getFilteredStudents()
    {
        $query = Student::withIndexRelations()->active();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('rnc', 'like', "%{$this->search}%")
                    ->orWhere('qr_code', 'like', "%{$this->search}%");
            });
        }

        // Filtro unificado de Sección
        if ($this->selectedSection) {
            $query->inSection($this->selectedSection); 
        }

        if ($this->selectedShift) {
            $query->whereHas('section', function ($q) {
                $q->forShift($this->selectedShift);
            });
        }

        return $query->orderBy('last_name')->orderBy('first_name');
    }

    public function resetFilters()
    {
        $this->reset(['search', 'selectedSection', 'selectedShift',]);
        $this->resetPage();
    }

    public function generatePrintSheet()
    {
        if (empty($this->selectedStudents)) {
            $this->dispatch('notify',
                type: 'warning',
                title: 'Atención',
                message: 'Debes seleccionar al menos un estudiante.',
            );
            return;
        }

        return redirect()->route('app.academic.students.print-qr-sheet', [
            'students' => implode(',', $this->selectedStudents)
        ]);
    }

    /**
     * Obtenemos todas las secciones con sus relaciones para el full_label
     */
    #[Computed]
    public function sections()
    {
        return SchoolSection::withFullRelations()
            ->get()
            ->sortBy(fn($section) => $section->full_label);
    }

    #[Computed]
    public function shifts()
    {
        return SchoolShift::orderBy('start_time')->get();
    }

    public function render()
    {
        $students = $this->getFilteredStudents()->paginate($this->perPage);

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.academic.students.student-print-manager', [
            'students' => $students,
            'totalSelected' => count($this->selectedStudents),
        ]);

        return $view->layout('layouts.app-module', config('modules.academico'));
    }
}