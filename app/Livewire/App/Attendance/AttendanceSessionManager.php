<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\Academic\SchoolShift;
use App\Models\Tenant\DailyAttendanceSession;
use App\Services\Attendance\PlantelAttendanceService;
use App\Models\Tenant\PlantelAttendanceRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Carbon\Carbon;

class AttendanceSessionManager extends Component
{
    public bool $showStatsModal = false;
    public ?DailyAttendanceSession $selectedSession = null;

    /**
     * Obtiene las tandas configuradas para la escuela.
     */
    #[Computed]
    public function shifts()
    {
        return SchoolShift::where('school_id', Auth::user()->school_id)
            ->get();
    }

    /**
     * Obtiene las sesiones creadas para el día de hoy.
     */
    #[Computed]
    public function dailySessions()
    {
        return DailyAttendanceSession::where('school_id', Auth::user()->school_id)
            ->whereDate('date', today())
            ->get()
            ->keyBy('school_shift_id');
    }

    /**
     * Abre una nueva sesión de asistencia para una tanda específica.
     */
    public function openSession(int $shiftId, PlantelAttendanceService $service)
    {
        $this->authorize('attendance_plantel.open_session');

        try {
            $service->openDailySession(Auth::user()->school_id, $shiftId, today());
            $this->dispatch('notify', type: 'success', message: 'Sesión de asistencia abierta correctamente.');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function confirmCloseSession(int $sessionId, \App\Services\Attendance\ExcuseService $excuseService)
    {
        $session = DailyAttendanceSession::with('shift')->find($sessionId);
        $sessionDate = Carbon::parse($session->date);

        // 1. Obtenemos los IDs de estudiantes de esta tanda que NO tienen registro aún
        $pendingStudentIds = \App\Models\Tenant\Student::active()
            ->where('school_id', $session->school_id)
            ->inShift($session->school_shift_id)
            ->whereNotIn('id', function($q) use ($sessionId) {
                $q->select('student_id')->from('plantel_attendance_records')
                ->where('daily_attendance_session_id', $sessionId);
            })
            ->pluck('id');

        // 2. OPTIMIZACIÓN: Obtenemos todos los estudiantes excusados para hoy en 1 sola consulta
        $excusedStudentIds = $excuseService->getCoveredStudentsForDate($sessionDate);

        // Intersectamos ambas colecciones para saber cuántos pendientes tienen excusa
        $projectedExcusedCount = $pendingStudentIds->intersect($excusedStudentIds)->count();

        // 3. Los ausentes proyectados son los que no tienen registro Y no tienen excusa
        $projectedAbsentCount = $pendingStudentIds->count() - $projectedExcusedCount;

        // 4. Calculamos totales actuales (los que ya están en la DB)
        $session->total_present = PlantelAttendanceRecord::where('daily_attendance_session_id', $sessionId)->present()->count();
        $session->total_late = PlantelAttendanceRecord::where('daily_attendance_session_id', $sessionId)->late()->count();
        
        // 5. Balance final: Real + Proyectado
        $session->total_absent = PlantelAttendanceRecord::where('daily_attendance_session_id', $sessionId)->absent()->count() + $projectedAbsentCount;
        $session->total_excused = PlantelAttendanceRecord::where('daily_attendance_session_id', $sessionId)->excused()->count() + $projectedExcusedCount;

        $this->selectedSession = $session;
        $this->dispatch('open-modal', 'confirm-session-close');
    }

    public function closeSession(PlantelAttendanceService $service)
    {
        $this->authorize('attendance_plantel.close_session');

        try {
            // 1. Antes de cerrar, forzamos el marcado de ausencias para los que faltan
            // Esto garantiza que total_registered sea igual a total_expected
            $service->markAbsences($this->selectedSession);

            // 2. Ahora cerramos y generamos los KPIs finales
            $service->closeDailySession($this->selectedSession);
            
            $this->dispatch('close-modal', 'confirm-session-close');
            $this->selectedSession = null; // Limpiamos la selección
            
            $this->dispatch('notify', type: 'success', message: 'Sesión cerrada. Ausencias procesadas y estadísticas generadas.');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.session-manager');

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }
}