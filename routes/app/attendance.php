<?php

use App\Livewire\App\Attendance\AttendanceAudit;
use App\Livewire\App\Attendance\AttendanceSessionHub;
use App\Livewire\App\Attendance\AttendanceSessionManager;
use App\Livewire\App\Attendance\ClassroomAttendanceLive;
use App\Livewire\App\Attendance\ExcuseIndex;
use App\Livewire\App\Attendance\ManualAttendance;
use Illuminate\Support\Facades\Route;
use App\Livewire\App\Attendance\AttendanceScanner;

/*
|--------------------------------------------------------------------------
| App Module Attendance Routes
|--------------------------------------------------------------------------
*/

Route::prefix('attendance')->name('attendance.')->group(function () {
    


    Route::middleware('can:attendance_plantel.record')->group(function () {
        Route::get('/scanner', AttendanceScanner::class)
            ->name('scanner');
    });


    // --- Hub de Gestión de Sesiones (Nueva ruta) ---
    Route::get('/hub', AttendanceSessionHub::class)
        ->middleware('can:attendance_plantel.view')
        ->name('hub');

    // Agregar dentro del grupo Route::prefix('attendance')->name('attendance.')

    Route::get('/audit/{sessionId}', AttendanceAudit::class)
        ->middleware('can:attendance_plantel.verify')
        ->name('audit');

    // --- Fase 11: Módulo de Excusas ---
    Route::prefix('excuses')->name('excuses.')->group(function () {
        Route::get('/', ExcuseIndex::class)
            ->middleware('can:excuses.view')
            ->name('index');
    });

    Route::middleware('can:attendance_plantel.open_session')->group(function () {
        Route::get('/session', AttendanceSessionManager::class)->name('session');
        Route::get('/manual', ManualAttendance::class)->name('manual');
    });

    // --- Fase 9: Asistencia de Salón (Classroom) ---
    Route::get('/classroom/{assignmentId}', ClassroomAttendanceLive::class)
        ->middleware('can:attendance_classroom.view')
        ->name('classroom.index');
});