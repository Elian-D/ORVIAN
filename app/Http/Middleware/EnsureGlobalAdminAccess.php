<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureGlobalAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario tiene school_id, NO es un admin global.
        if (Auth::user()->school_id !== null) {
            return redirect()->route('app.dashboard');
        }

        return $next($request);
    }
}