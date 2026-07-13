<?php

use App\Livewire\App\Attendance\AttendanceAudit;
use App\Livewire\App\Attendance\AttendanceReports;
use App\Livewire\App\Attendance\AttendanceDashboard;
use App\Livewire\App\Attendance\AttendanceSessionHub;
use App\Livewire\App\Attendance\AttendanceSessionManager;
use App\Livewire\App\Attendance\ClassroomAttendanceLive;
use App\Livewire\App\Attendance\ClassroomAttendanceHistory;
use App\Livewire\App\Attendance\ExcuseForm;
use App\Livewire\App\Attendance\ExcuseIndex;
use App\Livewire\App\Attendance\ManualAttendance;
use App\Livewire\App\Attendance\PlantelAttendanceIndex;
use App\Livewire\App\Attendance\ShiftWindowManager;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| App Module Attendance Routes
|--------------------------------------------------------------------------
*/

Route::prefix('attendance')->name('attendance.')->group(function () {

    Route::get('/dashboard', AttendanceDashboard::class)
        ->middleware('can:attendance_plantel.reports')
        ->name('dashboard');

    // --- Fase 15: Historial y Reportes ---
    Route::get('/plantel/history', PlantelAttendanceIndex::class)
        ->middleware('can:attendance_plantel.view')
        ->name('plantel.index');

    Route::get('/reports', AttendanceReports::class)
        ->middleware('can:attendance_plantel.reports')
        ->name('reports');

    // --- Hub de Gestión de Sesiones (Nueva ruta) ---
    Route::get('/hub', AttendanceSessionHub::class)
        ->middleware('can:attendance_plantel.view')
        ->name('hub');

    Route::get('/audit/{sessionId}', AttendanceAudit::class)
        ->middleware('can:attendance_plantel.verify')
        ->name('audit');

    // --- Fase 11: Módulo de Excusas ---
    Route::prefix('excuses')->name('excuses.')->group(function () {
        Route::get('/', ExcuseIndex::class)
            ->middleware('can:excuses.view')
            ->name('index');

        Route::get('/create', ExcuseForm::class)
            ->middleware('can:manage_excuses')
            ->name('create');

        Route::get('/{excuse}/edit', ExcuseForm::class)
            ->middleware('can:manage_excuses')
            ->name('edit');
    });

    Route::middleware('can:attendance_plantel.open_session')->group(function () {
        Route::get('/session', AttendanceSessionManager::class)->name('session');
        Route::get('/manual', ManualAttendance::class)->name('manual');
    });

    // --- Fase 10: Asistencia de Aula (Pase de Lista del Maestro) ---
    // Fase 5 (piloto): Aula desactivada por completo (REQ-05.13) — el código
    // queda intacto, solo apagamos el acceso por URL. Descomentar para reactivar.
    // Route::middleware('can:attendance_classroom.record')->group(function () {
    //     Route::get('/classroom', ClassroomAttendanceLive::class)->name('classroom.live');
    // });

    // Route::middleware('can:attendance_classroom.view')->group(function () {
    //     Route::get('/classroom/history', ClassroomAttendanceHistory::class)->name('classroom.history');
    // });

    Route::get('/shift-windows', ShiftWindowManager::class)
    ->name('shift-windows')
    ->middleware('can:settings.update');
});