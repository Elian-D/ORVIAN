<?php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Student;
use App\Services\FacialRecognition\FaceEncodingManager;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class BiometricKiosk extends Component
{
    use WithFileUploads;

    // Filtros del grid
    public ?int  $selectedSectionId = null;
    public string $filterBiometric  = '';  // '' | 'with' | 'without'
    public string $search           = '';

    // Enrolamiento
    public ?int  $enrollingStudentId = null;
    public       $capturedPhoto      = null;  // UploadedFile temporal
    public bool  $enrolling          = false;
    public array $enrollResult       = [];

    #[Computed]
    public function sections(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolSection::with(['grade', 'shift'])
            ->where('school_id', Auth::user()->school_id)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($s) => $s->grade->name . $s->label);
    }

    #[Computed]
    public function students(): \Illuminate\Database\Eloquent\Collection
    {
        return Student::query()
            ->where('is_active', true)
            ->when($this->selectedSectionId, fn ($q) =>
                $q->where('school_section_id', $this->selectedSectionId)
            )
            ->when($this->filterBiometric === 'with', fn ($q) =>
                $q->whereNotNull('face_encoding')
            )
            ->when($this->filterBiometric === 'without', fn ($q) =>
                $q->whereNull('face_encoding')
            )
            ->when($this->search, fn ($q) =>
                $q->where(fn ($sq) =>
                    $sq->where('first_name', 'like', "%{$this->search}%")
                       ->orWhere('last_name', 'like', "%{$this->search}%")
                )
            )
            ->with(['section.grade'])
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $students = $this->students;
        return [
            'total'    => $students->count(),
            'enrolled' => $students->whereNotNull('face_encoding')->count(),
            'pending'  => $students->whereNull('face_encoding')->count(),
        ];
    }

    #[Computed]
    public function enrollingStudent(): ?Student
    {
        if (! $this->enrollingStudentId) return null;
        return Student::find($this->enrollingStudentId);
    }

    // BiometricKiosk.php

    public function openEnrollModal(int $studentId): void
    {
        $this->enrollingStudentId = $studentId;
        $this->capturedPhoto      = null;
        $this->enrollResult       = [];
        // Emitir hacia el browser (JS), no hacia otros componentes Livewire
        $this->dispatch('open-biometric-modal');
    }

    public function closeEnrollModal(): void
    {
        $this->enrollingStudentId = null;
        $this->capturedPhoto      = null;
        $this->enrollResult       = [];
        $this->dispatch('close-biometric-modal');
    }

    public function enroll(FaceEncodingManager $manager): void
    {
        if (! $this->capturedPhoto || ! $this->enrollingStudentId) {
            $this->dispatch('notify', type: 'error', message: 'Captura una foto primero.');
            return;
        }

        $this->enrolling = true;

        $student = Student::findOrFail($this->enrollingStudentId);

        $success = $manager->enrollStudent($student, $this->capturedPhoto);

        $this->enrolling = false;

        if ($success) {
            $this->enrollResult = ['success' => true, 'message' => 'Biometría registrada correctamente.'];
            unset($this->students, $this->stats);
            // Cerrar modal después de 1.5 segundos (lo hace Alpine en la vista)
            $this->dispatch('enroll-success');
        } else {
            $this->enrollResult = [
                'success' => false,
                'message' => 'No se detectó un rostro claro. Intenta de nuevo con mejor iluminación.',
            ];
        }
    }

    public function render()
    {
        return view('livewire.app.academic.biometric-kiosk');
    }
}