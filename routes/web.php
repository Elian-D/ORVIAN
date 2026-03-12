<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\SchoolWizard;
use App\Livewire\Tenant\TenantSetupWizard;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * 🧙 Wizard para Usuarios de Escuela (Públicos)
 * Solo auth, para que puedan configurar su stub.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/wizard', TenantSetupWizard::class)->name('wizard');
});

/**
 * 🛠 Entorno Administrativo (Owner / Soporte)
 * Usamos una función anónima o una Gate para verificar que NO tengan school_id.
 * Esto engloba a Owner, Support y cualquier rol administrativo futuro.
 */
Route::middleware(['auth', 'verified', 'admin.global']) // Usamos el alias aquí
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        
        // Hub Global
        Route::get('/hub', function () {
            return view('admin.hub');
        })->name('hub');

        // Wizard del Owner
        Route::get('/setup', SchoolWizard::class)->name('setup');
});


/**
 * 🏫 Entorno de Aplicación (Tenants / Escuelas)
 * Protegido por onboarding.complete
 */
Route::middleware(['auth', 'verified', 'onboarding.complete'])
    ->prefix('app')
    ->name('app.')
    ->group(function () {
    
    Route::get('/dashboard', function () {
        return view('app.dashboard');
    })->name('dashboard');

});

require __DIR__.'/auth.php';