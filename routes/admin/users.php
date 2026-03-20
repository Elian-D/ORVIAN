<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Users\UserIndex;

/*
|--------------------------------------------------------------------------
| Admin Users Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin users routes for your application.
|
*/

Route::get('/users', UserIndex::class)->name('users.index');
