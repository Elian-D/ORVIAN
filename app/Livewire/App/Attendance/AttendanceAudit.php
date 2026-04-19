<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\PlantelAttendanceRecord;
use Illuminate\Support\Facades\DB; // <-- Asegúrate de importar DB
use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AttendanceAudit extends Component
{
    public DailyAttendanceSession $session;
    public string $activeFilter = 'all';

    public array $filters = [
        'search' => '',
    ];

    protected ?Collection $cachedRecords = null;
    
    public array $stats = [
        'all' => 0,
        'present' => 0,
        'late' => 0,
        'absent' => 0,
        'excused' => 0,
    ];

    protected $listeners = ['attendanceUpdated' => '$refresh'];

    public function mount($sessionId): void
    {
        $this->session = DailyAttendanceSession::findOrFail($sessionId);

        $this->authorize('attendance_plantel.view');
        
        // Verificar que la sesión esté cerrada
        if (!$this->session->closed_at) {
            abort(403, 'La auditoría solo está disponible para sesiones cerradas.');
        }

        $this->calculateStats();
    }

    public function calculateStats(): void
    {
        $records = $this->getSessionRecords();
        
        $this->stats = [
            'all' => $records->count(),
            'present' => $records->where('status', PlantelAttendanceRecord::STATUS_PRESENT)->count(),
            'late' => $records->where('status', PlantelAttendanceRecord::STATUS_LATE)->count(),
            'absent' => $records->where('status', PlantelAttendanceRecord::STATUS_ABSENT)->count(),
            'excused' => $records->where('status', PlantelAttendanceRecord::STATUS_EXCUSED)->count(),
        ];
    }

    protected function getSessionRecords(): Collection
    {
        // 2. Si ya los consultamos en esta misma petición, devolverlos de inmediato
        if ($this->cachedRecords !== null) {
            return $this->cachedRecords;
        }

        // 3. Si no, consultarlos y guardarlos en el "caché"
        return $this->cachedRecords = PlantelAttendanceRecord::where('daily_attendance_session_id', $this->session->id)
            ->with(['student'])
            ->get();
    }

    public function setFilter(string $filter): void
    {
        $this->activeFilter = $filter;
    }

    public function getFilteredRecordsProperty(): Collection
    {
        // Obtenemos todos los registros de la sesión
        $records = $this->getSessionRecords();

        // 2. Aplicar filtro por estado (activeFilter)
        if ($this->activeFilter !== 'all') {
            $records = $records->where('status', $this->activeFilter);
        }

        // 3. Aplicar filtro por búsqueda de nombre
        if (!empty($this->filters['search'])) {
            $search = Str::lower($this->filters['search']);
            $records = $records->filter(function ($record) use ($search) {
                return Str::contains(Str::lower($record->student->full_name), $search);
            });
        }

        return $records;
    }

    public function updateStatus(int $recordId, string $newStatus): void
    {
        $this->authorize('attendance_plantel.verify');

        $record = PlantelAttendanceRecord::findOrFail($recordId);
        
        // Validar que el registro pertenece a esta sesión
        if ($record->daily_attendance_session_id !== $this->session->id) {
            abort(403);
        }
        


        // No permitir cambios en registros excusados
        if ($record->status === PlantelAttendanceRecord::STATUS_EXCUSED) {
            $this->dispatch('notify', 
                type: 'warning',
                title: 'Acción no permitida',
                message: 'Los registros excusados no pueden modificarse desde la auditoría.'
            );
            return;
        }

        // Validar que el nuevo estado sea válido
        $validStatuses = [
            PlantelAttendanceRecord::STATUS_PRESENT,
            PlantelAttendanceRecord::STATUS_LATE,
            PlantelAttendanceRecord::STATUS_ABSENT,
        ];

        if (!in_array($newStatus, $validStatuses)) {
            $this->dispatch('notify',
                type: 'error',
                title: 'Estado inválido',
                message: 'Solo se permiten los estados: Presente, Tardanza o Ausente.'
            );
            return;
        }

        $oldStatus = $record->status;

        // Si el estado es el mismo, no hacemos consultas innecesarias
        if ($oldStatus === $newStatus) {
            return;
        }

        // ── Inicia la Transacción ─────────────────────────────────────
        DB::transaction(function () use ($record, $oldStatus, $newStatus) {
            
            // 1. Actualizar el registro del estudiante
            $record->update(['status' => $newStatus]);

            // 2. Mapear los estados con las columnas de la tabla de sesiones
            $columnMap = [
                PlantelAttendanceRecord::STATUS_PRESENT => 'total_present',
                PlantelAttendanceRecord::STATUS_LATE    => 'total_late',
                PlantelAttendanceRecord::STATUS_ABSENT  => 'total_absent',
                // Excusados no está aquí porque no se puede cambiar a/desde excusado en esta vista
            ];

            // 3. Decrementar el contador viejo e incrementar el nuevo en la sesión
            if (isset($columnMap[$oldStatus])) {
                $this->session->decrement($columnMap[$oldStatus]);
            }
            
            if (isset($columnMap[$newStatus])) {
                $this->session->increment($columnMap[$newStatus]);
            }
        });
        // ── Fin de la Transacción ─────────────────────────────────────

        // Refrescar el modelo de la sesión en memoria para tener los datos actualizados
        $this->session->refresh();
        
        // Recalcular las estadísticas de las cards (filtros)
        $this->calculateStats();

        $statusLabels = [
            PlantelAttendanceRecord::STATUS_PRESENT => 'Presente',
            PlantelAttendanceRecord::STATUS_LATE => 'Tardanza',
            PlantelAttendanceRecord::STATUS_ABSENT => 'Ausente',
        ];

        $this->dispatch('notify',
            type: 'success',
            title: 'Estado actualizado',
            message: "Cambio de {$statusLabels[$oldStatus]} a {$statusLabels[$newStatus]} registrado correctamente."
        );
    }

    public function getStatusColorProperty(): array
    {
        return [
            PlantelAttendanceRecord::STATUS_PRESENT => [
                'bg' => 'bg-emerald-500/10 dark:bg-emerald-500/20',
                'border' => 'border-emerald-500/20',
                'text' => 'text-emerald-600 dark:text-emerald-400',
                'ring' => 'ring-emerald-500/20',
                'icon' => 'text-emerald-500',
            ],
            PlantelAttendanceRecord::STATUS_LATE => [
                'bg' => 'bg-amber-500/10 dark:bg-amber-500/20',
                'border' => 'border-amber-500/20',
                'text' => 'text-amber-600 dark:text-amber-400',
                'ring' => 'ring-amber-500/20',
                'icon' => 'text-amber-500',
            ],
            PlantelAttendanceRecord::STATUS_ABSENT => [
                'bg' => 'bg-red-500/10 dark:bg-red-500/20',
                'border' => 'border-red-500/20',
                'text' => 'text-red-600 dark:text-red-400',
                'ring' => 'ring-red-500/20',
                'icon' => 'text-red-500',
            ],
            PlantelAttendanceRecord::STATUS_EXCUSED => [
                'bg' => 'bg-blue-500/10 dark:bg-blue-500/20',
                'border' => 'border-blue-500/20',
                'text' => 'text-blue-600 dark:text-blue-400',
                'ring' => 'ring-blue-500/20',
                'icon' => 'text-blue-500',
            ],
        ];
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.attendance-audit');

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }
}