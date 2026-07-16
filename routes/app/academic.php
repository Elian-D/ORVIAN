<?php

use App\Http\Controllers\App\Students\StudentPrintController;
use App\Livewire\App\Academic\AcademicBuilder;
use App\Livewire\App\Academic\BiometricKiosk;
use Illuminate\Support\Facades\Route;
use App\Livewire\App\Academic\Students\StudentIndex;
use App\Livewire\App\Academic\Students\StudentShow;
use App\Livewire\App\Academic\Students\StudentForm;
use App\Livewire\App\Academic\Students\StudentPrintManager;
use App\Livewire\App\Academic\Students\StudentImportWizard;
use App\Livewire\App\Academic\Teachers\TeacherIndex;
use App\Livewire\App\Academic\Teachers\TeacherShow;
use App\Livewire\App\Academic\Teachers\TeacherForm;
use App\Livewire\App\Academic\Teachers\TeacherAssignments;
use App\Livewire\App\Academic\CourseIndex;
use App\Livewire\App\Academic\CourseForm;
use App\Livewire\App\Academic\CourseShow;
use App\Livewire\App\Academic\EnrollmentHub;

/*
|--------------------------------------------------------------------------
| Módulo de Gestión Académica
|--------------------------------------------------------------------------
| Prefijo resultante: /app/academic/...
| Nombre resultante: app.academic....
*/

Route::prefix('academic')->name('academic.')->group(function () {

    // -------------------------------------------------------------------------
    // RUTAS DE GESTIÓN DE ESTUDIANTES
    // -------------------------------------------------------------------------

    Route::middleware('can:students.view')->group(function () {
        Route::get('/students', StudentIndex::class)->name('students.index')
            ->defaults('navigationSearch', [
                'title'       => 'Estudiantes',
                'description' => 'Expediente académico, datos personales y biometría',
                'keywords'    => ['alumnos', 'expediente', 'listado'],
            ]);

        // 1. PRIMERO LAS RUTAS ESTÁTICAS
        Route::get('/students/create', StudentForm::class)->name('students.create')->middleware('can:students.create')
            ->defaults('navigationSearch', [
                'title'       => 'Crear Estudiante',
                'description' => 'Registrar un nuevo estudiante en el centro',
                'keywords'    => ['nuevo estudiante', 'agregar alumno', 'inscribir'],
            ]);

        Route::get('/students/import', StudentImportWizard::class)
            ->middleware('can:students.import')
            ->name('students.import')
            ->defaults('navigationSearch', [
                'title'       => 'Importar Estudiantes',
                'description' => 'Carga masiva de estudiantes vía Excel',
                'keywords'    => ['excel', 'masivo', 'carga', 'importación'],
            ]);

        Route::get('/students/print-manager', StudentPrintManager::class)
            ->middleware('can:students.import')
            ->name('students.print-manager')
            ->defaults('navigationSearch', [
                'title'       => 'Gestión de Carnets',
                'description' => 'Imprimir carnets y hojas de códigos QR de estudiantes',
                'keywords'    => ['carnet', 'imprimir', 'qr', 'identificación'],
            ]);

        Route::get('/students/print-qr-sheet', [StudentPrintController::class, 'printQrSheet'])
            ->middleware('can:students.import')
            ->name('students.print-qr-sheet');

        // 2. ÚLTIMO LAS RUTAS CON PARÁMETROS ({student})
        Route::get('/students/{student}/edit', StudentForm::class)->name('students.edit')->middleware('can:students.edit');
        Route::get('/students/{student}', StudentShow::class)->name('students.show');
    });

    // -------------------------------------------------------------------------
    // RUTAS DE GESTIÓN DE DOCENTES
    // -------------------------------------------------------------------------


    Route::middleware('can:teachers.view')->group(function () {
        Route::get('/teachers', TeacherIndex::class)->name('teachers.index')
            ->defaults('navigationSearch', [
                'title'       => 'Maestros',
                'description' => 'Personal docente, asignaciones y acceso al sistema',
                'keywords'    => ['profesores', 'docentes', 'personal'],
            ]);

        // 1. PRIMERO LAS RUTAS ESTÁTICAS
        Route::get('/teachers/create', TeacherForm::class)
            ->middleware('can:teachers.create')
            ->name('teachers.create')
            ->defaults('navigationSearch', [
                'title'       => 'Crear Maestro',
                'description' => 'Registrar un nuevo docente en el centro',
                'keywords'    => ['nuevo maestro', 'agregar profesor', 'contratar'],
            ]);

        Route::get('/teachers/{teacher}/assignments', TeacherAssignments::class)
            ->middleware('can:teachers.assign_subjects')
            ->name('teachers.assignments');

        // 2. ÚLTIMO LAS RUTAS CON PARÁMETROS ({teacher})
        Route::get('/teachers/{teacher}/edit', TeacherForm::class)
            ->middleware('can:teachers.edit')
            ->name('teachers.edit');

        Route::get('/teachers/{teacher}', TeacherShow::class)
            ->name('teachers.show');
    });

    // -------------------------------------------------------------------------
    // RUTAS DE GESTIÓN DE SECCIONES, TURNOS Y GRADOS (Estructura Académica)
    // -------------------------------------------------------------------------

    // Gestión de cursos / secciones
    Route::middleware('can:settings.view, settings.update')->group(function () {
        Route::get('/courses',          CourseIndex::class)->name('courses.index')
            ->defaults('navigationSearch', [
                'title'       => 'Estructura Académica',
                'description' => 'Niveles, grados y secciones del centro',
                'keywords'    => ['cursos', 'grados', 'secciones', 'niveles', 'tandas'],
            ]);
        Route::get('/courses/create',   CourseForm::class)->name('courses.create')
            ->defaults('navigationSearch', [
                'title'       => 'Crear Curso',
                'description' => 'Agregar una nueva sección o grado académico',
                'keywords'    => ['nuevo curso', 'nueva sección', 'grado'],
            ]);
        Route::get('/courses/{section}',CourseShow::class)->name('courses.show');
    });

    // -------------------------------------------------------------------------
    // Hub de Matriculación (Sala de Espera)
    // -------------------------------------------------------------------------

    Route::get('/enrollment-hub', EnrollmentHub::class)
        ->middleware('can:students.edit')
        ->name('enrollment-hub')
        ->defaults('navigationSearch', [
            'title'       => 'Matriculación',
            'description' => 'Asignar sección a estudiantes en sala de espera',
            'keywords'    => ['matricular', 'inscripción', 'sala de espera', 'asignar sección'],
        ]);

    // -------------------------------------------------------------------------
    // Kiosko Biométrico (Enrolamiento Facial)
    // -------------------------------------------------------------------------

    Route::get('biometric-kiosk', BiometricKiosk::class)
        ->middleware('can:students.edit')
        ->name('biometric-kiosk')
        ->defaults('navigationSearch', [
            'title'       => 'Registro Facial',
            'description' => 'Enrolamiento biométrico de estudiantes',
            'keywords'    => ['biometría', 'facial', 'kiosko', 'enrolar'],
        ]);

});
