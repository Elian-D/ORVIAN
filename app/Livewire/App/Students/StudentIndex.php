<?php

namespace App\Livewire\App\Students;

use App\Models\Tenant\Student;
use App\Models\Tenant\Academic\SchoolSection;
use App\Filters\App\Students\StudentFilters;
use App\Tables\App\StudentTableConfig;
use App\Livewire\Base\DataTable;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class StudentIndex extends DataTable
{
    use AuthorizesRequests;

    #[Url]
    public array $filters = [
        'search'            => '',
        'school_section_id'  => '',
        'status'             => '', 
        'gender'             => '',
        'has_photo'          => false,
        'has_face_encoding'  => false,
    ];

    public bool $showQrModal = false;
    public bool $showWithdrawModal = false;
    public bool $showReactivateModal = false;
    
    // NUNCA guardes el objeto Model completo en una propiedad pública si quieres velocidad
    public ?int $selectedStudentId = null; 
    
    public $withdrawal_date;
    public $withdrawal_reason;

    protected function getTableDefinition(): string
    {
        return StudentTableConfig::class;
    }

    /**
     * Propiedad Computada: Solo busca al estudiante cuando se necesita en el modal.
     * No se serializa en el Snapshot de red, lo que hace la tabla 10x más rápida.
     */
    #[Computed]
    public function selectedStudent()
    {
        if (!$this->selectedStudentId) return null;
        return Student::find($this->selectedStudentId);
    }

    // app/Livewire/App/Students/StudentIndex.php

    #[Computed]
    public function studentQuotaStats()
    {
        $school = \App\Models\Tenant\School::with('plan')->find(Auth::user()->school_id);
        
        // Contamos solo estudiantes ACTIVOS que pertenezcan a la escuela
        $used = Student::where('school_id', $school->id)->active()->count();
        $limit = $school?->plan?->limit_students ?? 0; // Usamos limit_students del Plan    
        
        $percentage = $limit > 0 ? ($used / $limit) * 100 : 0;
        
        return [
            'used'        => $used,
            'limit'       => $limit,
            'percentage'  => round($percentage, 1),
            'remaining'   => max(0, $limit - $used),
            'atLimit'     => $limit > 0 && $used >= $limit,
        ];
    }

    // ── Acciones ──────────────────────────────────────────

    public function confirmWithdraw(int $id)
    {
        $this->selectedStudentId = $id;
        $this->withdrawal_date = now()->format('Y-m-d');
        $this->withdrawal_reason = '';
        $this->showWithdrawModal = true;
        $this->dispatch('open-modal', 'withdraw-modal');
    }

    public function withdraw()
    {
        $this->validate([
            'withdrawal_date' => 'required|date',
            'withdrawal_reason' => 'nullable|string|max:1000',
        ]);

        $student = Student::findOrFail($this->selectedStudentId);
        $student->update([
            'is_active' => false,
            'withdrawal_date' => $this->withdrawal_date,
            'withdrawal_reason' => $this->withdrawal_reason
        ]);
        
        $this->dispatch('notify', type: 'info', message: "{$student->full_name} ha sido dado de baja.");
        $this->closeModals();
    }

    public function confirmReactivate(int $id)
    {
        $this->selectedStudentId = $id;
        $this->showReactivateModal = true;
        $this->dispatch('open-modal', 'reactivate-modal');
    }

    public function reactivate()
    {
        // 1. Verificamos si ya alcanzó el límite usando la propiedad computada que ya tienes
        if ($this->studentQuotaStats['atLimit']) {
            $this->dispatch('notify', 
                type: 'error', 
                message: "No se puede reactivar: Se ha alcanzado el límite de {$this->studentQuotaStats['limit']} estudiantes del plan actual."
            );
            $this->closeModals();
            return;
        }

        $student = Student::findOrFail($this->selectedStudentId);
        
        $student->update([
            'is_active' => true,
            'withdrawal_date' => null,
            'withdrawal_reason' => null
        ]);

        $this->dispatch('notify', type: 'success', message: "{$student->full_name} ha sido re-inscrito.");
        $this->closeModals();
    }

    public function showQr(int $id)
    {
        $this->selectedStudentId = $id;
        $this->showQrModal = true;
        $this->dispatch('open-modal', 'qr-modal');
    }

    public function closeModals()
    {
        $this->reset(['showWithdrawModal', 'showReactivateModal', 'showQrModal', 'selectedStudentId', 'withdrawal_date', 'withdrawal_reason']);
        $this->dispatch('close-modal', 'withdraw-modal');
        $this->dispatch('close-modal', 'reactivate-modal');
        $this->dispatch('close-modal', 'qr-modal');
    }

    // ── Render y Pipeline ─────────────────────────────────

    public function render()
    {
        // 1. Consulta principal optimizada
        $students = (new StudentFilters($this->filters))
            ->apply(Student::withIndexRelations())
            ->latest()
            ->paginate($this->perPage);

        // 2. Consulta de secciones OPTIMIZADA (solo id y label, sin withFullRelations)
        // Cargamos las secciones para el filtro superior
        $sections = SchoolSection::withFullRelations()
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->full_label];
            });

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.students.index', [
            'students' => $students,
            'sections' => $sections,
        ]);

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'school_section_id' => SchoolSection::find($value)?->full_label ?? $value,
            'gender' => $value === 'M' ? 'Masculino' : 'Femenino',
            'status' => $value === 'active' ? 'Activos' : 'Inactivos',
            default => parent::formatFilterValue($key, $value),
        };
    }
}