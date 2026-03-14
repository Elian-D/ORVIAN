{{--
    resources/views/components/app/navbar.blade.php
    -----------------------------------------------
    ⚠️  IMPORTANTE — COMPATIBILIDAD LIVEWIRE:
        Este componente SOLO debe usarse en layouts (app.blade.php).
        NUNCA dentro de una vista de un componente Livewire.
        El @livewire('shared.user-status') anidado dentro de un componente
        Livewire genera conflictos de scope que rompen el dropdown.

    DOS ESTADOS:
    ─────────────────────────────────────────────────────────────────
    ESTADO HUB  (module = null):
      · Transparente en el top, sólido al hacer scroll
      · Logo + botón "›" para volver a página anterior (si hay historial)
      · Solo avatar en la derecha (sin nombre/chevron — más limpio)

    ESTADO MÓDULO (module = 'Nombre'):
      · Siempre sólido — el módulo tiene su propio contexto visual
      · Izquierda: "‹ Hub" | ícono | nombre | sub-links del módulo
      · Centro: buscador contextual del módulo
      · Derecha: notificaciones | avatar + dropdown completo

    USO HUB (en app.blade.php):
        <x-app.navbar />

    USO MÓDULO (en el layout del módulo o en app.blade.php con datos):
        <x-app.navbar
            module="Matrículas"
            moduleIcon="heroicon-o-academic-cap"
            :moduleLinks="[
                ['label' => 'Estudiantes', 'route' => 'app.students.index'],
                ['label' => 'Secciones',   'route' => 'app.sections.index'],
            ]"
        />
--}}

@props([
    'module'      => null,
    'moduleIcon'  => null,
    'moduleLinks' => [],
])

@php $isModule = !is_null($module); @endphp

