<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Roles\RoleIndex;
use App\Livewire\Shared\Roles\RoleForm;

Route::middleware(['can:roles.inspect'])->prefix('roles')->name('roles.')->group(function () {
    Route::get('/', RoleIndex::class)->name('index');
    
    Route::middleware(['can:roles.manage'])->group(function () {
        Route::get('/create', RoleForm::class)->name('create');
        Route::get('/{role}/edit', RoleForm::class)->name('edit');
        Route::get('/{role}/permissions', \App\Livewire\Shared\Roles\RolePermissions::class)->name('permissions');

    });
    // Matriz de permisos se agrega en Fase 5.4
});