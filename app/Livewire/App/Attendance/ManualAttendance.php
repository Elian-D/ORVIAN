<?php

namespace App\Livewire\App\Attendance;

use App\Filters\App\Attendance\Manual\ManualAttendanceFilters;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\Student;
use App\Services\Attendance\PlantelAttendanceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Services\Attendance\ExcuseService;

#[Title('Registro Manual de Asistencia')]
class ManualAttendance extends Component
{
    use WithPagination;

    // ── Filtros ────────────────────────────────────────────────────
    
    #[Url]
    public array $filters = [
        'search' => '',
        'section' => '',
        'status_filter' => '', // 'all', 'pending', 'registered'
        'hide_excused' => true, // Lo ponemos en true por defecto según tu preferencia
    ];

    // ── Estado de Tanda ────────────────────────────────────────────
    #[Url(as: 'shift')] // Esto permite que persista en la URL
    public int $selectedShiftId = 0;
    public ?DailyAttendanceSession $activeSession = null;

    // ── Configuración de Tabla ─────────────────────────────────────
    
    public array $visibleColumns = [];
    public int $perPage = 15;
    

    // ── Lifecycle ──────────────────────────────────────────────────

    public function mount(): void
    {
        // Si no viene por URL, tomamos la primera disponible
        if ($this->selectedShiftId === 0) {
            $firstShift = SchoolShift::where('school_id', Auth::user()->school_id)->first();
            if ($firstShift) {
                $this->selectedShiftId = $firstShift->id;
            }
        }
        
        $this->loadActiveSession();
    }

    // ── Watchers ───────────────────────────────────────────────────

