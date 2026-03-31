<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Schools\SchoolIndex;
use App\Livewire\Admin\Schools\SchoolShow;

/*
|--------------------------------------------------------------------------
| Admin Schools Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin schools routes for your application.
|
*/

Route::get('/schools', SchoolIndex::class)->name('schools.index')
    ->middleware('can:schools.view');

Route::get('/schools/{school}', SchoolShow::class)->name('schools.show')
    ->middleware('can:schools.view');
