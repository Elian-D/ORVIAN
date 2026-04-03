<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\Academic\SchoolShift;
use App\Services\Attendance\PlantelAttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DailySessionManager extends Component
{
    public ?DailyAttendanceSession $session = null;
    public int $selectedShiftId;
    public string $sessionDate;
    public bool $showConfirmClose = false;

    public function mount(): void
    {
        $this->sessionDate    = today()->toDateString();
        $this->selectedShiftId = SchoolShift::first()?->id ?? 0;
        $this->loadActiveSession();
    }

    public function loadActiveSession(): void
    {
        $this->session = DailyAttendanceSession::where('school_id', Auth::user()->school_id)
            ->where('date', $this->sessionDate)
            ->where('school_shift_id', $this->selectedShiftId)
            ->active()
            ->first();
    }

    public function openSession(PlantelAttendanceService $service): void
    {
        $this->authorize('attendance_plantel.open_session');

        try {
            $this->session = $service->openDailySession(
                Auth::user()->school_id,
                $this->selectedShiftId,
                Carbon::parse($this->sessionDate)
            );

            $this->dispatch('session-opened');
            $this->dispatch('toast', type: 'success', message: 'Sesión abierta correctamente.');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function closeSession(PlantelAttendanceService $service): void
    {
        $this->authorize('attendance_plantel.close_session');

        try {
            $service->closeDailySession($this->session);
            $this->showConfirmClose = false;
            $this->session          = null;

            $this->dispatch('session-closed');
            $this->dispatch('toast', type: 'success', message: 'Sesión cerrada. Estadísticas calculadas.');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function markAbsences(PlantelAttendanceService $service): void
    {
        $this->authorize('attendance_plantel.record');

        if (! $this->session) {
            $this->dispatch('toast', type: 'error', message: 'No hay sesión activa.');
            return;
        }

        $count = $service->markAbsences($this->session);
        $this->loadActiveSession();
        $this->dispatch('toast', type: 'info', message: "{$count} estudiantes marcados como ausentes/justificados.");
    }

    public function render()
    {
        return view('livewire.app.attendance.daily-session-manager', [
            'shifts' => SchoolShift::where('school_id', Auth::user()->school_id)->get(),
        ]);
    }
}