    public function updatedSelectedShiftId($value): void
    {
        $this->selectedShiftId = (int) $value; // Forzar a entero
        $this->filters['section'] = '';
        $this->filters['status_filter'] = '';
        $this->resetPage();
        $this->loadActiveSession();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    // ── Helpers ────────────────────────────────────────────────────

    protected function loadActiveSession(): void
    {
        if (empty($this->selectedShiftId) || $this->selectedShiftId == 0) {
            $this->activeSession = null;
            return;
        }

        $this->activeSession = DailyAttendanceSession::where('school_id', Auth::user()->school_id)
            ->whereDate('date', today())
            ->where('school_shift_id', (int)$this->selectedShiftId) // Cast explícito
            ->whereNull('closed_at')
            ->first();
    }

    // ── Computed Properties ────────────────────────────────────────

    #[Computed]
    public function shifts()
    {
        return SchoolShift::where('school_id', Auth::user()->school_id)
            ->withSectionCount()
            ->get();
    }

    #[Computed]
    public function sections()
    {
        if (!$this->selectedShiftId) return collect();

        return SchoolSection::where('school_id', Auth::user()->school_id)
            ->where('school_shift_id', $this->selectedShiftId)
            ->with(['grade:id,name', 'technicalTitle:id,name']) // Quitamos 'shift' del with
            ->get()
            ->map(function($section) {
                // Inyectamos la tanda que ya tenemos en memoria
                $section->setRelation('shift', $this->currentShift);
                return $section;
            })
            ->sortBy(fn($s) => $s->grade->name . $s->label);
    }

    #[Computed]
    public function students()
    {
        if (!$this->selectedShiftId) {
            return collect();
        }

        // 1. Query base y aplicación de filtros
        $query = Student::query()
            ->where('school_id', Auth::user()->school_id)
            ->where('is_active', true)
            ->inShift($this->selectedShiftId);

        // --- FILTRO DE EXCUSAS ---
        $excuseService = app(ExcuseService::class);
        $coveredStudentIds = $excuseService->getCoveredStudentsForDate(today());

        if ($this->filters['hide_excused']) {
            // Excluimos de la base de datos a los que tienen excusa hoy
            $query->whereNotIn('id', $coveredStudentIds);
        }

        $searchFilters = ['search' => $this->filters['search'], 'section' => $this->filters['section']];
        $query = (new ManualAttendanceFilters($searchFilters))->apply($query);

        // 2. Cargar relaciones necesarias
        $query->with([
            'section' => function($q) {
                $q->with(['grade:id,name', 'technicalTitle:id,name', 'shift:id,type']);
            }
        ]);

        // 3. Obtener estudiantes paginados
        $students = $query->orderBy('last_name')->orderBy('first_name')->paginate($this->perPage);
        $studentIds = $students->pluck('id');

        // --- NUEVA LÓGICA DE EXCUSAS ---
        // Obtenemos todos los IDs de estudiantes con excusa para hoy en una sola consulta
        $excuseService = app(ExcuseService::class);
        $coveredStudentIds = $excuseService->getCoveredStudentsForDate(today());
        // -------------------------------

        // 4. Enriquecer con datos de asistencia si hay sesión activa
        $records = collect();
        if ($this->activeSession) {
            $records = PlantelAttendanceRecord::where('daily_attendance_session_id', $this->activeSession->id)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');
        }

        // 5. Transformar la colección para inyectar estados dinámicos
        $students->getCollection()->transform(function ($student) use ($records, $coveredStudentIds) {
            $record = $records->get($student->id);
            
            $student->attendance_status = $record?->status;
            $student->attendance_time = $record?->time?->format('H:i');
            $student->attendance_method = $record?->method;
            
            // Marcamos si tiene excusa comparando el ID contra nuestra lista de cubiertos
            $student->has_excuse = $coveredStudentIds->contains($student->id);
            
            return $student;
        });

        // 6. Aplicar filtro de estado (pendientes/registrados)
        if ($this->filters['status_filter'] === 'pending') {
            $students->setCollection($students->getCollection()->filter(fn($s) => is_null($s->attendance_status))->values());
        } elseif ($this->filters['status_filter'] === 'registered') {
            $students->setCollection($students->getCollection()->filter(fn($s) => !is_null($s->attendance_status))->values());
        }

        return $students;
    }

    #[Computed]
    public function currentShift()
    {
        return SchoolShift::find($this->selectedShiftId);
    }

    #[Computed]
    public function totalStudentsInShift()
    {
        return Student::where('school_id', Auth::user()->school_id)
            ->where('is_active', true)
            ->inShift($this->selectedShiftId)
            ->count();
    }

    #[Computed]
    public function statistics()
    {
        if (!$this->activeSession) {
            return [
                'total' => 0, 
                'registered' => 0, 
                'pending' => 0, 
                'present' => 0, 
                'late' => 0, 
                'absent' => 0,
                'excused' => 0 // Inicializamos en 0
            ];
        }

        $total = $this->totalStudentsInShift;
        $records = PlantelAttendanceRecord::where('daily_attendance_session_id', $this->activeSession->id)->get();

        // --- CÁLCULO DE EXCUSADOS EN ESTA TANDA ---
        $excuseService = app(ExcuseService::class);
        $allCoveredIds = $excuseService->getCoveredStudentsForDate(today());

        $excusedCount = Student::where('school_id', Auth::user()->school_id)
            ->where('is_active', true)
            ->inShift($this->selectedShiftId)
            ->whereIn('id', $allCoveredIds)
            ->count();
        // ------------------------------------------

        return [
            'total'      => $total,
            'registered' => $records->count(),
            'pending'    => $total - $records->count(),
            'present'    => $records->where('status', 'present')->count(),
            'late'       => $records->where('status', 'late')->count(),
            'absent'     => $records->where('status', 'absent')->count(),
            'excused'    => $excusedCount, // Lo mandamos a la vista
        ];
    }


    
    // ── Acciones ───────────────────────────────────────────────────

    public function record(int $studentId, string $status): void
    {
        $this->authorize('attendance_plantel.record');

        if (!$this->activeSession) {
            $this->dispatch('notify', 
                type: 'error', 
                title: 'Sin sesión activa',
                message: 'No hay sesión abierta para registrar asistencia'
            );
            return;
        }

        try {
            $service = app(PlantelAttendanceService::class);
            
            $service->recordAttendance([
                'school_id' => Auth::user()->school_id,
                'student_id' => $studentId,
                'school_shift_id' => $this->selectedShiftId,
                'date' => today()->toDateString(),
                'time' => now()->format('H:i:s'),
                'status' => $status,
                'method' => PlantelAttendanceRecord::METHOD_MANUAL,
                'registered_by' => Auth::id(),
            ]);

            // Recargar datos
            unset($this->students, $this->statistics);

            $this->dispatch('notify', 
                type: 'success',
                message: 'Asistencia registrada'
            );

            $this->dispatch('student-recorded', studentId: $studentId);

        } catch (\Exception $e) {
            $this->dispatch('notify', 
                type: 'error',
                message: $e->getMessage()
            );
        }
    }

    #[On('clear-filter')]
    public function clearFilter(string $key): void
    {
        $this->filters[$key] = '';
    }

    public function clearAllFilters(): void
    {
        $this->filters = [
            'search' => '',
            'section' => '',
            'status_filter' => '',
            'hide_excused' => true, // O false, dependiendo de tu estado inicial deseado
        ];
        $this->resetPage(); // Importante para volver a la primera página tras limpiar
    }

    // ── Render ─────────────────────────────────────────────────────

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.manual-attendance', [
            'students' => $this->students,
            'stats' => $this->statistics,
        ]);

        return $view->layout('layouts.app-module', config('modules.asistencia'));
    }
}