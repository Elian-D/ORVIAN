<!DOCTYPE html>
@php
    /** @var \App\Models\User|null $authUser */
    $authUser = auth()->user();
    // DB es la única fuente de verdad para el estado inicial del sidebar.
    // sidebar_collapsed = true → sidebarOpen arranca en false (colapsado)
    // sidebar_collapsed = false/null → sidebarOpen arranca en true (expandido)
    $sidebarInitial = $authUser
        ? ($authUser->preference('sidebar_collapsed', false) ? 'false' : 'true')
        : 'true';
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          darkMode: localStorage.getItem('darkMode') === 'true',
          sidebarOpen: window.innerWidth >= 1024 ? {{ $sidebarInitial }} : false
      }"
      x-init="
          $watch('darkMode', val => localStorage.setItem('darkMode', val));
      "
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }} | SuperAdmin</title>
    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">



    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
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
                <div class="flex-1 p-4 md:p-6 pb-0 md:pb-0 relative">
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

    <script>
        // Aplica darkMode antes de que se pinte la página — sin flash
        if (localStorage.getItem('darkMode') === 'true' ||
            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <x-ui.toasts />
</body>
</html>