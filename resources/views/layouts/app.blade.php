<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ sidebarOpen: window.innerWidth >= 1024 ? (localStorage.getItem('sidebarOpen') !== null ? localStorage.getItem('sidebarOpen') === 'true' : true) : false }"
      x-init="$watch('sidebarOpen', val => { if (window.innerWidth >= 1024) localStorage.setItem('sidebarOpen', val) })">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($isAdminContext = request()->routeIs('admin.*'))
    <title>{{ $title ?? ($isAdminContext ? config('app.name') : 'Hub') }} | {{ $isAdminContext ? 'SuperAdmin' : config('app.name') }}</title>

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
<body class="font-sans antialiased bg-gray-50 dark:bg-dark-bg text-gray-900 dark:text-gray-100 transition-colors duration-300" >

    <div class="flex h-screen overflow-hidden" x-cloak>

        {{-- Overlay para móvil (drawer completo — distinto del peek de hover en desktop, ver components/sidebar/layout.blade.php) --}}
        <div x-show="sidebarOpen" x-cloak
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 sm:hidden">
        </div>

        @include($isAdminContext ? 'layouts.sidebar' : 'layouts.sidebar-app')

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <x-navbar.layout />

            @if(request()->routeIs('app.dashboard'))
                {{-- El Hub de escuela administra su propio ancho/centrado — sin breadcrumbs ni footer --}}
                <main class="flex-1 overflow-y-auto custom-scroll bg-gray-50 dark:bg-dark-bg relative">
                    <div class="relative z-10 flex flex-col items-center py-12 md:py-16 px-4 sm:px-6">
                        {{ $slot }}
                    </div>
                </main>
            @else
                <main class="flex-1 overflow-y-auto custom-scroll bg-gray-50 dark:bg-dark-bg flex flex-col">
                    <div class="flex-1 p-4 md:p-6 pb-4 md:pb-4 relative">
                        <x-navbar.breadcrumbs />
                        <div class="animate-fade-in">
                            {{ $slot }}
                        </div>
                    </div>
                    <x-ui.footer />
                </main>
            @endif
        </div>
    </div>

    <x-ui.toasts />

    @livewireScripts
    @stack('scripts')
</body>
</html>
