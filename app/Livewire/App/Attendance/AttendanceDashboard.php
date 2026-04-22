<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\ClassroomAttendanceRecord;
use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Services\Attendance\ClassroomAttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AttendanceDashboard extends Component
{
    use AuthorizesRequests;

    // ── Filtros ───────────────────────────────────────────────────────────────
    public string $selectedDate;
    public string $calendarMonth; // primer día del mes visible en el dropdown (Y-m-d)
    public ?int $selectedSection = null;
    public ?int $selectedShift   = null;

    // ── Datos calculados ──────────────────────────────────────────────────────
    public array  $plantelStats   = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'total' => 0, 'rate' => 0.0];
    public array  $classroomStats = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'total' => 0];
    public array  $discrepancies  = [];
    public array  $recentActivity = [];
    public array  $weeklyStats    = [];
    public ?array $activeSession  = null;

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->authorize('attendance_plantel.reports');
        $this->selectedDate  = today()->toDateString();
        $this->calendarMonth = today()->startOfMonth()->toDateString();
        $this->loadAll();
    }

    // Llamado por wire:poll.10s para actualizar sin recargar la página
    public function refresh(): void
    {
        $this->loadAll();
    }

    // ── Watchers ──────────────────────────────────────────────────────────────

    public function updatedSelectedShift(): void   { $this->loadPlantelStats(); $this->loadRecentActivity(); }
    public function updatedSelectedSection(): void { $this->loadClassroomStats(); $this->loadDiscrepancies(); }

    // ── Navegación del calendario ─────────────────────────────────────────────

    public function selectDate(string $date): void
    {
        $this->selectedDate  = $date;
        $this->calendarMonth = Carbon::parse($date)->startOfMonth()->toDateString();
        $this->loadAll();
    }

    public function previousCalendarMonth(): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth)
            ->subMonth()->startOfMonth()->toDateString();
    }

    public function nextCalendarMonth(): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth)
            ->addMonth()->startOfMonth()->toDateString();
    }

    protected function buildCalendarDays(): \Illuminate\Support\Collection
    {
        $current = Carbon::parse($this->calendarMonth);
        $start   = $current->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end     = $current->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        $sessions = DailyAttendanceSession::where('school_id', Auth::user()->school_id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date', 'closed_at', 'total_expected', 'total_absent']);

        $byDate = $sessions->groupBy(fn ($s) => Carbon::parse($s->date)->toDateString());

        $days   = collect();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $key         = $cursor->toDateString();
            $daySessions = $byDate->get($key, collect());

            $status = null;
            if ($daySessions->isNotEmpty()) {
                $hasOpen = $daySessions->contains(fn ($s) => is_null($s->closed_at));
                if ($hasOpen) {
                    $status = 'warning';
                } else {
                    $totalExpected = $daySessions->sum('total_expected');
                    $totalAbsent   = $daySessions->sum('total_absent');
                    $status = ($totalExpected > 0 && ($totalAbsent / $totalExpected) * 100 > 20)
                        ? 'error'
                        : 'success';
                }
            }

            $days->push([
                'date'             => $key,
                'day'              => $cursor->day,
                'is_current_month' => $cursor->month === $current->month,
                'is_today'         => $cursor->isToday(),
                'is_selected'      => $key === $this->selectedDate,
                'has_sessions'     => $daySessions->isNotEmpty(),
                'status'           => $status,
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    // ── Carga de datos ────────────────────────────────────────────────────────

    public function loadAll(): void
    {
        $this->loadActiveSession();
        $this->loadPlantelStats();
        $this->loadClassroomStats();
        $this->loadDiscrepancies();
        $this->loadRecentActivity();
        $this->loadWeeklyStats();
    }

    public function loadActiveSession(): void
    {
        $session = DailyAttendanceSession::where('school_id', Auth::user()->school_id)
            ->whereDate('date', $this->selectedDate)
            ->withIndexRelations()
            ->latest()
            ->first();

        $this->activeSession = $session ? [
            'id'             => $session->id,
            'is_open'        => $session->isOpen(),
            'shift'          => $session->shift?->name ?? '—',
            'opened_at'      => $session->opened_at?->format('h:i A'),
            'total_expected' => $session->total_expected ?? 0,
        ] : null;
    }

    public function loadPlantelStats(): void
    {
        $schoolId = Auth::user()->school_id;
        $date     = Carbon::parse($this->selectedDate);

        $query = PlantelAttendanceRecord::where('school_id', $schoolId)
            ->whereDate('date', $date);

        if ($this->selectedShift) {
            $query->where('school_shift_id', $this->selectedShift);
        }

        $counts = $query
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = (int) ($counts[PlantelAttendanceRecord::STATUS_PRESENT] ?? 0);
        $late    = (int) ($counts[PlantelAttendanceRecord::STATUS_LATE]    ?? 0);
        $absent  = (int) ($counts[PlantelAttendanceRecord::STATUS_ABSENT]  ?? 0);
        $excused = (int) ($counts[PlantelAttendanceRecord::STATUS_EXCUSED] ?? 0);
        $total   = $present + $late + $absent + $excused;

        $this->plantelStats = [
            'present' => $present,
            'late'    => $late,
            'absent'  => $absent,
            'excused' => $excused,
            'total'   => $total,
            'rate'    => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0.0,
        ];

        $this->dispatch('plantel-stats-updated', ...$this->plantelStats);
    }

    public function loadClassroomStats(): void
    {
        $schoolId = Auth::user()->school_id;
        $date     = Carbon::parse($this->selectedDate);

        $query = ClassroomAttendanceRecord::where('school_id', $schoolId)
            ->whereDate('date', $date);

        if ($this->selectedSection) {
            $query->whereHas('assignment', fn ($q) => $q->where('school_section_id', $this->selectedSection));
        }

        $counts = $query
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = (int) ($counts[ClassroomAttendanceRecord::STATUS_PRESENT] ?? 0);
        $late    = (int) ($counts[ClassroomAttendanceRecord::STATUS_LATE]    ?? 0);
        $absent  = (int) ($counts[ClassroomAttendanceRecord::STATUS_ABSENT]  ?? 0);
        $excused = (int) ($counts[ClassroomAttendanceRecord::STATUS_EXCUSED] ?? 0);

        $this->classroomStats = [
            'present' => $present,
            'late'    => $late,
            'absent'  => $absent,
            'excused' => $excused,
            'total'   => $present + $late + $absent + $excused,
        ];
    }

    public function loadDiscrepancies(): void
    {
        try {
            $service = app(ClassroomAttendanceService::class);
            $date    = Carbon::parse($this->selectedDate);

            $this->discrepancies = $service
                ->detectDiscrepancies($date, Auth::user()->school_id)
                ->map(fn ($item) => [
                    'student_id'     => data_get($item, 'student.id'),
                    'student_name'   => data_get($item, 'student.full_name', '—'),
                    'photo'          => data_get($item, 'student.photo_path'),
                    'plantel_status' => data_get($item, 'plantel_status', '—'),
                    'absent_classes' => data_get($item, 'absent_classes', 0),
                ])->values()->toArray();
        } catch (\Exception $e) {
            $this->discrepancies = [];
        }
    }

    public function loadRecentActivity(): void
    {
        $query = PlantelAttendanceRecord::where('school_id', Auth::user()->school_id)
            ->whereDate('date', $this->selectedDate)
            ->with(['student:id,first_name,last_name,photo_path'])
            ->orderByDesc('time')
            ->limit(15);

        if ($this->selectedShift) {
            $query->where('school_shift_id', $this->selectedShift);
        }

        $this->recentActivity = $query->get()
            ->map(fn ($r) => [
                'student_name' => $r->student->full_name,
                'photo'        => $r->student->photo_path,
                'time'         => $r->time->format('h:i A'),
                'status'       => $r->status,
                'status_label' => $r->status_label,
                'method'       => $r->method,
                'method_label' => $r->method_label,
            ])->toArray();
    }

    public function loadWeeklyStats(): void
    {
        $schoolId = Auth::user()->school_id;

        $records = PlantelAttendanceRecord::where('school_id', $schoolId)
            ->whereDate('date', '>=', today()->subDays(6))
            ->selectRaw('date, status, count(*) as total')
            ->groupBy('date', 'status')
            ->get();

        $byDate = $records->groupBy(fn ($r) => Carbon::parse($r->date)->toDateString());

        $stats = collect(range(6, 0))->map(function ($daysAgo) use ($byDate) {
            $date    = today()->subDays($daysAgo);
            $dayMap  = $byDate->get($date->toDateString(), collect())->pluck('total', 'status');

            $present = ((int) ($dayMap[PlantelAttendanceRecord::STATUS_PRESENT] ?? 0))
                     + ((int) ($dayMap[PlantelAttendanceRecord::STATUS_LATE]    ?? 0));
            $total   = (int) $dayMap->sum();
            $rate    = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;

            return ['date' => $date->format('d/M'), 'rate' => $rate];
        })->values()->toArray();

        $this->weeklyStats = $stats;
        $this->dispatch('weekly-stats-updated', stats: $this->weeklyStats);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.app.attendance.attendance-dashboard', [
            'shifts'        => Auth::user()->school->shifts,
            'sections'      => SchoolSection::withFullRelations()->get(),
            'calendarDays'  => $this->buildCalendarDays(),
            'calendarLabel' => Carbon::parse($this->calendarMonth)->isoFormat('MMMM YYYY'),
        ])->layout('layouts.app-module', config('modules.configuracion'));
    }
}
