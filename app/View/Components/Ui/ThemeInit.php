<?php

namespace App\View\Components\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;

/**
 * x-ui.theme-init
 *
 * Snippet síncrono que se inyecta en el <head> de cada layout ANTES de @vite.
 * Lee el tema desde las preferencias del usuario en DB y aplica (o no) la clase
 * `.dark` al <html> de forma síncrona — sin flash, sin localStorage.
 *
 * Uso: <x-ui.theme-init />
 *
 * Prioridad:
 *   1. DB: auth()->user()->preference('theme', 'system')
 *   2. Si es 'system' → prefers-color-scheme del navegador
 *   3. Si no hay usuario autenticado → 'system'
 */
class ThemeInit extends Component
{
    public string $theme;

    public function __construct()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $this->theme = $user
            ? ($user->preference('theme', 'system'))
            : 'system';
    }

    public function render(): View
    {
        return view('components.ui.theme-init');
    }
}