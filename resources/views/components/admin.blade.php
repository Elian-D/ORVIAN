@php
    /** @var \App\Models\User|null $authUser */
    $authUser = auth()->user();
    $sidebarInitial = $authUser
        ? ($authUser->preference('sidebar_collapsed', false) ? 'false' : 'true')
        : 'true';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ sidebarOpen: window.innerWidth >= 1024 ? {{ $sidebarInitial }} : false }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }} | SuperAdmin</title>

    {{-- ⬇ Primero el tema — síncrono, antes de cualquier CSS --}}
    <x-ui.theme-init />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-dark-bg text-gray-900 dark:text-gray-100 transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        {{-- Overlay para móvil --}}
        <div x-show="sidebarOpen"
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden">
        </div>

        @include('layouts.sidebar')

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <x-navbar.layout />

            <main class="flex-1 overflow-y-auto custom-scroll bg-gray-50 dark:bg-dark-bg flex flex-col">
                <div class="flex-1 p-4 md:p-6 pb-4 md:pb-4 relative">
                    <x-navbar.breadcrumbs />
                    <div class="animate-fade-in">
                        {{ $slot }}
                    </div>
                </div>
                <x-ui.footer />
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')

    <x-ui.toasts />
</body>
</html>