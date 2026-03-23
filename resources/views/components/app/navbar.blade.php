@props([
    'module'      => null,
    'moduleIcon'  => null,
    'moduleLinks' => [],
])

@php $isModule = !is_null($module); @endphp

<div x-data="{ drawerOpen: false }">

<header
    x-data="{
        scrolled: false,
        init() {
            this.scrolled = window.scrollY > 10;
            window.addEventListener('scroll', () => { this.scrolled = window.scrollY > 10; }, { passive: true });
        }
    }"
    @class([
        {{-- FIXED: se clava arriba, el contenido pasa por debajo --}}
        'fixed top-0 left-0 right-0 z-50 transition-all duration-300',
        'bg-white/95 dark:bg-[#0c1220]/95 border-b border-slate-200 dark:border-white/5 backdrop-blur-xl shadow-sm h-14' => $isModule,
        'h-[52px]' => !$isModule,
    ])
    @if(!$isModule)
        :class="scrolled
            ? 'bg-white/95 dark:bg-[#080e1a]/95 border-b border-slate-200/80 dark:border-white/5 backdrop-blur-xl shadow-sm'
            : 'bg-transparent border-b border-transparent'"
    @endif
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center gap-3">

        {{-- IZQUIERDA --}}
        <div class="flex items-center gap-2 {{ $isModule ? 'min-w-0' : 'flex-shrink-0' }}">

            @if(!$isModule)
                <button
                    onclick="if(history.length > 1) history.back();"
                    class="flex items-center justify-center w-7 h-7 rounded-lg
                           text-slate-300 dark:text-white/40
                           hover:text-white dark:hover:text-white
                           hover:bg-orvian-navy/80 dark:hover:bg-white/10
                           transition-all"
                    title="Volver a la página anterior">
                    <x-heroicon-s-chevron-right class="w-4 h-4" />
                </button>

            @else
                {{-- MOBILE: ☰ drawer --}}
                <button
                    @click="drawerOpen = true"
                    class="lg:hidden flex items-center justify-center w-9 h-9 rounded-xl
                           text-slate-500 dark:text-slate-400
                           hover:bg-slate-100 dark:hover:bg-white/5
                           transition-all flex-shrink-0"
                    title="Menú del módulo">
                    <x-heroicon-o-bars-3 class="w-5 h-5" />
                </button>

                {{-- DESKTOP: ícono flip a ‹ --}}
                <a
                    href="{{ route('app.dashboard') }}"
                    class="hidden lg:flex relative flex-shrink-0 w-9 h-9 rounded-xl overflow-hidden transition-all duration-200"
                    title="Volver al Hub"
                    style="perspective: 600px;"
                    x-data="{ hovered: false }"
                    @mouseenter="hovered = true"
                    @mouseleave="hovered = false"
                >
                    <div class="absolute inset-0 flex items-center justify-center rounded-xl transition-all duration-300"
                         :style="hovered ? 'transform: rotateY(-90deg); opacity: 0;' : 'transform: rotateY(0deg); opacity: 1;'">
                        @if($moduleIcon)
                            <x-ui.module-icon :name="$moduleIcon" class="w-8 h-8" />
                        @else
                            <div class="w-8 h-8 rounded-lg bg-orvian-orange/10 flex items-center justify-center">
                                <x-heroicon-o-squares-2x2 class="w-4 h-4 text-orvian-orange" />
                            </div>
                        @endif
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center rounded-xl transition-all duration-300"
                         :style="hovered ? 'transform: rotateY(0deg); opacity: 1;' : 'transform: rotateY(90deg); opacity: 0;'">
                        <x-heroicon-s-chevron-left class="w-5 h-5 text-slate-600 dark:text-slate-300" />
                    </div>
                </a>

                <span class="text-sm font-bold text-slate-800 dark:text-white flex-shrink-0">
                    {{ $module }}
                </span>

                @if(count($moduleLinks) > 0)
                    <nav class="hidden lg:flex items-center gap-0.5 ml-1">
                        @foreach($moduleLinks as $link)
                            <a href="{{ route($link['route']) }}"
                               @class([
                                   'px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                   'bg-orvian-orange/10 text-orvian-orange' => request()->routeIs($link['route']),
                                   'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-white/5' => !request()->routeIs($link['route']),
                               ])>
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>
                @endif
            @endif
        </div>

        {{-- CENTRO --}}
        <div class="flex-1 flex items-center justify-center">
            @if(!$isModule && Auth::user()->school)
                <span class="text-[13px] font-bold tracking-wide text-slate-600 dark:text-slate-300 truncate max-w-xs select-none">
                    {{ Auth::user()->school->name }}
                </span>
            @endif
        </div>

        {{-- DERECHA --}}
        <div class="flex items-center gap-1 flex-shrink-0">
            <button class="relative w-8 h-8 flex items-center justify-center rounded-lg
                           text-slate-400 dark:text-slate-500
                           hover:text-slate-700 dark:hover:text-slate-200
                           hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                <x-heroicon-o-bell class="w-4 h-4" />
                <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-orvian-orange ring-1 ring-white dark:ring-[#080e1a]"></span>
            </button>

            <div class="h-4 w-px bg-slate-200 dark:bg-white/10 mx-1"></div>

            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="flex items-center rounded-xl transition-colors p-0.5 hover:ring-2 hover:ring-slate-200 dark:hover:ring-white/10">
                    <x-ui.avatar :user="Auth::user()" size="sm" showStatus />
                </button>

                <div x-show="open"
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                     x-cloak
                     class="absolute right-0 top-full mt-2 w-60 rounded-2xl shadow-2xl border p-2 z-50
                            bg-white dark:bg-[#0f1828] border-slate-100 dark:border-white/8">

                    <div class="px-3 py-3 border-b border-slate-100 dark:border-white/5 flex items-center gap-3">
                        <x-ui.avatar :user="Auth::user()" size="md" showStatus />
                        <div class="min-w-0">
                            <p class="text-sm font-bold truncate text-slate-800 dark:text-slate-100">{{ Auth::user()->name }}</p>
                            <p class="text-[11px] truncate text-slate-400 dark:text-slate-500 mt-0.5">
                                {{ Auth::user()->position ?? Auth::user()->getRoleNames()->first() ?? Auth::user()->email }}
                            </p>
                        </div>
                    </div>

                    @livewire('shared.user-status')

                    <div class="my-1 border-t border-slate-100 dark:border-white/5"></div>

                    <a href="{{ route('app.profile') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm transition-colors group
                              text-slate-600 dark:text-slate-300
                              hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white">
                        <x-heroicon-o-user-circle class="w-4 h-4 opacity-40 group-hover:opacity-100 group-hover:text-orvian-orange transition-all" />
                        Mi Perfil
                    </a>

                    <div class="my-1 border-t border-slate-100 dark:border-white/5"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center gap-3 px-3 py-2 rounded-xl text-sm transition-colors group
                                       text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/25">
                            <x-heroicon-o-arrow-left-on-rectangle class="w-4 h-4 opacity-60 group-hover:opacity-100" />
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</header>

{{-- DRAWER MOBILE --}}
@if($isModule)
    <div x-show="drawerOpen" x-cloak @click="drawerOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm lg:hidden"></div>

    <div x-show="drawerOpen" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         class="fixed top-0 left-0 bottom-0 z-50 w-72 flex flex-col
                bg-white dark:bg-[#0f1828] border-r border-slate-100 dark:border-white/8 shadow-2xl lg:hidden">

        <div class="flex items-center justify-between px-4 py-4 border-b border-slate-100 dark:border-white/8 flex-shrink-0">
            <a href="{{ route('app.dashboard') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold
                      text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                <x-heroicon-s-chevron-left class="w-4 h-4 text-orvian-orange" />
                Volver al Hub
            </a>
            <button @click="drawerOpen = false"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                <x-heroicon-s-x-mark class="w-4 h-4" />
            </button>
        </div>

        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-white/8 flex-shrink-0">
            @if($moduleIcon)
                <x-ui.module-icon :name="$moduleIcon" class="w-10 h-10 flex-shrink-0" />
            @endif
            <p class="text-base font-bold text-slate-800 dark:text-white">{{ $module }}</p>
        </div>

        @if(count($moduleLinks) > 0)
            <nav class="flex-1 overflow-y-auto custom-scroll px-3 py-3 space-y-1">
                @foreach($moduleLinks as $link)
                    <a href="{{ route($link['route']) }}" @click="drawerOpen = false"
                       @class([
                           'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all',
                           'bg-orvian-orange/10 text-orvian-orange' => request()->routeIs($link['route']),
                           'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5' => !request()->routeIs($link['route']),
                       ])>
                        <span @class(['w-1.5 h-1.5 rounded-full flex-shrink-0', 'bg-orvian-orange' => request()->routeIs($link['route']), 'bg-slate-300 dark:bg-slate-600' => !request()->routeIs($link['route'])])></span>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
        @else
            <div class="flex-1"></div>
        @endif
    </div>
@endif

</div>