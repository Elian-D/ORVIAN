<?php

use Illuminate\Support\Facades\Route;

Route::get('/hub', fn () => view('admin.hub'))->name('hub');

Route::get('/setup', \App\Livewire\Tenant\SchoolWizard::class)->name('setup');

Route::get('/profile', \App\Livewire\Shared\Profile::class)->name('profile');
// → routeIs('admin.profile') → $isAdmin = true → layout: components.admin
