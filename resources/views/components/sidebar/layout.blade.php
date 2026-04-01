<aside 
    x-data="{ hasHover: false }"
    @mouseover="if (!sidebarOpen) hasHover = true"
    @mouseleave="if (!sidebarOpen) hasHover = false"
    class="bg-white dark:bg-dark-bg border-r border-white/10 transition-all duration-300 ease-in-out flex flex-col shadow-xl z-50"
    :class="{
        'w-72': sidebarOpen || hasHover,
        'w-20': !sidebarOpen && !hasHover,
        'fixed inset-y-0 left-0 -translate-x-full sm:relative sm:translate-x-0': !sidebarOpen,
        'fixed inset-y-0 left-0 translate-x-0': sidebarOpen && window.innerWidth < 1024
    }"
>
    <div class="h-20 flex items-center px-4 border-b border-white/5 overflow-hidden flex-shrink-0">
        <a href="{{ route('admin.hub') }}" class="flex items-center w-full justify-center transition-all duration-300">
            <div x-show="sidebarOpen || hasHover" 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="flex items-center justify-center">
                <x-application-logo type="full"  class="h-13" />
            </div>

            {{-- Icono - Forzado a DARK --}}
            <div x-show="!sidebarOpen && !hasHover" 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="flex items-center justify-center">
                <x-application-logo type="icon" class="h-10" />
            </div>
        </a>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto overflow-x-hidden custom-scroll">
        {{ $slot }}
    </nav>

    <div class="m-3 border border-white/10 rounded-2xl bg-black/10 relative transition-all duration-300 ease-in-out flex-shrink-0"
        :class="(sidebarOpen || hasHover) ? 'p-3' : 'p-1.5'"
        x-data="{ userMenuOpen: false }">
        
        <div class="flex items-center relative transition-all duration-300" 
            :class="(sidebarOpen || hasHover) ? 'gap-3' : 'justify-center'">
            
            <x-ui.avatar :user="Auth::user()" size="sm" showStatus />
            
            <div x-show="sidebarOpen || hasHover" 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ Auth::user()->hasRole('Owner') ? 'Dueño' : 'Usuario del Sistema' }}
                </p>
            </div>

            <button @click="userMenuOpen = !userMenuOpen"
                    x-show="sidebarOpen || hasHover"
                    class="p-1.5 rounded-lg text-gray-500 hover:bg-white/10 hover:text-white transition-colors duration-200 flex-shrink-0">
                <x-heroicon-s-chevron-up-down class="w-5 h-5" />
            </button>
        </div>

        {{-- ══════════════════════════════════════════════════════
            DROPDOWN SIDEBAR  —  reemplaza el bloque completo
            Ruta: resources/views/layouts/sidebar.blade.php
            ══════════════════════════════════════════════════════ --}}

        <div x-show="userMenuOpen && (sidebarOpen || hasHover)"
            @click.away="userMenuOpen = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="absolute bottom-full left-0 mb-3 w-full
                    bg-white dark:bg-dark-card border border-gray-100 dark:border-white/5
                    rounded-2xl shadow-2xl p-2 z-50">

            {{-- Cabecera --}}
            <div class="px-3 py-2 border-b border-gray-100 dark:border-white/5 mb-2 flex items-center gap-3">
                <x-ui.avatar :user="Auth::user()" size="sm" showStatus />
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                        {{ Auth::user()->name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ Auth::user()->email }}
                    </p>
                </div>
            </div>

            {{-- Estado (sub-dropdown vía Livewire) --}}
            @livewire('shared.user-status')

            <div class="my-1 border-t border-gray-100 dark:border-white/5"></div>

            <a href="{{ route('admin.profile') }}"
            class="flex w-full items-center gap-3 px-3 py-2 rounded-xl text-sm
                    text-gray-600 dark:text-gray-300
                    hover:bg-orvian-blue/5 dark:hover:bg-white/5
                    hover:text-orvian-blue dark:hover:text-white
                    transition duration-200 group">
                <x-heroicon-s-user class="w-4 h-4 text-gray-400 group-hover:text-orvian-blue dark:group-hover:text-white" />
                <span>Mi Perfil</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-3 px-3 py-2 rounded-xl text-sm
                            text-gray-600 dark:text-gray-300
                            hover:bg-red-50 dark:hover:bg-red-950/30
                            hover:text-red-600 transition duration-200 group">
                    <x-heroicon-s-arrow-left-on-rectangle class="w-4 h-4 text-gray-400 group-hover:text-red-600" />
                    <span>Cerrar sesión</span>
                </button>
            </form>
        </div>
    </div>
</aside>