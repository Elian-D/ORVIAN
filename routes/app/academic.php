<?php

use App\Http\Controllers\App\Students\StudentPrintController;
use Illuminate\Support\Facades\Route;
use App\Livewire\App\Students\StudentIndex;
use App\Livewire\App\Students\StudentShow;
use App\Livewire\App\Students\StudentForm;
use App\Livewire\App\Students\StudentPrintManager;

use App\Livewire\App\Teachers\TeacherIndex;
use App\Livewire\App\Teachers\TeacherShow;
use App\Livewire\App\Teachers\TeacherForm;
use App\Livewire\App\Teachers\TeacherAssignments;

/*
|--------------------------------------------------------------------------
| Módulo de Gestión Académica
|--------------------------------------------------------------------------
| Prefijo resultante: /app/academic/...
| Nombre resultante: app.academic....
*/

Route::prefix('academic')->name('academic.')->group(function () {
    Route::middleware('can:students.view')->group(function () {
        Route::get('/students', StudentIndex::class)->name('students.index');
        
        // 1. PRIMERO LAS RUTAS ESTÁTICAS
        Route::get('/students/create', StudentForm::class)->name('students.create')->middleware('can:students.create');
        
        Route::get('/students/print-manager', StudentPrintManager::class)
            ->middleware('can:students.import')
            ->name('students.print-manager');

        Route::get('/students/print-qr-sheet', [StudentPrintController::class, 'printQrSheet'])
            ->middleware('can:students.import')
            ->name('students.print-qr-sheet');

        // 2. ÚLTIMO LAS RUTAS CON PARÁMETROS ({student})
        Route::get('/students/{student}/edit', StudentForm::class)->name('students.edit')->middleware('can:students.edit');
        Route::get('/students/{student}', StudentShow::class)->name('students.show');
    });

    Route::middleware('can:teachers.view')->group(function () {
        Route::get('/teachers', TeacherIndex::class)->name('teachers.index');

        // 1. PRIMERO LAS RUTAS ESTÁTICAS
        Route::get('/teachers/create', TeacherForm::class)
            ->middleware('can:teachers.create')
            ->name('teachers.create');

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
});