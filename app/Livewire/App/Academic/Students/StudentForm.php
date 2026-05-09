<?php

namespace App\Livewire\App\Academic\Students;

use App\Models\Tenant\Student;
use App\Models\Tenant\Academic\SchoolSection;
use App\Services\Academic\Students\StudentService;
use App\Services\FacialRecognition\FaceEncodingManager;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentForm extends Component
{
    use WithFileUploads;

    public ?Student $student = null;
    public $isEdit = false;

    // Propiedades del Formulario
    public $first_name, $last_name, $email, $rnc, $gender = 'M';
    public $date_of_birth, $address, $school_section_id;
    public $tutor_name, $tutor_phone;
    public $blood_type, $allergies, $medical_notes;
    public $photo;
    public $is_active = true;

    // Inyectamos el servicio mediante el método save o mount si fuera necesario, 
    // pero en Livewire 3 es más limpio usar la inyección en el método de acción.

    protected function rules()
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'school_section_id' => 'required|exists:school_sections,id',
            'gender' => 'required|in:M,F',
            'date_of_birth' => 'nullable|date',
            'rnc' => 'required|string|min:11|max:15',
            'photo' => 'nullable|image|max:2048',
            'tutor_name' => 'nullable|string|max:120',
            'tutor_phone' => ['nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'blood_type' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function updatedRnc(): void
    {
        if (!$this->isEdit) {
            $clean = preg_replace('/[^0-9]/', '', $this->rnc ?? '');
            $this->email = $clean ? $clean . '@orvian.com.do' : '';
        }
    }

    public function mount(?Student $student = null)
    {
        if ($student && $student->exists) {
            $this->student = $student;
            $this->isEdit = true;
            
            $this->first_name = $student->first_name;
            $this->last_name = $student->last_name;
            $this->email = $student->user->email;
            $this->rnc = $student->rnc;
            $this->gender = $student->gender;
            $this->date_of_birth = $student->date_of_birth?->format('Y-m-d');
            $this->address = $student->address;
            $this->school_section_id = $student->school_section_id;
            $this->tutor_name = $student->tutor_name;
            $this->tutor_phone = $student->tutor_phone;
            $this->blood_type = $student->blood_type;
            $this->allergies = $student->allergies;
            $this->medical_notes = $student->medical_notes;
            $this->is_active = $student->is_active;
        }
    }

    public function save(StudentService $studentService, FaceEncodingManager $faceManager)
    {
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

        $cleanRnc = preg_replace('/[^0-9]/', '', $this->rnc);

        try {
            DB::beginTransaction();

            $currentSchoolId = Auth::user()->school_id;

            if (!$this->isEdit) {
                // Usuario creado automáticamente por StudentObserver@created desde el RNC
                $this->student = $studentService->createStudent([
                    'school_id' => $currentSchoolId,
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'rnc' => $cleanRnc,
                    'gender' => $this->gender,
                    'date_of_birth' => $this->date_of_birth,
                    'address' => $this->address,
                    'school_section_id' => $this->school_section_id,
                    'tutor_name' => $this->tutor_name ?: null,
                    'tutor_phone' => $this->tutor_phone ?: null,
                    'blood_type' => $this->blood_type,
                    'allergies' => $this->allergies,
                    'medical_notes' => $this->medical_notes,
                    'is_active' => $this->is_active,
                ]);
            } else {
                // Actualizar nombre del usuario vinculado — email es inmutable (generado por Observer)
                $this->student->user->update([
                    'name' => "{$this->first_name} {$this->last_name}",
                ]);

                // Actualizar datos básicos del estudiante
                $this->student->update([
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'rnc' => $cleanRnc,
                    'gender' => $this->gender,
                    'date_of_birth' => $this->date_of_birth,
                    'address' => $this->address,
                    'school_section_id' => $this->school_section_id,
                    'tutor_name' => $this->tutor_name ?: null,
                    'tutor_phone' => $this->tutor_phone ?: null,
                    'blood_type' => $this->blood_type,
                    'allergies' => $this->allergies,
                    'medical_notes' => $this->medical_notes,
                    'is_active' => $this->is_active,
                ]);
            }

            // 3. Guardar foto
            if ($this->photo) {
                $studentService->updatePhoto($this->student, $this->photo);
            }

            DB::commit();

            // 4. Generar face encoding (fuera de la transacción para no bloquear el guardado)
            if ($this->photo) {
                $faceManager->enrollStudent($this->student, $this->photo);
            }

            $message = $this->isEdit ? "Perfil actualizado." : "Estudiante registrado correctamente.";
            return redirect()->route('app.academic.students.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify',
                type: 'error',
                title: 'Error de sistema',
                message: 'No se pudo procesar: ' . $e->getMessage(),
            );
        }
    }

    public function render()
    {

        $sections = SchoolSection::with(['grade', 'shift', 'technicalTitle'])
            ->get()
            ->pluck('fullLabel', 'id');
        
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.academic.students.student-form', [
            'sections' => $sections,
        ]);

        return $view->layout('layouts.app-module', config('modules.academico'));
    }
}