<?php

namespace App\Livewire\App\Attendance;

use App\Exports\ClassroomAttendanceExport;
use App\Filters\App\Attendance\Classroom\ClassroomAttendanceFilters;
use App\Livewire\Base\DataTable;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\Subject;
use App\Models\Tenant\ClassroomAttendanceRecord;
use App\Models\Tenant\Teacher;
use App\Tables\App\Attendance\ClassroomAttendanceTableConfig;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Facades\Excel;

class ClassroomAttendanceHistory extends DataTable
{
    use AuthorizesRequests;

    #[Url]
    public array $filters = [
        'search'     => '',
        'date_from'  => '',
        'date_to'    => '',
        'section_id' => '',
        'subject_id' => '',
        'teacher_id' => '',
        'status'     => '',
    ];

    // ID del maestro autenticado (null = director/coordinador, ve todo)
    public ?int $teacherScope = null;

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        parent::mount();

        $teacher = Teacher::where('user_id', Auth::id())
            ->where('school_id', Auth::user()->school_id)
            ->first();

        $this->teacherScope = $teacher?->id;

        // Los maestros no necesitan ver la columna "Maestro" (siempre son ellos mismos)
        if ($this->teacherScope) {
            $this->visibleColumns = array_values(
                array_diff($this->visibleColumns, ['teacher'])
            );
        }
    }

    // ── DataTable contract ────────────────────────────────────────────────────

    protected function getTableDefinition(): string
    {
        return ClassroomAttendanceTableConfig::class;
    }

    public function paginationView(): string
    {
        return 'pagination.orvian-ledger';
    }

    public function paginationSimpleView(): string
    {
        return 'pagination.orvian-ledger';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function clearDateRange(): void
    {
        $this->filters['date_from'] = '';
        $this->filters['date_to']   = '';
        $this->resetPage();
    }

    public function exportExcel()
    {
        $fileName = 'historial-aula-' . date('Y-m-d') . '.xlsx';

        return Excel::download(
            new ClassroomAttendanceExport(
                schoolId:     Auth::user()->school_id,
                filters:      $this->filters,
                teacherScope: $this->teacherScope,
            ),
            $fileName
        );
    }

    // ── Filter chip labels ────────────────────────────────────────────────────

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'section_id' => SchoolSection::find($value)?->full_label ?? $value,
            'subject_id' => Subject::find($value)?->name ?? $value,
            'teacher_id' => Teacher::find($value)?->full_name ?? $value,
            'status'     => ClassroomAttendanceRecord::STATUS_LABELS[$value] ?? $value,
            default      => parent::formatFilterValue($key, $value),
        };
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $schoolId = Auth::user()->school_id;

        $baseQuery = ClassroomAttendanceRecord::where('school_id', $schoolId)
            ->with([
                'student:id,first_name,last_name,photo_path,school_section_id,rnc',
                'teacher:id,first_name,last_name',
                'assignment.subject:id,name,code,color',
                'assignment.section' => fn ($q) => $q->with('grade:id,name'),
            ]);

        // Los maestros solo pueden ver sus propios registros; no es bypasseable por URL
        if ($this->teacherScope) {
            $baseQuery->where('teacher_id', $this->teacherScope);
        }

        $records = (new ClassroomAttendanceFilters($this->filters))
            ->apply($baseQuery)
            ->orderByDesc('date')
            ->orderBy('class_time')
            ->paginate($this->perPage);

            /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.classroom-attendance-history', [
            'records'        => $records,
            'isTeacher'      => (bool) $this->teacherScope,
            'sectionOptions' => SchoolSection::withFullRelations()->get()
                ->pluck('full_label', 'id')
                ->toArray(),
            'subjectOptions' => Subject::whereHas(
                'teacherAssignments',
                fn ($q) => $q->where('school_id', $schoolId)
            )->active()->orderBy('name')->get()->pluck('name', 'id')->toArray(),
            'teacherOptions' => $this->teacherScope ? [] : Teacher::where('school_id', $schoolId)
                ->active()
                ->orderBy('first_name')
                ->get()
                ->pluck('full_name', 'id')
                ->toArray(),
        ]);

        return $view->layout('layouts.app-module', config('modules.asistencia'));
    }
}
