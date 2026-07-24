<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\PlantelAttendanceRecord;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[Layout('layouts.app')]
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

    // ── Modal: Confirmar corrección a Tardanza ──────────────────────
    public bool $showMarkLateModal = false;
    public ?int $recordToMarkLateId = null;

    // ── Modal: Confirmar corrección de Excusado a Presente ──────────
    public bool $showMarkPresentModal = false;
    public ?int $recordToMarkPresentId = null;

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

    /**
     * Abre el modal de confirmación — usar un modal en vez de aplicar el
     * cambio directo al clic evita corregir a un estudiante por error
     * (doble clic accidental, dedo resbalado en móvil, etc.).
     */
    public function confirmMarkAsLate(int $recordId): void
    {
        $this->authorize('attendance_plantel.verify');

        $this->recordToMarkLateId = $recordId;
        $this->showMarkLateModal  = true;
        $this->dispatch('open-modal', 'confirm-mark-late');
    }

    public function getRecordToMarkLateProperty(): ?PlantelAttendanceRecord
    {
        return $this->recordToMarkLateId
            ? PlantelAttendanceRecord::with('student')->find($this->recordToMarkLateId)
            : null;
    }

    protected function closeMarkLateModal(): void
    {
        $this->showMarkLateModal  = false;
        $this->recordToMarkLateId = null;
        $this->dispatch('close-modal', 'confirm-mark-late');
    }

    /**
     * Abre el modal de confirmación para la corrección de Excusado a Presente.
     * Gate distinto (`manage_excuses`, no `attendance_plantel.verify`)
     * por ser una corrección más sensible: reescribe indirectamente el efecto
     * de una excusa confirmada.
     */
    public function confirmMarkAsPresent(int $recordId): void
    {
        $this->authorize('manage_excuses');

        $this->recordToMarkPresentId = $recordId;
        $this->showMarkPresentModal   = true;
        $this->dispatch('open-modal', 'confirm-mark-present');
    }

    public function getRecordToMarkPresentProperty(): ?PlantelAttendanceRecord
    {
        return $this->recordToMarkPresentId
            ? PlantelAttendanceRecord::with('student')->find($this->recordToMarkPresentId)
            : null;
    }

    protected function closeMarkPresentModal(): void
    {
        $this->showMarkPresentModal   = false;
        $this->recordToMarkPresentId  = null;
        $this->dispatch('close-modal', 'confirm-mark-present');
    }

    /**
     * Única corrección manual permitida en esta vista: un estudiante marcado
     * como ausente (sin excusa) que en realidad llegó tarde sin avisar. No
     * existe ninguna otra transición — editar registros de sesiones pasadas,
     * o de un estudiante actualmente fuera del plantel, violaría la regla
     * central de este dominio (una excusa, o una corrección, nunca modifica
     * un registro ya creado fuera del momento en que ocurrió el hecho).
     */
    public function markAsLate(?int $recordId = null): void
    {
        $this->authorize('attendance_plantel.verify');

        $recordId ??= $this->recordToMarkLateId;

        if (! $recordId) {
            $this->closeMarkLateModal();
            return;
        }

        if (! $this->session->date->isToday()) {
            $this->closeMarkLateModal();
            $this->dispatch('notify',
                type: 'warning',
                title: 'Acción no permitida',
                message: 'Solo se pueden corregir registros de la sesión de hoy.'
            );
            return;
        }

        $record = PlantelAttendanceRecord::findOrFail($recordId);

        // Validar que el registro pertenece a esta sesión
        if ($record->daily_attendance_session_id !== $this->session->id) {
            abort(403);
        }

        if ($record->status !== PlantelAttendanceRecord::STATUS_ABSENT) {
            $this->closeMarkLateModal();
            $this->dispatch('notify',
                type: 'warning',
                title: 'Acción no permitida',
                message: 'Solo se pueden corregir registros marcados como ausente.'
            );
            return;
        }

        /*
        Aquí se actualizan ambos campos (`status` y `corrected_by_user_id`) dentro de la misma transacción existente, 
        tal como lo requiere la auditoría de asistencia. Esto asegura que la corrección manual quede registrada de manera consistente 
        y que las estadísticas de la sesión se mantengan precisas.
        */

        DB::transaction(function () use ($record) {
            $record->update([
                'status'              => PlantelAttendanceRecord::STATUS_LATE,
                'corrected_by_user_id'=> Auth::id(),
                'corrected_at'        => now(),
            ]);
            $this->session->decrement('total_absent');
            $this->session->increment('total_late');
        });

        // Refrescar el modelo de la sesión en memoria para tener los datos actualizados
        $this->session->refresh();

        // Recalcular las estadísticas de las cards (filtros)
        $this->calculateStats();

        $this->closeMarkLateModal();

        $this->dispatch('notify',
            type: 'success',
            title: 'Estado actualizado',
            message: 'El estudiante ahora aparece como Tardanza.'
        );
    }

    /**
     * Determina si un registro puede corregirse desde esta vista: la sesión
     * debe ser la de hoy y el registro debe estar ausente.
     */
    public function canMarkAsLate(PlantelAttendanceRecord $record): bool
    {
        return $this->session->date->isToday()
            && $record->status === PlantelAttendanceRecord::STATUS_ABSENT;
    }

    /**
     * Segunda transición permitida en esta vista: un estudiante que quedó
     * como excusado porque la sesión cerró antes de que el kiosko capturara
     * su llegada real. Igual que la corrección a Tardanza, solo aplica hoy
     * y nunca mientras el estudiante esté "afuera" (salida sin regreso).
     */
    public function markAsPresent(?int $recordId = null): void
    {
        $this->authorize('manage_excuses');

        $recordId ??= $this->recordToMarkPresentId;

        if (! $recordId) {
            $this->closeMarkPresentModal();
            return;
        }

        if (! $this->session->date->isToday()) {
            $this->closeMarkPresentModal();
            $this->dispatch('notify',
                type: 'warning',
                title: 'Acción no permitida',
                message: 'Solo se pueden corregir registros de la sesión de hoy.'
            );
            return;
        }

        $record = PlantelAttendanceRecord::findOrFail($recordId);

        // Validar que el registro pertenece a esta sesión
        if ($record->daily_attendance_session_id !== $this->session->id) {
            abort(403);
        }

        if ($record->status !== PlantelAttendanceRecord::STATUS_EXCUSED) {
            $this->closeMarkPresentModal();
            $this->dispatch('notify',
                type: 'warning',
                title: 'Acción no permitida',
                message: 'Solo se pueden corregir registros marcados como Excusado.'
            );
            return;
        }

        DB::transaction(function () use ($record) {
            $record->update([
                'status'               => PlantelAttendanceRecord::STATUS_PRESENT,
                'corrected_by_user_id' => Auth::id(),
                'corrected_at'         => now(),
            ]);
            $this->session->decrement('total_excused');
            $this->session->increment('total_present');
        });

        // Refrescar el modelo de la sesión en memoria para tener los datos actualizados
        $this->session->refresh();

        // Recalcular las estadísticas de las cards (filtros)
        $this->calculateStats();

        $this->closeMarkPresentModal();

        $this->dispatch('notify',
            type: 'success',
            title: 'Estado actualizado',
            message: 'El estudiante ahora aparece como Presente.'
        );
    }

    /**
     * Determina si un registro puede corregirse de Excusado a Presente: la
     * sesión debe ser la de hoy y el registro debe estar excusado.
     */
    public function canMarkAsPresent(PlantelAttendanceRecord $record): bool
    {
        return $this->session->date->isToday()
            && $record->status === PlantelAttendanceRecord::STATUS_EXCUSED;
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
        return view('livewire.app.attendance.attendance-audit');
    }
}