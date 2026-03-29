<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Hub' }} | {{ config('app.name') }}</title>

    <x-ui.theme-init />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        .dot-pattern {
            background-image: radial-gradient(circle, currentColor 1px, transparent 1px);
            background-size: 28px 28px;
        }
        @keyframes tile-in {
            from { opacity: 0; transform: translateY(16px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .tile-animate {
            opacity: 0;
            animation: tile-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>

    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">
</head>

<body class="font-sans antialiased transition-colors duration-300 min-h-screen flex flex-col bg-slate-100 dark:bg-dark-bg text-slate-900 dark:text-slate-100">

    <x-app.navbar />

    <main class="flex-1 relative">
        <div class="relative z-10 flex flex-col items-center justify-center py-12 md:py-16 px-4 sm:px-6">
            {{ $slot }}
        </div>
    </main>

    <x-ui.toasts />
    @livewire('shared.profile-modal')
    @livewireScripts
</body>
</html>