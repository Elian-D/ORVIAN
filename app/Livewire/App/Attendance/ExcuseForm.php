<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\AttendanceExcuse;
use App\Models\Tenant\Student;
use App\Services\Attendance\ExcuseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Excusa')]
class ExcuseForm extends Component
{
    use WithFileUploads;

    public ?AttendanceExcuse $excuse = null;
    public bool $isEdit = false;

    // ── Propiedades del Formulario ─────────────────────────────────
    public $student_id = '';
    public string $type      = AttendanceExcuse::TYPE_PERSONAL;
    public string $dateStart = '';
    public string $dateEnd   = '';
    public string $reason    = '';
    public $attachment       = null;
    public ?string $existingAttachmentPath = null;

    public function mount(?AttendanceExcuse $excuse = null): void
    {
        if ($excuse && $excuse->exists) {
            abort_if(! $excuse->isPending(), 403, 'Solo se pueden editar excusas pendientes.');

            $this->excuse     = $excuse;
            $this->isEdit     = true;
            $this->student_id = $excuse->student_id;
            $this->type       = $excuse->type;
            $this->dateStart  = $excuse->date_start->format('Y-m-d');
            $this->dateEnd    = $excuse->date_end->format('Y-m-d');
            $this->reason     = $excuse->reason;
            $this->existingAttachmentPath = $excuse->attachment_path;

            return;
        }

        if ($this->type === AttendanceExcuse::TYPE_PERSONAL) {
            $this->applyAutomaticPersonalRange();
        }
    }

    public function updatedType(): void
    {
        if ($this->type === AttendanceExcuse::TYPE_PERSONAL) {
            $this->applyAutomaticPersonalRange();
        } else {
            $this->dateStart = '';
            $this->dateEnd   = '';
        }
    }

    /**
     * La excusa personal cubre un solo día — hoy hasta mañana — y nunca se
     * captura manualmente, sin importar el rol que la esté creando.
     */
    private function applyAutomaticPersonalRange(): void
    {
        $this->dateStart = today()->toDateString();
        $this->dateEnd   = today()->addDay()->toDateString();
    }

    protected function rules(): array
    {
        $isMedical = $this->type === AttendanceExcuse::TYPE_MEDICAL;

        $rules = [
            'student_id' => 'required|integer|exists:students,id',
            'reason'     => 'required|string|min:10|max:1000',
        ];

        if ($isMedical) {
            $rules['dateStart']  = 'required|date';
            $rules['dateEnd']    = 'required|date|after:dateStart';
            $rules['attachment'] = $this->existingAttachmentPath
                ? 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
                : 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
        } else {
            $rules['attachment'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }

        return $rules;
    }

    public function save(ExcuseService $service)
    {
        // Solo mientras esté pendiente se puede editar — guard también del
        // lado del servidor, no solo ocultando el botón en la UI.
        if ($this->isEdit) {
            abort_if(! $this->excuse->isPending(), 403, 'Solo se pueden editar excusas pendientes.');
        }

        if ($this->type === AttendanceExcuse::TYPE_PERSONAL) {
            $this->applyAutomaticPersonalRange();
        }

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify',
                type: 'error',
                title: 'Error de validación',
                message: 'Por favor, revisa los campos marcados en rojo.',
            );
            throw $e;
        }

        // canCreateForDate() se evalúa en cada guardado — crear Y editar —
        // porque depende únicamente del propio valor, nunca queda obsoleta.
        // El traslape (hasOverlappingConfirmedExcuse) ya NO se evalúa aquí:
        // vive exclusivamente en ExcuseService::confirmExcuse() (ver REQ-05.7),
        // para no quedar obsoleto si la excusa se edita después de este guardado.
        if (! $service->canCreateForDate(Carbon::parse($this->dateStart))) {
            $this->dispatch('notify',
                type: 'error',
                title: 'Fecha inválida',
                message: 'No se puede registrar una excusa con fecha pasada — se registra en el momento, nunca de forma retroactiva.',
            );
            return;
        }

        $schoolId = Auth::user()->school_id;

        $data = [
            'school_id'  => $schoolId,
            'student_id' => $this->student_id,
            'date_start' => $this->dateStart,
            'date_end'   => $this->dateEnd,
            'type'       => $this->type,
            'reason'     => $this->reason,
        ];

        if ($this->attachment) {
            $data['attachment_path'] = $this->attachment->store(
                "schools/{$schoolId}/excuses",
                'public'
            );
        }

        if ($this->isEdit) {
            $this->excuse->update($data);
        } else {
            $service->submitExcuse($data);
        }

        $message = $this->isEdit ? 'Excusa actualizada correctamente.' : 'Excusa registrada correctamente.';

        return redirect()->route('app.attendance.excuses.index')->with('success', $message);
    }

    public function render()
    {
        $students = Student::active()
            ->select('id', 'first_name', 'last_name', 'photo_path', 'is_active', 'school_section_id')
            ->with([
                'section:id,school_id,label,grade_id,technical_title_id',
                'section.grade:id,name',
                'section.technicalTitle:id,name,short_name',
            ])
            ->orderBy('first_name')
            ->get();

        $selectedStudent = $this->student_id
            ? $students->firstWhere('id', (int) $this->student_id)
            : null;

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.excuse-form', [
            'students'        => $students,
            'selectedStudent' => $selectedStudent,
        ]);

        return $view->layout('layouts.app-module');
    }
}
