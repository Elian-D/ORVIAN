<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\App\Users\UserIndex;

/*
|--------------------------------------------------------------------------
| App Users Routes
|--------------------------------------------------------------------------
|
| Here is where you can register app users routes for your application.
|
*/

 
Route::get('/users', UserIndex::class)->name('users.index');