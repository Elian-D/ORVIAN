<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Hub' }} | {{ config('app.name') }}</title>
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

<body class="font-sans antialiased transition-colors duration-300 min-h-screen flex flex-col bg-slate-100 dark:bg-[#080e1a] text-slate-900 dark:text-slate-100">

    {{-- NAVBAR --}}
    <header class="sticky top-0 z-50 bg-white/90 dark:bg-[#0c1220]/90 border-b border-slate-200 dark:border-white/5 backdrop-blur-xl transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">

            {{-- Logo + Badge --}}
            <div class="flex items-center gap-3 flex-shrink-0">
                <x-application-logo type="full" mode="dynamic" class="h-12 w-auto" />
                <span class="hidden sm:inline-flex text-[10px] font-black uppercase tracking-[0.2em] px-2 py-0.5 rounded-md bg-orvian-orange/10 text-orvian-orange">
                    App Hub
                </span>
            </div>

            {{-- Buscador --}}
            <div class="flex-1 max-w-sm hidden md:block">
                <div class="relative group">
                    <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors" />
                    <input type="text"
                           placeholder="Buscar módulo..."
                           class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border transition-all duration-200 focus:outline-none
                                  bg-slate-100 dark:bg-white/5
                                  border-slate-200 dark:border-white/10
                                  text-slate-700 dark:text-slate-200
                                  placeholder:text-slate-400 dark:placeholder:text-slate-500
                                  focus:border-orvian-orange/50 focus:bg-white dark:focus:bg-white/8" />
                </div>
            </div>

            {{-- Acciones --}}
            <div class="flex items-center gap-1 sm:gap-2">

                {{-- Toggle tema --}}
                <button @click="darkMode = !darkMode"
                        class="p-2 rounded-xl transition-colors text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-amber-500 dark:hover:text-amber-400">
                    <x-heroicon-o-sun x-show="darkMode" x-cloak class="w-5 h-5" />
                    <x-heroicon-o-moon x-show="!darkMode" x-cloak class="w-5 h-5" />
                </button>

                <div class="w-px h-6 mx-1 bg-slate-200 dark:bg-white/10"></div>

                {{-- Usuario --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="flex items-center gap-2.5 pl-1 pr-3 py-1 rounded-xl transition-colors hover:bg-slate-100 dark:hover:bg-white/5">
                        <x-ui.avatar :user="Auth::user()" size="sm" showStatus />
                        <span class="hidden sm:block text-[13px] font-semibold truncate max-w-[100px] text-slate-700 dark:text-slate-200">
                            {{ explode(' ', Auth::user()->name ?? 'Usuario')[0] }}
                        </span>
                        <x-heroicon-s-chevron-down class="w-3.5 h-3.5 hidden sm:block text-slate-400 dark:text-slate-500 transition-transform duration-200" ::class="{ 'rotate-180': open }" />
                    </button>

                    {{-- ══════════════════════════════════════════════════════
                        DROPDOWN NAVBAR  —  reemplaza el bloque completo
                        Ruta: resources/views/components/navbar/*.blade.php
                        ══════════════════════════════════════════════════════ --}}

                    <div x-show="open"
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                        x-cloak
                        class="absolute right-0 top-full mt-2 w-56 rounded-2xl shadow-2xl border p-2 z-50
                                bg-white dark:bg-[#0f1828]
                                border-slate-100 dark:border-white/8">

                        {{-- Cabecera --}}
                        <div class="px-3 py-2.5 mb-1 border-b border-slate-100 dark:border-white/5 flex items-center gap-3">
                            <x-ui.avatar :user="Auth::user()" size="sm" showStatus />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold truncate text-slate-800 dark:text-slate-100">
                                    {{ Auth::user()->name }}
                                </p>
                                <p class="text-xs truncate text-slate-400 dark:text-slate-500">
                                    {{ Auth::user()->email }}
                                </p>
                            </div>
                        </div>

                        {{-- Estado (sub-dropdown vía Livewire) --}}
                        @livewire('shared.user-status')

                        <div class="my-1 border-t border-slate-100 dark:border-white/5"></div>

                        <a href="{{ route('app.profile') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm transition-colors group
                                text-slate-600 dark:text-slate-300
                                hover:bg-slate-50 dark:hover:bg-white/5
                                hover:text-slate-900 dark:hover:text-white">
                            <x-heroicon-s-user class="w-4 h-4 opacity-50 group-hover:opacity-100" />
                            Mi Perfil
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="flex w-full items-center gap-3 px-3 py-2 rounded-xl text-sm transition-colors group
                                        text-red-500 hover:bg-red-50 dark:hover:bg-red-950/25">
                                <x-heroicon-s-arrow-left-on-rectangle class="w-4 h-4 opacity-60 group-hover:opacity-100" />
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- MAIN --}}
    <main class="flex-1 relative">
        <div class="relative z-10 flex flex-col items-center justify-center py-12 md:py-16 px-4 sm:px-6">
            {{ $slot }}
        </div>
    </main>

    <x-ui.toasts />

    @livewireScripts

<script>
    if (localStorage.getItem('darkMode') === 'true' ||
        (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
</script>

</body>
</html>