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
    ->middleware('can:settings.view, settings.update')
    ->defaults('navigationSearch', [
        'title'       => 'Configuración del Centro',
        'description' => 'Datos, logo y ajustes generales de la escuela',
        'keywords'    => ['configuración', 'ajustes', 'logo', 'centro', 'perfil del centro'],
    ]);
