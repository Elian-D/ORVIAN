<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureOnboardingIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // 1. Los roles globales pasan siempre (Owner, Soporte Técnico, Administrativo Global)
        // Estos usuarios no tienen school_id asignado en sus modelos.
        if ($user->school_id === null) {
            return $next($request);
        }

        $school = $user->school;

        // 2. Si tiene escuela pero no está configurada, enviarlo al wizard
        if (!$school || !$school->is_configured) {
            session()->flash('info', "Por favor, completa la configuración de '{$school->name}' para continuar.");
            return redirect()->route('wizard');
        }

        return $next($request);
    }
}