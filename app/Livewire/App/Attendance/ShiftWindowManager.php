<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\Academic\SchoolShift;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ShiftWindowManager extends Component
{
    use AuthorizesRequests;

    public ?int    $selectedShiftId         = null;
    public string  $newLateThresholdMinutes = '';
    public string  $newStartTime            = ''; // Nueva propiedad

    #[Computed]
    public function shifts(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolShift::where('school_id', Auth::user()->school_id)->get();
    }

    #[Computed]
    public function selectedShift(): ?SchoolShift
    {
        return $this->selectedShiftId ? SchoolShift::find($this->selectedShiftId) : null;
    }

    #[Computed]
    public function impactDescription(): string
    {
        // Verificamos tanda y hora nueva, y que el umbral sea un valor numérico (incluyendo 0)
        if (!$this->selectedShift || empty($this->newStartTime) || !is_numeric($this->newLateThresholdMinutes)) return '';

        $shiftName = $this->selectedShift->type;
        $threshold = (int) $this->newLateThresholdMinutes;

        // Cálculo de tiempos nuevos
        $newTimeFull = Carbon::parse($this->newStartTime)
            ->addMinutes($threshold)
            ->format('h:i A');
        
        // Cálculo de tiempos antiguos
        $oldTimeFull = Carbon::parse($this->selectedShift->start_time)
            ->addMinutes($this->selectedShift->late_threshold_minutes ?? 0)
            ->format('h:i A');

        $oldStartTime = Carbon::parse($this->selectedShift->start_time)->format('h:i A');
        $newStartTime = Carbon::parse($this->newStartTime)->format('h:i A');

        // Mensaje dinámico si la tolerancia es 0
        $lateMessage = $threshold === 0 
            ? "serán marcados como \"Tarde\" inmediatamente después de las <span class='font-bold'>{$newTimeFull}</span>"
            : "serán marcados como \"Tarde\" después de las <span class='font-bold'>{$newTimeFull}</span>";

        return "Atención: La entrada de la <span class='underline decoration-orvian-orange underline-offset-4 font-bold'>Tanda {$shiftName}</span> cambiará de las " .
            "<span class='font-bold'>{$oldStartTime}</span> a las <span class='font-bold'>{$newStartTime}</span>. " .
            "Los estudiantes {$lateMessage} (anteriormente las <span class='font-bold'>{$oldTimeFull}</span>).";
    }



    // Al seleccionar una tanda, cargamos los valores actuales
    public function updatedSelectedShiftId()
    {
        if ($this->selectedShift) {
            $this->newStartTime = Carbon::parse($this->selectedShift->start_time)->format('H:i');
            $this->newLateThresholdMinutes = (string) $this->selectedShift->late_threshold_minutes;
        }
    }

    public function applyAdjustment(): void
    {
        $this->authorize('settings.update');

        // Validación: 'required' asegura que no sea null o vacío, 'numeric' permite 0
        // Si falla, Livewire lanzará una excepción que podemos capturar o manejar
        try {
            $this->validate([
                'selectedShiftId'         => 'required|exists:school_shifts,id',
                'newLateThresholdMinutes' => 'required|numeric|min:0|max:120',
                'newStartTime'            => 'required|date_format:H:i',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('notify', 
                type: 'error', 
                title: 'Campo incompleto', 
                message: 'La tolerancia de entrada es obligatoria y no puede quedar vacía.'
            );
            return;
        }

        $shift = SchoolShift::findOrFail($this->selectedShiftId);
        $shift->update([
            'late_threshold_minutes' => (int) $this->newLateThresholdMinutes,
            'start_time'             => $this->newStartTime,
        ]);

        $this->dispatch('notify', type: 'success', title: '¡Éxito!', message: "Configuración actualizada correctamente.");
        
        $this->selectedShiftId = null; 
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.shift-window-manager');

        return $view->layout('layouts.app-module', config('modules.asistencia'));
    }
}