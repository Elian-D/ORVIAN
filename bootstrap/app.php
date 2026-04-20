<?php


use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        // Redirección para invitados (sustituye a Authenticate.php antiguo)
        $middleware->redirectTo(
            guests: fn (Request $request) => route('login')
        );

<<<<<<< HEAD
        // FIX (Produccion)
=======
        // Fix produccion
>>>>>>> main
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\IdentifyTenant::class,
        ]);

        $middleware->alias([
            'system.not_installed' => \App\Http\Middleware\RedirectIfSystemNotInstalled::class,
            'system.installed' => \App\Http\Middleware\EnsureSystemIsInstalled::class,
            'onboarding.complete' => \App\Http\Middleware\EnsureOnboardingIsComplete::class,
            'admin.global' => \App\Http\Middleware\EnsureGlobalAdminAccess::class,
            'school.active' => \App\Http\Middleware\EnsureSchoolIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
