<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Si no hay usuario o no tiene escuela, seguimos (o manejas tu lógica de error)
        if (!$user || !$user->school) {
            return $next($request);
        }

        $school = $user->school;

        // 2. Evitar bucle infinito: Si ya estamos en las rutas de aviso, permitir paso
        if ($request->routeIs('app.notice.*')) {
            return $next($request);
        }

        // 3. PRIORIDAD 1: Centro Inactivo (Deshabilitado por el sistema)
        if (!$school->is_active) {
            return redirect()->route('app.notice.inactive');
        }

        // 4. PRIORIDAD 2: Centro Suspendido (Falta de pago)
        if ($school->is_suspended) {
            return redirect()->route('app.notice.suspended');
        }

        return $next($request);
    }
}