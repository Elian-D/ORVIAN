<?php

use App\Http\Controllers\App\Students\StudentPrintController;
use Illuminate\Support\Facades\Route;
use App\Livewire\App\Students\StudentIndex;
use App\Livewire\App\Students\StudentShow;
use App\Livewire\App\Students\StudentForm;
use App\Livewire\App\Students\StudentPrintManager;

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
});