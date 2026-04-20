<?php

namespace App\Livewire\App\Attendance;

use Livewire\Component;

class ClassroomAttendanceHistory extends Component
{
    public function render()
    {
        return view('livewire.app.attendance.classroom-attendance-history')
            ->layout('layouts.app-module', config('modules.configuracion'));
    }
}
