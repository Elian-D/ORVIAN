<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalación | {{ config('app.name') }}</title>

    {{-- ⬇ Primero el tema — síncrono, antes de cualquier CSS --}}
    <x-ui.theme-init />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html.dark body {
            background-color: #020617;
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.06) 1px, transparent 0);
            background-size: 40px 40px;
        }
        html:not(.dark) body {
            background-color: #f1f5f9;
            background-image: radial-gradient(circle at 2px 2px, rgba(4,39,95,0.07) 1px, transparent 0);
            background-size: 40px 40px;
        }
        @keyframes slide-in-right {
            from { opacity: 0; transform: translateX(28px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes slide-in-left {
            from { opacity: 0; transform: translateX(-28px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .step-enter-next { animation: slide-in-right 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .step-enter-prev { animation: slide-in-left  0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>

    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">
</head>

<body class="font-sans antialiased min-h-screen flex flex-col relative text-slate-800 dark:text-slate-200 transition-colors duration-300">

    {{-- Orbs decorativos — clases dark: nativas, sin Alpine --}}
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full blur-[120px] bg-blue-400/10 dark:bg-blue-900/20"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full blur-[120px] bg-orange-400/10 dark:bg-orange-900/10"></div>
    </div>

    <x-ui.toasts />

    {{-- Toggle de tema --}}
    <div class="fixed top-5 left-5 z-50">
        <button @click="darkMode = !darkMode"
                class="p-2.5 rounded-xl border transition-all duration-200 backdrop-blur-sm
                       bg-white/80 dark:bg-white/5
                       border-slate-200 dark:border-white/10
                       text-slate-500 dark:text-slate-400
                       hover:text-amber-500 dark:hover:text-amber-400
                       hover:bg-white dark:hover:bg-white/10">
            <svg x-show="darkMode" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <svg x-show="!darkMode" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>
    </div>

    {{-- Contenido scrollable --}}
    <main class="relative z-10 flex flex-col items-center justify-start min-h-screen w-full px-4 py-12 sm:py-16">

        <div class="text-center mb-10 w-full max-w-lg">
            <x-application-logo type="full" mode="dynamic" class="h-12 w-auto mx-auto mb-6 drop-shadow-[0_0_15px_rgba(247,137,4,0.3)]" />
            <h1 class="text-2xl font-bold tracking-tight text-orvian-navy dark:text-white">
                Configuración Inicial
            </h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Estás a un paso de tomar el control total de la plataforma.
            </p>
        </div>

        <div class="w-full max-w-lg">
            {{ $slot }}
        </div>

        <div class="mt-10 text-center">
            <p class="text-xs uppercase tracking-widest font-bold text-slate-400 dark:text-slate-600">
                ORVIAN <span class="text-orvian-orange/50 mx-2">//</span> Deployment Mode
            </p>
        </div>

    </main>
</body>
</html>