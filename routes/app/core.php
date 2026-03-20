<?php

use Illuminate\Support\Facades\Route;

Route::get('/dashboard', fn () => view('app.dashboard'))->name('dashboard');

Route::get('/profile', \App\Livewire\Shared\Profile::class)->name('profile');
// → routeIs('app.profile') → $isAdmin = false → layout: layouts.app