<header
    x-data="{
        scrolled: false,
        init() {
            {{-- Inicia ya scrolled si la página se cargó con scroll (ej: recarga a mitad) --}}
            this.scrolled = window.scrollY > 10;
            window.addEventListener('scroll', () => { this.scrolled = window.scrollY > 10; }, { passive: true });
        }
    }"
    @class([
        'sticky top-0 z-50 transition-all duration-300',
        {{-- Módulo: siempre sólido --}}
        'bg-white/95 dark:bg-[#0c1220]/95 border-b border-slate-200 dark:border-white/5 backdrop-blur-xl shadow-sm h-14' => $isModule,
        {{-- Hub: transparente hasta scroll — Alpine alterna las clases --}}
        'h-[52px]' => !$isModule,
    ])
    @if(!$isModule)
        :class="scrolled
            ? 'bg-white/95 dark:bg-[#080e1a]/95 border-b border-slate-200/80 dark:border-white/5 backdrop-blur-xl shadow-sm'
            : 'bg-transparent border-b border-transparent'"
    @endif
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center gap-3">

        {{-- ══════════════════════════════════════
             SECCIÓN IZQUIERDA
        ═══════════════════════════════════════ --}}
        <div class="flex items-center gap-2 flex-1 min-w-0">

            @if(!$isModule)
                {{-- ── ESTADO HUB ── --}}

                {{-- Logo --}}
                <a href="{{ route('app.dashboard') }}" class="flex-shrink-0">
                    <x-application-logo type="full" mode="dynamic" class="h-9 w-auto" />
                </a>

                {{-- Botón "›" volver atrás — solo si hay historial de navegación --}}
                <button
                    onclick="if(history.length > 1) history.back();"
                    x-show="window.history.length > 1"
                    class="hidden sm:flex items-center justify-center w-7 h-7 rounded-lg
                           text-slate-300 dark:text-slate-700
                           hover:text-slate-500 dark:hover:text-slate-400
                           hover:bg-slate-100 dark:hover:bg-white/5
                           transition-all ml-1"
                    title="Volver a la página anterior">
                    <x-heroicon-s-chevron-right class="w-3.5 h-3.5" />
                </button>

            @else
                {{-- ── ESTADO MÓDULO ── --}}

                {{-- Botón "‹ Hub" --}}
                <a href="{{ route('app.dashboard') }}"
                   class="flex items-center gap-1.5 flex-shrink-0 px-2.5 py-1.5 rounded-lg
                          text-slate-400 dark:text-slate-500
                          hover:text-slate-700 dark:hover:text-slate-200
                          hover:bg-slate-100 dark:hover:bg-white/5
                          transition-all text-xs font-semibold group">
                    <x-heroicon-s-chevron-left class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform" />
                    <span class="hidden sm:block">Hub</span>
                </a>

                {{-- Divisor --}}
                <div class="h-4 w-px bg-slate-200 dark:bg-white/10 flex-shrink-0"></div>

                {{-- Ícono + nombre del módulo --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($moduleIcon)
                        <div class="w-6 h-6 rounded-md bg-orvian-orange/10 flex items-center justify-center flex-shrink-0">
                            <x-dynamic-component :component="$moduleIcon" class="w-3.5 h-3.5 text-orvian-orange" />
                        </div>
                    @endif
                    <span class="text-sm font-bold text-slate-800 dark:text-white">
                        {{ $module }}
                    </span>
                </div>

                {{-- Sub-links del módulo (estilo tab compacto) --}}
                @if(count($moduleLinks) > 0)
                    <nav class="hidden lg:flex items-center gap-0.5 ml-2 pl-3 border-l border-slate-200 dark:border-white/10">
                        @foreach($moduleLinks as $link)
                            <a href="{{ route($link['route']) }}"
                               @class([
                                   'px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                   'bg-orvian-orange/10 text-orvian-orange'
                                       => request()->routeIs($link['route']),
                                   'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-white/5'
                                       => !request()->routeIs($link['route']),
                               ])>
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>
                @endif

            @endif
        </div>

        {{-- ══════════════════════════════════════
             SECCIÓN CENTRAL (solo módulo)
        ═══════════════════════════════════════ --}}
        @if($isModule)
            <div class="flex-1 max-w-xs hidden md:block">
                <x-app.search :module="$module" /> 
            </div>
        @endif

        {{-- ══════════════════════════════════════
             SECCIÓN DERECHA
        ═══════════════════════════════════════ --}}
        <div class="flex items-center gap-1 flex-shrink-0">

            @if($isModule)
                {{-- Notificaciones --}}
                <button class="relative w-8 h-8 flex items-center justify-center rounded-lg
                               text-slate-400 dark:text-slate-500
                               hover:text-slate-700 dark:hover:text-slate-200
                               hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                    <x-heroicon-o-bell class="w-4 h-4" />

                    <span class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-orvian-orange ring-1 ring-white dark:ring-[#0c1220]"></span>

                </button>
                <div class="h-4 w-px bg-slate-200 dark:bg-white/10 mx-1"></div>
            @endif

            {{-- ── Dropdown de usuario ── --}}
            <div class="relative" x-data="{ open: false }">

                {{-- Trigger — compacto en hub, con nombre en módulo --}}
                <button @click="open = !open"
                        @class([
                            'flex items-center rounded-xl transition-colors',
                            'gap-2 pl-1 pr-2.5 py-1 hover:bg-slate-100 dark:hover:bg-white/5' => $isModule,
                            'p-0.5 hover:ring-2 hover:ring-slate-200 dark:hover:ring-white/10' => !$isModule,
                        ])>
                    <x-ui.avatar :user="Auth::user()" size="sm" showStatus />
                    @if($isModule)
                        <span class="hidden sm:block text-[13px] font-semibold truncate max-w-[90px] text-slate-700 dark:text-slate-200">
                            {{ explode(' ', Auth::user()->name ?? 'Usuario')[0] }}
                        </span>
                        <x-heroicon-s-chevron-down
                            class="w-3 h-3 hidden sm:block text-slate-400 dark:text-slate-500 transition-transform duration-200"
                            ::class="{ 'rotate-180': open }" />
                    @endif
                </button>

                {{-- Panel del dropdown --}}
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
                            bg-white dark:bg-[#0f1828]
                            border-slate-100 dark:border-white/8">

                    {{-- Cabecera: avatar md + nombre + cargo/rol --}}
                    <div class="px-3 py-3 border-b border-slate-100 dark:border-white/5 flex items-center gap-3">
                        <x-ui.avatar :user="Auth::user()" size="md" showStatus />
                        <div class="min-w-0">
                            <p class="text-sm font-bold truncate text-slate-800 dark:text-slate-100">
                                {{ Auth::user()->name }}
                            </p>
                            <p class="text-[11px] truncate text-slate-400 dark:text-slate-500 mt-0.5">
                                {{ Auth::user()->position
                                    ?? Auth::user()->getRoleNames()->first()
                                    ?? Auth::user()->email }}
                            </p>
                        </div>
                    </div>

                    {{-- Selector de estado (Livewire) --}}
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