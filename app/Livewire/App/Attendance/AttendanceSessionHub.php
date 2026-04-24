<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Hub de Asistencia')]
class AttendanceSessionHub extends Component
{
    #[Url(as: 'fecha')]
    public string $date = '';

    public ?int $selectedSessionId = null;

    public function mount(): void
    {
        // Si no hay fecha en URL, usar hoy
        if (empty($this->date)) {
            $this->date = today()->toDateString();
        }
        $this->setDefaultSession(); // Auto-seleccionar al cargar
    }

    /**
     * Construye el calendario del mes de la fecha seleccionada
     * con indicadores de estado de las sesiones de cada día
     */
    #[Computed]
    public function calendarDays(): Collection
    {
        $current = Carbon::parse($this->date);
        $startOfMonth = $current->copy()->startOfMonth();
        $endOfMonth = $current->copy()->endOfMonth();

        // Ajustar inicio para comenzar en lunes
        $start = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $end = $endOfMonth->copy()->endOfWeek(Carbon::MONDAY);

        $days = collect();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dateKey = $cursor->toDateString();
            
            // Obtener sesiones de este día
            $sessions = DailyAttendanceSession::where('school_id', Auth::user()->school_id)
                ->whereDate('date', $dateKey)
                ->get();

            // Determinar estado del día basado en las sesiones
            $status = $this->determineDayStatus($sessions);
            
            $days->push([
                'date' => $cursor->copy(),
                'is_current_month' => $cursor->month === $current->month,
                'is_today' => $cursor->isToday(),
                'is_selected' => $cursor->isSameDay(Carbon::parse($this->date)),
                'has_sessions' => $sessions->isNotEmpty(),
                'status' => $status, // 'success', 'warning', 'error', null
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    /**
     * Sesiones del día seleccionado
     */
    #[Computed]
    public function sessionsOfDay(): Collection
    {
        return DailyAttendanceSession::with(['shift', 'openedBy'])
            ->where('school_id', Auth::user()->school_id)
            ->whereDate('date', $this->date)
            ->orderBy('school_shift_id')
            ->get();
    }

    /**
     * Métricas del día seleccionado
     */
    #[Computed]
    public function dayMetrics(): array
    {
        $sessions = $this->sessionsOfDay;

        if ($sessions->isEmpty()) {
            return [
                'total_expected'  => 0,
                'total_present'   => 0,
                'total_late'      => 0,
                'total_absent'    => 0,
                'total_excused'   => 0,
                'attendance_rate' => 0,
            ];
        }

        // Sumatorias directas desde la base de datos (Ultra rápido)
        $totalExpected = $sessions->sum('total_expected');
        $totalPresent  = $sessions->sum('total_present');
        $totalLate     = $sessions->sum('total_late');
        $totalAbsent   = $sessions->sum('total_absent');
        $totalExcused  = $sessions->sum('total_excused'); // Directo de la migración

        $attendanceRate = $totalExpected > 0 
            ? round((($totalPresent + $totalLate) / $totalExpected) * 100, 1)
            : 0;

        return [
            'total_expected'  => $totalExpected,
            'total_present'   => $totalPresent,
            'total_late'      => $totalLate,
            'total_absent'    => $totalAbsent,
            'total_excused'   => $totalExcused,
            'attendance_rate' => $attendanceRate,
        ];
    }

    /**
     * Cambiar fecha seleccionada
     */
    public function selectDate(string $date): void
    {
        $this->date = $date;
        $this->setDefaultSession();
    }

    /**
     * Navegar al mes anterior
     */
    public function previousMonth(): void
    {
        $this->date = Carbon::parse($this->date)
            ->subMonth()
            ->toDateString();
        $this->setDefaultSession();
    }

    /**
     * Navegar al mes siguiente
     */
    public function nextMonth(): void
    {
        $this->date = Carbon::parse($this->date)
            ->addMonth()
            ->toDateString();
        $this->setDefaultSession();
    }

    /**
     * Ir a hoy
     */
    public function goToToday(): void
    {
        $this->date = today()->toDateString();
        $this->setDefaultSession();
    }

    /**
     * Determinar el estado visual de un día basado en sus sesiones
     */
    protected function determineDayStatus(Collection $sessions): ?string
    {
        if ($sessions->isEmpty()) {
            return null;
        }

        $hasOpen = $sessions->contains(fn($s) => is_null($s->closed_at));
        
        // Si hay sesiones abiertas → naranja (warning)
        if ($hasOpen) {
            return 'warning';
        }

        // Calcular tasa de ausencias
        $totalExpected = $sessions->sum('total_expected');
        $totalAbsent = $sessions->sum('total_absent');

        if ($totalExpected === 0) {
            return 'success';
        }

        $absenceRate = ($totalAbsent / $totalExpected) * 100;

        // > 20% de ausencias → rojo (error)
        if ($absenceRate > 20) {
            return 'error';
        }

        // Sesiones cerradas normalmente → azul (success)
        return 'success';
    }

    // --- NUEVOS MÉTODOS ---

    public function selectSession(int $sessionId): void
    {
        $this->selectedSessionId = $sessionId;
    }

    protected function setDefaultSession(): void
    {
        // Selecciona la primera sesión del día automáticamente si hay alguna
        $first = $this->sessionsOfDay->first();
        $this->selectedSessionId = $first ? $first->id : null;
    }

    #[Computed]
    public function selectedSessionDetail()
    {
        if (!$this->selectedSessionId) return null;
        return $this->sessionsOfDay->firstWhere('id', $this->selectedSessionId);
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.attendance-session-hub', [
            'currentMonth' => Carbon::parse($this->date)->isoFormat('MMMM YYYY'),
        ]);

        return $view->layout('layouts.app-module', config('modules.asistencia'));
    }
}