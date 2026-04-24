<?php

namespace App\Livewire\App\Attendance;

use App\Exports\ReportExport;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\Subject;
use App\Models\Tenant\Academic\TeacherSubjectSection;
use App\Models\Tenant\ClassroomAttendanceRecord;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\Student;
use App\Models\Tenant\Teacher;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceReports extends Component
{
    use AuthorizesRequests;

    // ── Tipo de reporte ───────────────────────────────────────────────────────
    // summary | student | discrepancies | teacher
    public string $reportType = 'summary';

    // ── Filtros compartidos ───────────────────────────────────────────────────
    public string $dateFrom = '';
    public string $dateTo   = '';

    // ── Filtros por tipo ──────────────────────────────────────────────────────
    public ?int $sectionId = null;
    public ?int $studentId = null;
    public ?int $teacherId = null;

    // ── Estado del reporte ────────────────────────────────────────────────────
    public bool  $reportGenerated = false;
    public array $reportData      = [];
    public array $reportMeta      = [];

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->authorize('attendance_plantel.reports');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = today()->toDateString();
    }

    public function updatedReportType(): void
    {
        $this->reportGenerated = false;
        $this->reportData      = [];
        $this->reportMeta      = [];
    }

    // ── Generación ────────────────────────────────────────────────────────────

    public function generate(): void
    {
        $this->validate([
            'dateFrom' => 'required|date',
            'dateTo'   => 'required|date|after_or_equal:dateFrom',
        ]);

        match ($this->reportType) {
            'summary'       => $this->generateSummary(),
            'student'       => $this->generateStudentReport(),
            'discrepancies' => $this->generateDiscrepanciesReport(),
            'teacher'       => $this->generateTeacherCoverageReport(),
            default         => null,
        };

        $this->reportGenerated = true;
    }

    private function generateSummary(): void
    {
        $schoolId = Auth::user()->school_id;

        $query = PlantelAttendanceRecord::where('school_id', $schoolId)
            ->whereBetween('date', [$this->dateFrom, $this->dateTo])
            ->with(['student:id,first_name,last_name,school_section_id', 'student.section.grade']);

        if ($this->sectionId) {
            $query->whereHas('student', fn ($q) => $q->where('school_section_id', $this->sectionId));
        }

        $records = $query->get();

        $bySection = $records->groupBy(fn ($r) => $r->student->section?->id ?? 0);

        $this->reportData = $bySection->map(fn ($group) => [
            'Sección'     => $group->first()->student->section?->full_label ?? 'Sin sección',
            'Total'       => $group->count(),
            'Presentes'   => $group->whereIn('status', ['present', 'late'])->count(),
            'Tardanzas'   => $group->where('status', 'late')->count(),
            'Ausentes'    => $group->where('status', 'absent')->count(),
            'Justificados'=> $group->where('status', 'excused')->count(),
            'Tasa (%)'    => $group->count() > 0
                ? round(($group->whereIn('status', ['present', 'late'])->count() / $group->count()) * 100, 1)
                : 0,
        ])->sortByDesc('Tasa (%)')->values()->toArray();

        $total   = $records->count();
        $present = $records->whereIn('status', ['present', 'late'])->count();

        $this->reportMeta = [
            'total'         => $total,
            'present'       => $present,
            'absent'        => $records->where('status', 'absent')->count(),
            'excused'       => $records->where('status', 'excused')->count(),
            'late'          => $records->where('status', 'late')->count(),
            'overall_rate'  => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            'sections'      => $bySection->count(),
        ];
    }

    private function generateStudentReport(): void
    {
        if (! $this->studentId) {
            $this->addError('studentId', 'Selecciona un estudiante para continuar.');
            $this->reportGenerated = false;
            return;
        }

        $schoolId = Auth::user()->school_id;

        $records = PlantelAttendanceRecord::where('school_id', $schoolId)
            ->where('student_id', $this->studentId)
            ->whereBetween('date', [$this->dateFrom, $this->dateTo])
            ->with(['shift:id,type', 'registeredBy:id,name'])
            ->orderByDesc('date')
            ->get();

        $this->reportData = $records->map(fn ($r) => [
            'Fecha'          => $r->date->isoFormat('D MMM YYYY'),
            'Hora'           => $r->time?->format('h:i A') ?? '—',
            'Estado'         => $r->status_label,
            'Tanda'          => $r->shift?->type ?? '—',
            'Método'         => $r->method_label,
            'Registrado por' => $r->registeredBy?->name ?? '—',
            'Notas'          => $r->notes ?? '',
        ])->toArray();

        $student = Student::with(['section.grade'])->find($this->studentId);
        $total   = $records->count();
        $present = $records->whereIn('status', ['present', 'late'])->count();

        $this->reportMeta = [
            'student_name' => $student?->full_name ?? '—',
            'section'      => $student?->section?->full_label ?? '—',
            'total'        => $total,
            'present'      => $present,
            'absent'       => $records->where('status', 'absent')->count(),
            'excused'      => $records->where('status', 'excused')->count(),
            'late'         => $records->where('status', 'late')->count(),
            'rate'         => $total > 0 ? round(($present / $total) * 100, 1) : 0,
        ];
    }

    private function generateDiscrepanciesReport(): void
    {
        $schoolId = Auth::user()->school_id;

        // Registros de plantel presentes/tardanza → indexados por student_id + date
        $plantelPresent = PlantelAttendanceRecord::where('school_id', $schoolId)
            ->whereBetween('date', [$this->dateFrom, $this->dateTo])
            ->whereIn('status', ['present', 'late'])
            ->with('student:id,first_name,last_name')
            ->get()
            ->keyBy(fn ($r) => $r->student_id . '_' . $r->date->toDateString());

        // Conteo de clases ausentes por student_id + date
        $classAbsences = ClassroomAttendanceRecord::where('school_id', $schoolId)
            ->whereBetween('date', [$this->dateFrom, $this->dateTo])
            ->where('status', 'absent')
            ->selectRaw('student_id, date, COUNT(*) as absent_count')
            ->groupBy('student_id', 'date')
            ->get()
            ->mapWithKeys(fn ($r) => [
                $r->student_id . '_' . Carbon::parse($r->date)->toDateString() => (int) $r->absent_count,
            ]);

        $discrepancies = collect();

        foreach ($classAbsences as $key => $absentCount) {
            if (isset($plantelPresent[$key])) {
                $record = $plantelPresent[$key];
                $discrepancies->push([
                    'Fecha'           => $record->date->isoFormat('D MMM YYYY'),
                    'Estudiante'      => $record->student?->full_name ?? '—',
                    'Estado Plantel'  => PlantelAttendanceRecord::STATUS_LABELS[$record->status] ?? '—',
                    'Clases Ausente'  => $absentCount,
                ]);
            }
        }

        $this->reportData = $discrepancies->sortByDesc('Fecha')->values()->toArray();

        $this->reportMeta = [
            'total_events' => count($this->reportData),
        ];
    }

    private function generateTeacherCoverageReport(): void
    {
        $schoolId = Auth::user()->school_id;

        $query = TeacherSubjectSection::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with(['teacher:id,first_name,last_name', 'subject:id,name', 'section.grade']);

        if ($this->teacherId) {
            $query->where('teacher_id', $this->teacherId);
        }

        $assignments = $query->get();

        // Días hábiles en el rango (lunes–viernes)
        $dateFrom  = Carbon::parse($this->dateFrom);
        $dateTo    = Carbon::parse($this->dateTo);
        $weekdays  = 0;
        for ($d = $dateFrom->copy(); $d->lte($dateTo); $d->addDay()) {
            if ($d->isWeekday()) {
                $weekdays++;
            }
        }

        $this->reportData = $assignments->map(function ($assignment) use ($weekdays) {
            $recorded = ClassroomAttendanceRecord::where('teacher_subject_section_id', $assignment->id)
                ->whereBetween('date', [$this->dateFrom, $this->dateTo])
                ->distinct('date')
                ->count('date');

            $coverage = $weekdays > 0 ? min(round(($recorded / $weekdays) * 100, 1), 100) : 0;

            $grade   = $assignment->section?->grade?->name;
            $section = $assignment->section?->label;

            return [
                'Maestro'    => $assignment->teacher?->full_name ?? '—',
                'Materia'    => $assignment->subject?->name ?? '—',
                'Sección'    => $grade && $section ? "{$grade}° - {$section}" : '—',
                'Registradas'=> $recorded,
                'Esperadas'  => $weekdays,
                'Cobertura'  => $coverage . '%',
            ];
        })->sortBy('Maestro')->values()->toArray();

        $this->reportMeta = [
            'total_assignments' => $assignments->count(),
            'weekdays'          => $weekdays,
        ];
    }

    // ── Exportación ───────────────────────────────────────────────────────────

    public function exportExcel()
    {
        if (! $this->reportGenerated || empty($this->reportData)) {
            return;
        }

        $headings = array_keys($this->reportData[0] ?? []);
        $rows     = array_map('array_values', $this->reportData);

        $typeLabel = match ($this->reportType) {
            'summary'       => 'resumen',
            'student'       => 'estudiante',
            'discrepancies' => 'discrepancias',
            'teacher'       => 'maestros',
            default         => 'reporte',
        };

        return Excel::download(
            new ReportExport($rows, $headings),
            "reporte-asistencia-{$typeLabel}-" . date('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->reportGenerated || empty($this->reportData)) {
            return;
        }

        $school     = Auth::user()->school;
        $logoPath   = $school->logo_path ? storage_path('app/public/' . $school->logo_path) : null;
        $logoBase64 = ($logoPath && file_exists($logoPath))
            ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $typeLabel = match ($this->reportType) {
            'summary'       => 'Resumen General del Período',
            'student'       => 'Por Estudiante',
            'discrepancies' => 'Discrepancias del Período',
            'teacher'       => 'Cobertura de Pase de Lista por Maestro',
            default         => 'Reporte',
        };

        $pdf = Pdf::loadView('reports.attendance-report', [
            'school'     => $school,
            'logoBase64' => $logoBase64,
            'typeLabel'  => $typeLabel,
            'dateFrom'   => $this->dateFrom,
            'dateTo'     => $this->dateTo,
            'reportData' => $this->reportData,
            'reportMeta' => $this->reportMeta,
            'headings'   => array_keys($this->reportData[0] ?? []),
        ])->setPaper('letter', 'landscape');

        $slug = str_replace(' ', '-', strtolower($typeLabel));

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "reporte-{$slug}-" . date('Y-m-d') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $schoolId = Auth::user()->school_id;

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.attendance-reports', [
            'sectionOptions' => SchoolSection::withFullRelations()->get()
                ->pluck('full_label', 'id')
                ->toArray(),
            'studentOptions' => Student::where('school_id', $schoolId)
                ->active()
                ->orderBy('last_name')
                ->get()
                ->pluck('full_name', 'id')
                ->toArray(),
            'teacherOptions' => Teacher::where('school_id', $schoolId)
                ->active()
                ->orderBy('first_name')
                ->get()
                ->pluck('full_name', 'id')
                ->toArray(),
        ]);

        return $view->layout('layouts.app-module', config('modules.asistencia'));
    }
}
