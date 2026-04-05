<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\App\Students\StudentIndex;
use App\Livewire\App\Students\StudentShow;
use App\Livewire\App\Students\StudentForm;


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
        // Rutas del Formulario
        Route::get('/students/create', StudentForm::class)->name('students.create')->middleware('can:students.create');
        Route::get('/students/{student}/edit', StudentForm::class)->name('students.edit')->middleware('can:students.edit');
        Route::get('/students/{student}', StudentShow::class)->name('students.show');
    });
});