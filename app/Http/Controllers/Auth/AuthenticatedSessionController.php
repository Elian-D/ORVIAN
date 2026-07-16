<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Toast de bienvenida — detectado automáticamente por x-ui.toasts
        session()->flash('success', '¡Bienvenido de nuevo, ' . Auth::user()->name . '!');

        return redirect()->intended(
            is_null(Auth::user()->school_id)
                ? route('admin.hub')
                : route('app.dashboard')
        );
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')
            ->with('success', 'Has cerrado sesión. ¡Vuelve pronto!');
    }
}
