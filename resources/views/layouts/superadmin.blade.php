<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ 
        darkMode: localStorage.getItem('darkMode') === 'true',
        sidebarOpen: window.innerWidth >= 1024 ? (localStorage.getItem('sidebarOpen') !== 'false') : false
      }"
      x-init="
        $watch('darkMode', val => localStorage.setItem('darkMode', val));
        $watch('sidebarOpen', val => {
            if (window.innerWidth >= 1024) {
                localStorage.setItem('sidebarOpen', val);
            }
        });
      "
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }} | SuperAdmin</title>
    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">

    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">


<script>
    // Bloqueo de renderizado para aplicar el tema antes de que se vea la página
    if (localStorage.getItem('darkMode') === 'true' || 
        (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-dark-bg text-gray-900 dark:text-gray-100 transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">
        
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
            
            {{-- El main ahora es el contenedor de scroll --}}
            <main class="flex-1 overflow-y-auto custom-scroll bg-gray-50 dark:bg-dark-bg flex flex-col">
                
                {{-- Contenedor del contenido con el padding --}}
                <div class="flex-1 p-4 md:p-6 pb-0 md:pb-0 relative"> {{-- Quitamos padding inferior aquí --}}
                    <x-navbar.breadcrumbs />

                    <div class="animate-fade-in">
                        {{ $slot }}
                    </div>
                </div>

                {{-- El footer ahora está fuera del div de padding, permitiendo expansión total --}}
                <x-ui.footer />
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
    <div class="fixed top-4 right-0 md:right-4 z-[9999] flex flex-col gap-3 w-full max-w-sm px-4 md:px-0 pointer-events-none">
        <div class="pointer-events-auto">
            <x-ui.toasts />
        </div>
    </div>
</body>
</html>