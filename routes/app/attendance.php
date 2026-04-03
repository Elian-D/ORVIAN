<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\App\Attendance\ClassroomAttendanceLive;

/*
|--------------------------------------------------------------------------
| App Module Attendance Routes
|--------------------------------------------------------------------------
|
| Here is where you can register app users routes for your application.
|
*/


Route::middleware(['can:attendance_classroom.view'])->prefix('attendance')->name('attendance.')->group(function () {
    // Agregamos el parámetro opcional o requerido en la URL
    Route::get('/{assignmentId}', ClassroomAttendanceLive::class)->name('index');
    
});