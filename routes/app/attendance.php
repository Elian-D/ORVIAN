<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\App\Attendance\ClassroomAttendanceLive;
use App\Livewire\App\Attendance\ExcuseIndex;

/*
|--------------------------------------------------------------------------
| App Module Attendance Routes
|--------------------------------------------------------------------------
|
| Here is where you can register app users routes for your application.
|
*/


Route::prefix('attendance')->name('attendance.')->group(function () {
    
    Route::prefix('excuses')->name('excuses.')->group(function () {
        Route::get('/', ExcuseIndex::class)->middleware('can:excuses.view')->name('index');
    });



    // Agregamos el parámetro opcional o requerido en la URL
    Route::get('/{assignmentId}', ClassroomAttendanceLive::class)->name('index');
});