<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Admin\Dashboard\StatsOverview;

// Cambiamos la función anónima por el componente de clase
Route::get('/hub', StatsOverview::class)->name('hub');

Route::get('/setup', \App\Livewire\Tenant\SchoolWizard::class)->name('setup');

Route::get('/profile', \App\Livewire\Shared\Profile::class)->name('profile');
// → routeIs('admin.profile') → $isAdmin = true → layout: components.admin
