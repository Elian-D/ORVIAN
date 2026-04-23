<?php

namespace App\Livewire\App\Attendance;

use App\Exports\PlantelAttendanceExport;
use App\Filters\App\Attendance\Plantel\PlantelAttendanceFilters;
use App\Livewire\Base\DataTable;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Tables\App\Attendance\PlantelAttendanceTableConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Facades\Excel;

class PlantelAttendanceIndex extends DataTable
{
    use AuthorizesRequests;

    #[Url]
    public array $filters = [
        'search'     => '',
        'date_from'  => '',
        'date_to'    => '',
        'section_id' => '',
        'status'     => '',
        'method'     => '',
        'verified'   => '',
    ];

    // ── Modal state ───────────────────────────────────────────────────────────

    public bool $showEditModal    = false;
    public ?int $selectedRecordId = null;
    public string $editStatus     = '';
    public string $editNotes      = '';

    // ── DataTable contract ────────────────────────────────────────────────────

    protected function getTableDefinition(): string
    {
        return PlantelAttendanceTableConfig::class;
    }

    public function paginationView(): string
    {
        return 'pagination.orvian-ledger';
    }

    public function paginationSimpleView(): string
    {
        return 'pagination.orvian-ledger';
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    #[Computed]
    public function selectedRecord(): ?PlantelAttendanceRecord
    {
        if (! $this->selectedRecordId) {
            return null;
        }

        return PlantelAttendanceRecord::with([
            'student:id,first_name,last_name',
        ])->find($this->selectedRecordId);
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function openEdit(int $recordId): void
    {
        $this->authorize('attendance_plantel.verify');

        $record = PlantelAttendanceRecord::where('school_id', Auth::user()->school_id)
            ->findOrFail($recordId);

        $this->selectedRecordId = $recordId;
        $this->editStatus       = $record->status;
        $this->editNotes        = $record->notes ?? '';
        $this->showEditModal    = true;
    }

    public function saveEdit(): void
    {
        $this->authorize('attendance_plantel.verify');

        $this->validate([
            'editStatus' => 'required|in:present,late,absent,excused',
            'editNotes'  => 'nullable|string|max:500',
        ]);

        PlantelAttendanceRecord::where('school_id', Auth::user()->school_id)
            ->findOrFail($this->selectedRecordId)
            ->update([
                'status' => $this->editStatus,
                'notes'  => $this->editNotes ?: null,
            ]);

        $this->closeModals();
        $this->dispatch('toast', type: 'success', message: 'Registro actualizado correctamente.');
    }

    public function verify(int $recordId): void
    {
        $this->authorize('attendance_plantel.verify');

        PlantelAttendanceRecord::where('school_id', Auth::user()->school_id)
            ->findOrFail($recordId)
            ->update([
                'verified_at' => now(),
                'verified_by' => Auth::id(),
            ]);

        $this->dispatch('toast', type: 'success', message: 'Registro verificado.');
    }

    public function clearDateRange(): void
    {
        $this->filters['date_from'] = '';
        $this->filters['date_to']   = '';
        $this->resetPage();
    }

    public function exportExcel()
    {
        $fileName = 'historial-plantel-' . date('Y-m-d') . '.xlsx';

        return Excel::download(
            new PlantelAttendanceExport(Auth::user()->school_id, $this->filters),
            $fileName
        );
    }

    public function exportPdf()
    {
        $school  = Auth::user()->school;
        $records = (new PlantelAttendanceFilters($this->filters))
            ->apply(
                PlantelAttendanceRecord::where('school_id', $school->id)
                    ->with([
                        'student:id,first_name,last_name,rnc,school_section_id',
                        'student.section' => fn ($q) => $q->with('grade:id,name'),
                        'shift:id,type',
                        'registeredBy:id,name',
                    ])
            )
            ->orderByDesc('date')
            ->get();

        $logoPath   = $school->logo_path ? storage_path('app/public/' . $school->logo_path) : null;
        $logoBase64 = ($logoPath && file_exists($logoPath))
            ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $present = $records->whereIn('status', ['present', 'late'])->count();
        $total   = $records->count();

        $pdf = Pdf::loadView('reports.attendance-plantel', [
            'school'     => $school,
            'records'    => $records,
            'dateFrom'   => $this->filters['date_from'] ?: date('Y-m-d'),
            'dateTo'     => $this->filters['date_to']   ?: date('Y-m-d'),
            'logoBase64' => $logoBase64,
            'meta'       => [
                'total'   => $total,
                'present' => $present,
                'late'    => $records->where('status', 'late')->count(),
                'absent'  => $records->where('status', 'absent')->count(),
                'excused' => $records->where('status', 'excused')->count(),
                'rate'    => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ],
        ])->setPaper('letter', 'landscape');

        $fileName = 'historial-plantel-' . date('Y-m-d') . '.pdf';

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function closeModals(): void
    {
        $this->showEditModal    = false;
        $this->selectedRecordId = null;
        $this->editStatus       = '';
        $this->editNotes        = '';
    }

    // ── Filter chip labels ────────────────────────────────────────────────────

    protected function formatFilterValue(string $key, mixed $value): string
    {
        return match ($key) {
            'section_id' => SchoolSection::find($value)?->full_label ?? $value,
            'status'     => PlantelAttendanceRecord::STATUS_LABELS[$value] ?? $value,
            'method'     => PlantelAttendanceRecord::METHOD_LABELS[$value] ?? $value,
            'verified'   => $value === 'yes' ? 'Verificados' : 'Pendientes',
            default      => parent::formatFilterValue($key, $value),
        };
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $records = (new PlantelAttendanceFilters($this->filters))
            ->apply(
                PlantelAttendanceRecord::where('school_id', Auth::user()->school_id)
                    ->with([
                        'student:id,first_name,last_name,photo_path,school_section_id,rnc',
                        'student.section' => fn ($q) => $q->with('grade:id,name'),
                        'shift:id,type',
                        'registeredBy:id,name',
                        'verifiedBy:id,name',
                    ])
            )
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate($this->perPage);

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.plantel-attendance-index', [
            'records'        => $records,
            'sectionOptions' => SchoolSection::withFullRelations()->get()
                ->pluck('full_label', 'id')
                ->toArray(),
        ]);

        return $view->layout('layouts.app-module', config('modules.asistencia'));
    }
}
