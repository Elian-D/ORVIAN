<?php

namespace App\Livewire\App\Students;

use App\Models\User;
use App\Models\Tenant\Student;
use App\Models\Tenant\Academic\SchoolSection;
use App\Services\Students\StudentService; // Importamos el servicio
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentForm extends Component
{
    use WithFileUploads;

    public ?Student $student = null;
    public $isEdit = false;

    // Propiedades del Formulario
    public $first_name, $last_name, $email, $rnc, $gender = 'M';
    public $date_of_birth, $address, $school_section_id;
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
            'email' => 'required|email|unique:users,email,' . ($this->student?->user_id ?? 'NULL'),
            'school_section_id' => 'required|exists:school_sections,id',
            'gender' => 'required|in:M,F',
            'date_of_birth' => 'nullable|date',
            'rnc' => 'required|string|min:11|max:15', 
            'photo' => 'nullable|image|max:2048', // 2MB máximo
            'blood_type' => 'nullable|string',
            'is_active' => 'boolean',
        ];
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
            $this->blood_type = $student->blood_type;
            $this->allergies = $student->allergies;
            $this->medical_notes = $student->medical_notes;
            $this->is_active = $student->is_active;
        }
    }

    public function save(StudentService $studentService) // Inyección del servicio
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
                // 1. Crear Usuario
                $password = !empty($cleanRnc) ? $cleanRnc : '123456';
                $user = User::create([
                    'name' => "{$this->first_name} {$this->last_name}",
                    'email' => $this->email,
                    'password' => Hash::make($password),
                    'school_id' => $currentSchoolId,
                    'status' => 'active',
                ]);
                
                setPermissionsTeamId($currentSchoolId);
                $user->assignRole('student');

                // 2. Crear Estudiante usando el Servicio (Aquí se genera el QR automáticamente)
                $this->student = $studentService->createStudent([
                    'user_id' => $user->id,
                    'school_id' => $currentSchoolId,
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'rnc' => $cleanRnc,
                    'gender' => $this->gender,
                    'date_of_birth' => $this->date_of_birth,
                    'address' => $this->address,
                    'school_section_id' => $this->school_section_id,
                    'blood_type' => $this->blood_type,
                    'allergies' => $this->allergies,
                    'medical_notes' => $this->medical_notes,
                    'is_active' => $this->is_active,
                ]);
            } else {
                // Actualizar usuario vinculado
                $this->student->user->update([
                    'name' => "{$this->first_name} {$this->last_name}",
                    'email' => $this->email,
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
                    'blood_type' => $this->blood_type,
                    'allergies' => $this->allergies,
                    'medical_notes' => $this->medical_notes,
                    'is_active' => $this->is_active,
                ]);
            }

            // 3. Procesar Foto a través del Servicio
            if ($this->photo) {
                $studentService->updatePhoto($this->student, $this->photo);
                
                // NOTA: Cuando implementes la Fase 13, dentro de updatePhoto() 
                // llamarás al microservicio de Python para generar el encoding.
            }

            DB::commit();

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
        $view = view('livewire.app.students.student-form', [
            'sections' => $sections,
        ]);

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }
}