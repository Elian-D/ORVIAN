<?php

namespace App\Livewire\App\Academic\Students;

use App\Models\Tenant\Student;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\ClassroomAttendanceRecord;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class StudentShow extends Component
{
    use WithPagination;

    public Student $student;
    public string $activeTab = 'perfil';
    public string $attendancePeriod = '30'; // '7' | '30' | '90'

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


    #[Computed]
    public function plantelAttendanceSummary(): array
    {
        $days  = (int) $this->attendancePeriod;
        $from  = Carbon::now()->subDays($days)->startOfDay();

        $records = PlantelAttendanceRecord::where('student_id', $this->student->id)
            ->where('date', '>=', $from)
            ->get();

        $total   = $records->count();
        $present = $records->whereIn('status', ['present', 'late'])->count();
        $absent  = $records->where('status', 'absent')->count();
        $excused = $records->where('status', 'excused')->count();

        return [
            'total'      => $total,
            'present'    => $present,
            'absent'     => $absent,
            'excused'    => $excused,
            'rate'       => $total > 0 ? round(($present / $total) * 100, 1) : null,
            'late'       => $records->where('status', 'late')->count(),
        ];
    }

    #[Computed]
    public function classroomAttendanceSummary(): array
    {
        $days = (int) $this->attendancePeriod;
        $from = Carbon::now()->subDays($days)->startOfDay();

        $records = ClassroomAttendanceRecord::where('student_id', $this->student->id)
            ->where('date', '>=', $from)
            ->get();

        $total   = $records->count();
        $present = $records->whereIn('status', ['present', 'late'])->count();
        $absent  = $records->where('status', 'absent')->count();

        return [
            'total'   => $total,
            'present' => $present,
            'absent'  => $absent,
            'rate'    => $total > 0 ? round(($present / $total) * 100, 1) : null,
        ];
    }

    /**
     * Renderizado con el layout de módulo específico
     */
    public function render()
    {
        return view('livewire.app.academic.students.student-show');
    }
}