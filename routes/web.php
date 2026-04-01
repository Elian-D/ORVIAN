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

// ── Rutas de Aviso de Estado (Fuera del middleware restrictivo para evitar bucles)
Route::middleware(['auth'])->prefix('app/notice')->name('app.notice.')->group(function () {
    Route::get('/suspended', function () {
        return view('errors.school-status', ['type' => 'suspended']);
    })->name('suspended');

    Route::get('/inactive', function () {
        return view('errors.school-status', ['type' => 'inactive']);
    })->name('inactive');
});

// ── Perfil compartido: usuarios de escuela ──────────────────────────
// Se añade 'school.active' al middleware group
Route::middleware(['auth', 'verified', 'onboarding.complete', 'school.active'])
    ->prefix('app')
    ->name('app.')
    ->group(function () {
        foreach (glob(base_path('routes/app/*.php')) as $file) {
            require $file;
        }
    });


// ── Perfil desde admin: Owner / Soporte ────────────────────────────
// Layout: components/admin.blade.php | Email: editable
Route::middleware(['auth', 'verified', 'admin.global'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        foreach (glob(base_path('routes/admin/*.php')) as $file) {
            require $file;
        }
    });

require __DIR__.'/auth.php';

