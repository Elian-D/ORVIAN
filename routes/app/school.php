<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\App\Settings\SchoolSettings;

/*
|--------------------------------------------------------------------------
| Admin Schools Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin schools routes for your application.
|
*/

Route::get('/school/settings', SchoolSettings::class)->name('school.settings')
    ->middleware('can:settings.view, settings.update');

