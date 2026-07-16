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
        ->name('dashboard')
        ->defaults('navigationSearch', [
            'title'       => 'Dashboard de Asistencia',
            'description' => 'Métricas y resumen general de asistencia',
            'keywords'    => ['estadísticas', 'resumen', 'gráficos', 'faltas', 'kiosko'],
        ]);

    // --- Fase 15: Historial y Reportes ---
    Route::get('/plantel/history', PlantelAttendanceIndex::class)
        ->middleware('can:attendance_plantel.view')
        ->name('plantel.index')
        ->defaults('navigationSearch', [
            'title'       => 'Historial de Plantel',
            'description' => 'Registros de entrada y verificación de asistencia institucional',
            'keywords'    => ['historial', 'registros', 'entradas', 'plantel'],
        ]);

    Route::get('/reports', AttendanceReports::class)
        ->middleware('can:attendance_plantel.reports')
        ->name('reports')
        ->defaults('navigationSearch', [
            'title'       => 'Reportes de Asistencia',
            'description' => 'Genera reportes por período o por estudiante',
            'keywords'    => ['reporte', 'exportar', 'excel', 'pdf', 'resumen general', 'por estudiante'],
        ]);

    // --- Hub de Gestión de Sesiones (Nueva ruta) ---
    Route::get('/hub', AttendanceSessionHub::class)
        ->middleware('can:attendance_plantel.view')
        ->name('hub')
        ->defaults('navigationSearch', [
            'title'       => 'Control Diario de Asistencia',
            'description' => 'Abre, cierra y supervisa la sesión de asistencia del día',
            'keywords'    => ['sesión', 'abrir', 'cerrar', 'diario', 'hoy'],
        ]);

    Route::get('/audit/{sessionId}', AttendanceAudit::class)
        ->middleware('can:attendance_plantel.verify')
        ->name('audit');

    // --- Fase 11: Módulo de Excusas ---
    Route::prefix('excuses')->name('excuses.')->group(function () {
        Route::get('/', ExcuseIndex::class)
            ->middleware('can:excuses.view')
            ->name('index')
            ->defaults('navigationSearch', [
                'title'       => 'Excusas',
                'description' => 'Listado y aprobación de excusas de asistencia',
                'keywords'    => ['justificaciones', 'aprobar', 'rechazar', 'ausencias'],
            ]);

        Route::get('/create', ExcuseForm::class)
            ->middleware('can:manage_excuses')
            ->name('create')
            ->defaults('navigationSearch', [
                'title'       => 'Registrar Nueva Excusa',
                'description' => 'Justificar la ausencia o salida temprana de un estudiante',
                'keywords'    => ['enfermo', 'cita', 'justificar', 'ausencia', 'falta', 'médica'],
            ]);

        Route::get('/{excuse}/edit', ExcuseForm::class)
            ->middleware('can:manage_excuses')
            ->name('edit');
    });

    Route::middleware('can:attendance_plantel.open_session')->group(function () {
        Route::get('/session', AttendanceSessionManager::class)
            ->name('session')
            ->defaults('navigationSearch', [
                'title'       => 'Sesión del Día',
                'description' => 'Abrir o cerrar la ventana de registro de asistencia',
                'keywords'    => ['abrir sesión', 'cerrar sesión', 'tanda'],
            ]);

        Route::get('/manual', ManualAttendance::class)
            ->name('manual')
            ->defaults('navigationSearch', [
                'title'       => 'Registro Manual de Asistencia',
                'description' => 'Marcar asistencia de un estudiante a mano',
                'keywords'    => ['manual', 'marcar', 'registrar asistencia'],
            ]);
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
        ->middleware('can:settings.update')
        ->defaults('navigationSearch', [
            'title'       => 'Configuración Horaria',
            'description' => 'Ventanas de entrada, tardanza y cierre por tanda',
            'keywords'    => ['tandas', 'horario', 'entrada', 'tardanza', 'cierre'],
        ]);
});
