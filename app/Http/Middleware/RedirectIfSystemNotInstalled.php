<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfSystemNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Verificar si NO existen usuarios en el sistema
        // 2. Evitar un bucle infinito permitiendo que la ruta 'register' pase
        if (!User::exists() && !$request->routeIs('register')) {
            return redirect()->route('register')
                ->with('info',  'Por favor, configura la cuenta de Propietario para comenzar.'
                );
        }

        return $next($request);
    }
}