<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\AttendanceExcuse;
use App\Models\Tenant\Student;
use App\Services\Attendance\ExcuseService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class ExcuseManager extends Component
{
    use WithFileUploads;

    // ── Formulario ────────────────────────────────────────────────
    public int    $studentId    = 0;
    public string $dateStart    = '';
    public string $dateEnd      = '';
    public string $type         = AttendanceExcuse::TYPE_FULL_ABSENCE;
    public string $reason       = '';
    public        $attachment   = null;

    // ── UI ────────────────────────────────────────────────────────
    public bool   $showPanel    = false;
    public ?int   $excuseId     = null; // Para aprobación/rechazo
    public string $reviewNotes  = '';
    public bool   $showReview   = false;
    public string $reviewAction = ''; // 'approve' | 'reject'

    // ── Filtros de listado ────────────────────────────────────────
    public string $filterStatus = '';
    public string $search       = '';

    protected function rules(): array
    {
        return [
            'studentId'  => 'required|integer|exists:students,id',
            'dateStart'  => 'required|date',
            'dateEnd'    => 'required|date|after_or_equal:dateStart',
            'type'       => 'required|string',
            'reason'     => 'required|string|min:10|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['studentId', 'dateStart', 'dateEnd', 'type', 'reason', 'attachment', 'excuseId']);
        $this->showPanel = true;
    }

    public function save(ExcuseService $service): void
    {
        $this->validate();
        $this->authorize('excuses.submit');

        $data = [
            'school_id'  => Auth::user()->school_id,
            'student_id' => $this->studentId,
            'date_start' => $this->dateStart,
            'date_end'   => $this->dateEnd,
            'type'       => $this->type,
            'reason'     => $this->reason,
        ];

        if ($this->attachment) {
            $data['attachment_path'] = $this->attachment->store('excuses', 'public');
        }

        $service->submitExcuse($data);

        $this->showPanel = false;
        $this->dispatch('toast', type: 'success', message: 'Excusa registrada. Pendiente de revisión.');
    }

    public function openReview(int $excuseId, string $action): void
    {
        $this->authorize('excuses.review');
        $this->excuseId     = $excuseId;
        $this->reviewAction = $action;
        $this->reviewNotes  = '';
        $this->showReview   = true;
    }

    public function confirmReview(ExcuseService $service): void
    {
        $this->authorize('excuses.review');

        $excuse = AttendanceExcuse::findOrFail($this->excuseId);

        if ($this->reviewAction === 'approve') {
            $service->approveExcuse($excuse, $this->reviewNotes ?: null);
            $message = 'Excusa aprobada. Registros de asistencia actualizados automáticamente.';
        } else {
            $this->validate(['reviewNotes' => 'required|string|min:5']);
            $service->rejectExcuse($excuse, $this->reviewNotes);
            $message = 'Excusa rechazada.';
        }

        $this->showReview = false;
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function render()
    {
        $excuses = AttendanceExcuse::with(['student:id,first_name,last_name', 'submittedBy:id,name'])
            ->where('school_id', Auth::user()->school_id)
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, function ($q) {
                $q->whereHas('student', fn ($s) =>
                    $s->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                );
            })
            ->orderBy('submitted_at', 'desc')
            ->paginate($this->perPage);
        
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.excuse-manager', [
            'excuses'       => $excuses,
            'students'      => Student::active()->select('id', 'first_name', 'last_name')->get(),
            'typeOptions'   => AttendanceExcuse::TYPE_LABELS,
            'statusOptions' => AttendanceExcuse::STATUS_LABELS,
        ]);

        return $view->layout('layouts.app-module', config('modules.asistencia'));
    }
}