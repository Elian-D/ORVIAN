<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Configuración' }} | ORVIAN</title>

    {{-- ⬇ Primero el tema — síncrono, antes de cualquier CSS --}}
    <x-ui.theme-init />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">
</head>
<body class="font-sans antialiased min-h-screen bg-slate-50 dark:bg-[#020617] text-slate-900 dark:text-slate-100 transition-colors duration-300 overflow-x-hidden">

    <div class="fixed inset-0 pointer-events-none z-0">
        <div class="absolute top-[-10%] left-[-5%] w-[50%] h-[50%] rounded-full blur-[120px] bg-indigo-500/10 dark:bg-indigo-600/20 animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-5%] w-[40%] h-[40%] rounded-full blur-[100px] bg-orange-400/10 dark:bg-orvian-orange/10"></div>
    </div>

    <x-ui.toasts />

    <div class="relative z-10 min-h-screen flex flex-col items-center py-12 px-4">
        
        <header class="w-full max-w-5xl flex items-center justify-between mb-12">
            <x-application-logo mode="dynamic" class="h-12 w-auto" />
            
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-slate-500 uppercase tracking-widest">Setup v0.2.0</span>
            </div>
        </header>

        <main class="w-full max-w-5xl">
            {{ $slot }}
        </main>
        
    </div>
</body>
</html>