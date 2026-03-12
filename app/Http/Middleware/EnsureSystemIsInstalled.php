<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemIsInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si hay usuarios y el tipo intenta registrarse, lo mandamos al login
        if (User::exists() && $request->routeIs('register')) {
            return redirect()->route('login')
                ->with('info', 'El registro está deshabilitado para nuevos administradores.');
        }

        return $next($request);
    }
}