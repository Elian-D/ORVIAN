<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\App\Roles\RoleIndex;
use App\Livewire\Shared\Roles\RoleForm;

Route::middleware(['can:roles.view'])->prefix('roles')->name('roles.')->group(function () {
    Route::get('/', RoleIndex::class)->name('index')
        ->defaults('navigationSearch', [
            'title'       => 'Roles',
            'description' => 'Roles y permisos del centro',
            'keywords'    => ['permisos', 'accesos', 'perfiles'],
        ]);

    Route::middleware(['can:roles.create'])->group(function () {
        Route::get('/create', RoleForm::class)->name('create')
            ->defaults('navigationSearch', [
                'title'       => 'Crear Rol',
                'description' => 'Definir un nuevo rol con sus permisos',
                'keywords'    => ['nuevo rol', 'permisos'],
            ]);
        Route::get('/{role}/edit', RoleForm::class)->name('edit');
        Route::get('/{role}/permissions', \App\Livewire\Shared\Roles\RolePermissions::class)->name('permissions');
    });
    // Matriz de permisos se agrega en Fase 5.4
});
