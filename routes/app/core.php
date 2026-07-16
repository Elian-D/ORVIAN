<?php

use Illuminate\Support\Facades\Route;

Route::get('/dashboard', fn () => view('app.dashboard'))->name('dashboard')
    ->defaults('navigationSearch', [
        'title'       => 'Dashboard',
        'description' => 'Panel principal del centro',
        'keywords'    => ['inicio', 'hub', 'home'],
    ]);

Route::get('/profile', \App\Livewire\Shared\Profile::class)->name('profile')
    ->defaults('navigationSearch', [
        'title'       => 'Mi Perfil',
        'description' => 'Datos personales, contraseña y preferencias',
        'keywords'    => ['perfil', 'contraseña', 'cuenta', 'preferencias', 'tema'],
    ]);
// → routeIs('app.profile') → $isAdmin = false → layout: layouts.app
