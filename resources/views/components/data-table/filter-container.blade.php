{{--
    resources/views/components/data-table/filter-container.blade.php
    -----------------------------------------------------------------
    Contenedor dropdown para los filtros del módulo.
    En mobile (< md) se convierte en un panel deslizante desde abajo (drawer)
    para evitar que el dropdown se salga de la pantalla.

    SLOT por defecto: los filtros internos (filter-select, filter-toggle, etc.)

    USO:
      <x-data-table.filter-container :activeCount="count(array_filter($filters))">
          <x-data-table.filter-select label="Rol" filterKey="role" :options="$roleOptions" />
          <x-data-table.filter-toggle label="Solo activos" filterKey="active" />
      </x-data-table.filter-container>

    NOTA: activeCount se puede pasar desde el componente Livewire o calcularlo
    directamente: :activeCount="count(array_filter($this->filters))"
--}}

@props([
    'activeCount' => 0,
])

<div
    x-data="{
        open: false,
        isMobile: window.innerWidth < 768,
        init() {
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth < 768;
                if (!this.isMobile) this.open = false;
            });
        }
    }"
    class="relative flex-shrink-0"
>

    {{-- ── Trigger button ── --}}
    <button
        @click="open = !open"
        @class([
            'flex items-center gap-2 px-3 py-2 rounded-xl border text-sm font-semibold
             transition-all duration-200 focus:outline-none',
            // Activo: borde y fondo naranja suave
            'border-orvian-orange/40 bg-orvian-orange/8 text-orvian-orange dark:bg-orvian-orange/10' => $activeCount > 0,
            // Inactivo
            'border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card
             text-slate-600 dark:text-slate-300
             hover:border-slate-300 dark:hover:border-white/20
             hover:text-slate-800 dark:hover:text-white' => $activeCount === 0,
        ])
        :class="open && !isMobile ? 'border-orvian-orange/40 bg-orvian-orange/5 dark:bg-orvian-orange/10' : ''"
    >
        <x-heroicon-o-adjustments-horizontal class="w-4 h-4" />
        <span class="hidden sm:block">Filtros</span>

        {{-- Badge de filtros activos --}}
        @if($activeCount > 0)
            <span class="flex items-center justify-center w-5 h-5 rounded-full
                         bg-orvian-orange text-white text-[10px] font-black leading-none
                         flex-shrink-0">
                {{ $activeCount }}
            </span>
        @endif

        <x-heroicon-s-chevron-down
            class="w-3.5 h-3.5 transition-transform duration-200 hidden sm:block"
            ::class="open && !isMobile ? 'rotate-180' : ''" />
    </button>

    {{-- ══════════════════════════════════════════════════
         DESKTOP DROPDOWN (md+)
         Posicionado absoluto debajo del trigger.
         @click.away cierra al hacer click fuera.
    ═══════════════════════════════════════════════════ --}}
    <div
        x-show="open && !isMobile"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
        x-cloak
        class="absolute right-0 top-full mt-2 z-50
               w-72 rounded-2xl border shadow-2xl
               bg-white dark:bg-dark-card
               border-slate-100 dark:border-dark-border"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3
                    border-b border-slate-100 dark:border-dark-border">
            <p class="text-xs font-bold uppercase tracking-wider
                      text-slate-500 dark:text-slate-400">
                Filtros
            </p>
            @if($activeCount > 0)
                <button
                    wire:click="clearAllFilters"
                    @click="open = false"
                    class="text-[11px] font-semibold text-orvian-orange hover:text-orvian-orange/80
                           transition-colors duration-200">
                    Limpiar todo
                </button>
            @endif
        </div>

        {{-- Filtros internos --}}
        <div class="p-4 space-y-4 max-h-[60vh] overflow-y-auto custom-scroll">
            {{ $slot }}
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         MOBILE DRAWER (< md)
         Overlay + panel desde abajo. Más usable en touch.
    ═══════════════════════════════════════════════════ --}}

    {{-- Overlay --}}
    <div
        x-show="open && isMobile"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click="open = false"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm md:hidden"
    ></div>

    {{-- Panel --}}
    <div
        x-show="open && isMobile"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        x-cloak
        class="fixed bottom-0 left-0 right-0 z-50 rounded-t-3xl shadow-2xl
               bg-white dark:bg-dark-card
               border-t border-slate-100 dark:border-dark-border
               md:hidden"
    >
        {{-- Handle + Header --}}
        <div class="flex flex-col items-center pt-3 pb-2">
            <div class="w-10 h-1 rounded-full bg-slate-200 dark:bg-dark-border mb-3"></div>
            <div class="w-full flex items-center justify-between px-5 pb-2
                        border-b border-slate-100 dark:border-dark-border">
                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Filtros</p>
                <div class="flex items-center gap-3">
                    @if($activeCount > 0)
                        <button
                            wire:click="clearAllFilters"
                            @click="open = false"
                            class="text-xs font-semibold text-orvian-orange">
                            Limpiar todo
                        </button>
                    @endif
                    <button @click="open = false"
                            class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">
                        <x-heroicon-s-x-mark class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Filtros internos --}}
        <div class="px-5 py-4 space-y-5 max-h-[70vh] overflow-y-auto pb-safe">
            {{ $slot }}
        </div>

        {{-- Botón aplicar (mobile) --}}
        <div class="px-5 pb-6 pt-3 border-t border-slate-100 dark:border-dark-border">
            <button
                @click="open = false"
                class="w-full py-3 rounded-xl bg-orvian-orange text-white text-sm font-bold
                       hover:opacity-90 active:scale-[0.98] transition-all duration-200">
                Aplicar filtros
            </button>
        </div>
    </div>

</div>