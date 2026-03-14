<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\SchoolWizard;
use App\Livewire\Tenant\TenantSetupWizard;

Route::get('/', function () {
    return view('welcome');
});



/**
 * 🧙 Wizard para Usuarios de Escuela (Públicos)
 * Solo auth, para que puedan configurar su stub.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/wizard', TenantSetupWizard::class)->name('wizard');
});


// ── Perfil compartido: usuarios de escuela ──────────────────────────
// Layout: layouts/app.blade.php | Email: solo lectura
Route::middleware(['auth', 'verified', 'onboarding.complete'])
    ->prefix('app')
    ->name('app.')
    ->group(function () {

        Route::get('/dashboard', fn () => view('app.dashboard'))->name('dashboard');

        Route::get('/profile', \App\Livewire\Shared\Profile::class)->name('profile');
        // → routeIs('app.profile') → $isAdmin = false → layout: layouts.app
    });


// ── Perfil desde admin: Owner / Soporte ────────────────────────────
// Layout: components/admin.blade.php | Email: editable
Route::middleware(['auth', 'verified', 'admin.global'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/hub', fn () => view('admin.hub'))->name('hub');

        Route::get('/setup', \App\Livewire\Tenant\SchoolWizard::class)->name('setup');

        Route::get('/profile', \App\Livewire\Shared\Profile::class)->name('profile');
        // → routeIs('admin.profile') → $isAdmin = true → layout: components.admin
    });

require __DIR__.'/auth.php';

