<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // 1. Si el usuario pertenece a una escuela, seteamos el ID para Spatie Permissions
            if ($user->school_id) {
                setPermissionsTeamId($user->school_id);
            }
            
            // 2. Opcional: Compartir el tenant globalmente en la app para acceso rápido
            if ($user->school_id) {
                app()->instance('currentSchool', $user->school);
            }
        }

        return $next($request);
    }
}