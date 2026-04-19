<?php

namespace App\Livewire\App\Attendance;

use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\Student;
use App\Services\Attendance\PlantelAttendanceService;
use App\Services\FacialRecognition\FaceEncodingManager;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class AttendanceScanner extends Component
{
    use WithFileUploads, AuthorizesRequests;

    // ── Estado del Modo ───────────────────────────────────────────
    public string $mode = 'qr'; // 'qr' | 'facial'

    // ── Sesión Activa ─────────────────────────────────────────────
    public ?DailyAttendanceSession $activeSession = null;
    public int $selectedShiftId = 0;
    public bool $sessionLoaded = false;

    // ── Último Estudiante Registrado ──────────────────────────────
    public ?array $lastRegistered = null;

    // ── Historial (últimos 10) ────────────────────────────────────
    public array $recentScans = [];

    // ── Facial Upload ─────────────────────────────────────────────
    public $capturedPhoto = null;
    public bool $isProcessing = false;

    // ── Flash Feedback ────────────────────────────────────────────
    public string $flashType = ''; // 'success' | 'error' | 'warning'
    public string $flashMessage = '';

    // ── Listeners ─────────────────────────────────────────────────
    protected $listeners = ['qrCodeScanned', 'facialCaptureReady'];

    public function mount(): void
    {
        
        $this->authorize('attendance_plantel.record');
        
        // Cargar primera tanda disponible
        $firstShift = Auth::user()->school->shifts()->first();
        $this->selectedShiftId = $firstShift?->id ?? 0;
        
        $this->loadActiveSession();
    }

    /**
     * Carga la sesión activa del día para la tanda seleccionada.
     */
    public function loadActiveSession(): void
    {
        if (!$this->selectedShiftId) {
            $this->sessionLoaded = false;
            return;
        }

        $this->activeSession = DailyAttendanceSession::where('school_id', Auth::user()->school_id)
            ->where('school_shift_id', $this->selectedShiftId)
            ->whereDate('date', today())
            ->active()
            ->withIndexRelations()
            ->first();

        $this->sessionLoaded = true;

        if (!$this->activeSession) {
            $this->dispatch('notify',
                type: 'warning',
                title: 'Sesión no iniciada',
                message: 'No hay una sesión de asistencia abierta para hoy. Contacta al administrador.',
            );
        }
    }

    /**
     * Listener para QR Code escaneado (disparado desde Alpine.js).
     */
    public function qrCodeScanned(string $code): void
    {
        if (!$this->activeSession) {
            $this->showFlash('error', 'No hay sesión abierta');
            return;
        }

        $this->isProcessing = true;

        try {
            $student = Student::where('qr_code', $code)
                ->where('school_id', Auth::user()->school_id)
                ->active()
                ->first();

            if (!$student) {
                $this->showFlash('error', 'Código QR no válido o estudiante inactivo');
                $this->isProcessing = false;
                return;
            }

            // Verificar duplicado
            $existing = PlantelAttendanceRecord::where('student_id', $student->id)
                ->whereDate('date', today())
                ->where('school_shift_id', $this->selectedShiftId)
                ->first();

            if ($existing) {
                $this->showFlash('warning', "⚠️ {$student->full_name} ya registró entrada hoy");
                $this->isProcessing = false;
                return;
            }

            // Registrar asistencia
            $this->recordStudentAttendance($student, PlantelAttendanceRecord::METHOD_QR);

        } catch (\Exception $e) {
            $this->showFlash('error', 'Error: ' . $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Listener para captura facial lista (disparado desde Alpine.js tras auto-captura).
     */
    public function facialCaptureReady(): void
    {
        if (!$this->capturedPhoto) {
            $this->showFlash('error', 'No se recibió la captura');
            return;
        }

        $this->processFacialCapture();
    }

    /**
     * Procesa la foto capturada automáticamente por el frontend.
     */
    public function processFacialCapture(): void
    {
        if (!$this->activeSession) {
            $this->showFlash('error', 'No hay sesión abierta');
            $this->reset('capturedPhoto');
            return;
        }

        $this->isProcessing = true;

        try {
            $faceManager = app(FaceEncodingManager::class);

            // Verificar salud del servicio
            if (!$faceManager->isServiceHealthy()) {
                $this->showFlash('warning', '🔧 Servicio facial no disponible (modo simulación activo)');
            }

            // Identificar estudiante
            $result = $faceManager->identifyStudent(
                Auth::user()->school_id,
                $this->capturedPhoto
            );

            if (!$result) {
                $this->showFlash('error', '😕 Rostro no reconocido. Intenta de nuevo.');
                $this->reset('capturedPhoto');
                $this->isProcessing = false;
                return;
            }

            $student = Student::find($result['student_id']);

            if (!$student || !$student->is_active) {
                $this->showFlash('error', 'Estudiante no encontrado o inactivo');
                $this->reset('capturedPhoto');
                $this->isProcessing = false;
                return;
            }

            // Verificar duplicado
            $existing = PlantelAttendanceRecord::where('student_id', $student->id)
                ->whereDate('date', today())
                ->where('school_shift_id', $this->selectedShiftId)
                ->first();

            if ($existing) {
                $this->showFlash('warning', "⚠️ {$student->full_name} ya registró entrada hoy");
                $this->reset('capturedPhoto');
                $this->isProcessing = false;
                return;
            }

            // Registrar asistencia con metadata de confianza
            $this->recordStudentAttendance($student, PlantelAttendanceRecord::METHOD_FACIAL, [
                'facial_confidence' => $result['confidence'],
                'facial_distance' => $result['distance'],
            ]);

        } catch (\Exception $e) {
            $this->showFlash('error', 'Error: ' . $e->getMessage());
        } finally {
            $this->reset('capturedPhoto');
            $this->isProcessing = false;
        }
    }

    /**
     * REGLA DE ORO: Usar SOLO PlantelAttendanceService para registrar.
     */
    protected function recordStudentAttendance(Student $student, string $method, array $metadata = []): void
    {
        $service = app(PlantelAttendanceService::class);

        $record = $service->recordAttendance([
            'school_id' => Auth::user()->school_id,
            'student_id' => $student->id,
            'date' => today(),
            'time' => now()->format('H:i:s'),
            'school_shift_id' => $this->selectedShiftId,
            'method' => $method,
            'registered_by' => Auth::id(),
            'metadata' => $metadata,
        ]);

        // Actualizar UI
        $this->addToRecentScans($student, $record);
        $this->showFlash('success', "✅ {$student->full_name} registrado correctamente");

        // Notificar éxito
        $this->dispatch('attendance-recorded', [
            'student' => $student->full_name,
            'status' => $record->status_label,
        ]);
    }

    /**
     * Agrega el registro al historial local (máximo 10).
     */
    protected function addToRecentScans(Student $student, PlantelAttendanceRecord $record): void
    {
        $this->lastRegistered = [
            'student_id'   => $student->id,
            'photo'        => $student->photo_path,
            'name'         => $student->full_name,
            'section'      => $student->full_section_name,
            'status'       => $record->status,
            'status_label' => $record->status_label,
            'time'         => $record->time->format('h:i A'),
            'method'       => $record->method_label,
        ];

        array_unshift($this->recentScans, $this->lastRegistered);
        $this->recentScans = array_slice($this->recentScans, 0, 10);
    }

    /**
     * Muestra feedback flash temporal.
     */
    protected function showFlash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;

        $this->dispatch('flash-shown', type: $type, message: $message);
    }

    public function render()
    {
        $studentIds = collect($this->recentScans)->pluck('student_id')->filter()->all();
        $scannedStudents = count($studentIds)
            ? Student::whereIn('id', $studentIds)->get()->keyBy('id')
            : collect();

        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.attendance.attendance-scanner', [
            'shifts'          => Auth::user()->school->shifts,
            'scannedStudents' => $scannedStudents,
        ]);

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }
}