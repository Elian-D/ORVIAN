<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User; // Asegúrate de importar tu modelo
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */

    public function store(LoginRequest $request): RedirectResponse
    {
        if ($request->filled('qr_code')) {
            $qrCode = $request->input('qr_code');

            $user = \App\Models\User::whereHas('teacher', fn($q) => $q->where('qr_code', $qrCode))
                ->orWhereHas('student', fn($q) => $q->where('qr_code', $qrCode))
                ->first();

            if ($user) {
                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();
                
                return redirect()->intended(route('app.dashboard'))
                    ->with('success', '¡Sesión iniciada vía QR!');
            }

            return back()->withErrors(['email' => 'Código QR no reconocido.']);
        }

        // SOLO si no hay QR o el flujo falló arriba, ejecutamos esto:
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('app.dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')
            ->with('success', 'Has cerrado sesión. ¡Vuelve pronto!');
    }
}
