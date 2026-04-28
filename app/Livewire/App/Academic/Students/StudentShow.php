<?php

namespace App\Livewire\App\Academic\Students;

use App\Models\Tenant\Student;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class StudentShow extends Component
{
    use WithPagination;

    public Student $student;
    public string $activeTab = 'perfil';

    // Propiedades para el formulario de credenciales
    public $email;
    public $password;

    public function mount(Student $student)
    {
        $this->student = $student->load(['user', 'section']);
        $this->email = $this->student->user->email;
    }

    public function updateCredentials()
    {
        $this->validate([
            'password' => 'nullable|min:6',
        ]);

        $user = $this->student->user;

        if (!empty($this->password)) {
            $user->password = Hash::make($this->password);
            $user->save();
        }

        $this->password = '';

        // Disparar notificación Toast
        $this->dispatch('notify', 
            type: 'success',
            title: 'Credenciales Actualizadas',
            message: 'El acceso del estudiante ha sido modificado correctamente.'
        );
    }

    /**
     * Renderizado con el layout de módulo específico
     */
    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.academic.students.student-show');

        return $view->layout('layouts.app-module', config('modules.academico'));
    }
}