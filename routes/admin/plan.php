<?php

use App\Livewire\Admin\Plans\PlanFeatures;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Plans\PlanIndex;

/*
|--------------------------------------------------------------------------
| Admin Plans Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin plans routes for your application.
|
*/

Route::middleware(['can:plans.view'])->prefix('plans')->name('plans.')->group(function () {
    Route::get('/', PlanIndex::class)->name('index');
    
    Route::middleware(['can:plans.manage'])->group(function () {
        Route::get('/{plan}/features', PlanFeatures::class)->name('features');
    });
});