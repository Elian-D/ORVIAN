@props(['homeRoute' => 'admin.hub', 'profileRoute' => 'admin.profile'])

{{--
    REQ-07.12 — el ancho "de flujo" (que empuja <main>) depende SOLO de
    $sidebarOpen (decisión explícita del usuario, persistida en localStorage
    vía REQ-07.9). El hover sobre el estado colapsado (hasHover) NUNCA toca
    el ancho del <aside> — solo expande el <div> interno (absolute) como un
    overlay flotante sobre el contenido, sin empujar nada y sin oscurecer
    el fondo (a diferencia del drawer mobile, que sí usa backdrop en el
    layout — ver components/admin.blade.php / layouts/app-module.blade.php).
--}}
<aside
    x-data="{ hasHover: false }"
    @mouseenter="if (!sidebarOpen) hasHover = true"
    @mouseleave="hasHover = false"
    class="fixed inset-y-0 left-0 sm:relative z-50 flex-shrink-0 transition-[width,transform] duration-300 ease-in-out"
    :class="{
        'w-72': sidebarOpen,
        'w-20': !sidebarOpen,
        'translate-x-0': sidebarOpen,
        '-translate-x-full sm:translate-x-0': !sidebarOpen,
    }"
>
    <div
        class="absolute inset-y-0 left-0 bg-white dark:bg-dark-bg border-r border-white/10 flex flex-col shadow-xl transition-[width] duration-300 ease-in-out"
        :class="(sidebarOpen || hasHover) ? 'w-72' : 'w-20'"
    >
        <div class="h-20 flex items-center px-4 border-b border-white/5 overflow-hidden flex-shrink-0">
            <a href="{{ route($homeRoute) }}" class="flex items-center w-full justify-center transition-all duration-300">
                <div x-show="sidebarOpen || hasHover"
                    x-transition:enter="transition ease-out duration-150 delay-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="flex items-center justify-center">
                    <x-application-logo type="full"  class="h-13" />
                </div>

                {{-- Icono - Forzado a DARK --}}
                <div x-show="!sidebarOpen && !hasHover"
                    x-transition:enter="transition ease-out duration-150 delay-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="flex items-center justify-center">
                    <x-application-logo type="icon" class="h-10" />
                </div>
            </a>
        </div>

        {{-- openDropdown: id del único dropdown de módulo abierto a la vez (acordeón) — ver components/sidebar/dropdown.blade.php --}}
        <nav x-data="{ openDropdown: null }" class="flex-1 px-4 py-6 space-y-2 overflow-y-auto overflow-x-hidden custom-scroll">
            {{ $slot }}
        </nav>

        <div class="m-3 border border-gray-100 dark:border-white/10 rounded-2xl bg-gray-50 dark:bg-black/10 relative transition-all duration-300 ease-in-out flex-shrink-0"
            :class="(sidebarOpen || hasHover) ? 'p-3' : 'p-1.5'"
            x-data="{ userMenuOpen: false }">

            <div class="flex items-center relative transition-all duration-300"
                :class="(sidebarOpen || hasHover) ? 'gap-3' : 'justify-center'">

                <x-ui.avatar :user="Auth::user()" size="sm" />

                <div x-show="sidebarOpen || hasHover"
                    x-transition:enter="transition ease-out duration-150 delay-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ Auth::user()->position ?? Auth::user()->getRoleNames()->first() ?? 'Usuario' }}
                    </p>
                </div>

                <button @click="userMenuOpen = !userMenuOpen"
                        x-show="sidebarOpen || hasHover"
                        class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-200 dark:hover:bg-white/10 hover:text-gray-700 dark:hover:text-white transition-colors duration-200 flex-shrink-0">
                    <x-heroicon-s-chevron-up-down class="w-5 h-5" />
                </button>
            </div>

            <div x-show="userMenuOpen && (sidebarOpen || hasHover)"
                @click.away="userMenuOpen = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="absolute bottom-full left-0 mb-3 w-full
                        bg-white dark:bg-dark-card border border-gray-100 dark:border-white/5
                        rounded-2xl shadow-2xl p-2 z-50">

                <div class="px-3 py-2 border-b border-gray-100 dark:border-white/5 mb-2 flex items-center gap-3">
                    <x-ui.avatar :user="Auth::user()" size="sm" />
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                            {{ Auth::user()->name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ Auth::user()->email }}
                        </p>
                    </div>
                </div>

                <a href="{{ route($profileRoute) }}"
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
    </div>
</aside>
