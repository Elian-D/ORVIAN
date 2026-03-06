<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $schoolId = null;

            // 1. Prioridad: Sesión activa (Para Soporte Técnico / SuperAdmin)
            // Si eres SuperAdmin y has "seleccionado" una escuela para administrar
            if (!$user->school_id && session()->has('impersonated_school_id')) {
                $schoolId = session()->get('impersonated_school_id');
            } 
            // 2. Prioridad: Usuario Normal
            else if ($user->school_id) {
                $schoolId = $user->school_id;
            }

            if ($schoolId) {
                // Seteamos el Tenant para Spatie
                setPermissionsTeamId($schoolId);

                // Compartimos la instancia de la escuela globalmente
                $school = School::find($schoolId);
                if ($school) {
                    app()->instance('currentSchool', $school);
                }
            }
        }

        return $next($request);
    }
}