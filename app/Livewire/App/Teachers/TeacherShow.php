<?php

namespace App\Livewire\App\Teachers;

use App\Models\Tenant\Teacher;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class TeacherShow extends Component
{
    public Teacher $teacher;
    public string $activeTab = 'perfil';

    // Datos para credenciales
    public string $email = '';
    public string $password = '';

    public function mount(Teacher $teacher): void
    {
        // Cargamos el usuario y las asignaciones con sus respectivas relaciones (materia y sección)
        $this->teacher = $teacher->load([
            'user', 
            'assignments.subject', 
            'assignments.section'
        ]);

        if ($this->teacher->user) {
            $this->email = $this->teacher->user->email;
        }
    }

    public function updateCredentials(): void
    {
        $this->validate([
            'email' => 'required|email|unique:users,email,' . ($this->teacher->user_id ?? 'NULL'),
            'password' => 'nullable|min:8',
        ]);

        if ($this->teacher->user) {
            $this->teacher->user->email = $this->email;
            
            if (!empty($this->password)) {
                $this->teacher->user->password = Hash::make($this->password);
            }
            
            $this->teacher->user->save();

            $this->dispatch('notify', 
                type: 'success', 
                title: 'Credenciales Actualizadas', 
                message: 'El acceso del maestro ha sido actualizado correctamente.'
            );
            
            $this->password = ''; // Limpiar el campo por seguridad
        }
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.teachers.teacher-show');

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }
}