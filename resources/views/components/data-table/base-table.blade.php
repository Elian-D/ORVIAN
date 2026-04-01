@props([
    'items'          => null,
    'definition'     => null,
    'visibleColumns' => [],
    'activeChips'    => [],
    'hasFilters'     => false,
])

<div class="flex flex-col w-full gap-2">

    {{-- ══════════════════════════════════════════════════
         TOOLBAR
    ═══════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-2 flex-wrap">
        <div class="flex-1 min-w-[180px]">
            <x-data-table.search />
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <x-data-table.per-page-selector />
            @if(isset($filterSlot))
                {{ $filterSlot }}
            @endif
            <x-data-table.column-selector
                :definition="$definition"
                :visibleColumns="$visibleColumns" />
        </div>
    </div>

    {{-- CHIPS --}}
    <x-data-table.filter-chips :chips="$activeChips" :hasFilters="$hasFilters" />

    {{-- ══════════════════════════════════════════════════
         TABLA con overlay de carga contextual
         
         Capas:
           1. wire:loading.class → baja opacidad inmediata (feedback < 200ms)
           2. wire:loading.delay.long → overlay con blur + badge (> 200ms)
           
         El overlay cubre solo la tabla, no el toolbar ni la paginación.
         El usuario sigue viendo sus datos "congelados" mientras espera.
    ═══════════════════════════════════════════════════ --}}
    <div class="relative">

        {{-- ── Nivel 1: atenuación inmediata del contenedor ──────
             Se activa sin delay — feedback instantáneo al click.
             No parpadea porque es solo opacidad, no un overlay nuevo.
        ─────────────────────────────────────────────────────── --}}
        <div
            wire:loading.class="opacity-60 pointer-events-none"
            wire:target="filters,perPage,toggleColumn,resetColumns,gotoPage,nextPage,previousPage,clearFilter,clearAllFilters"
            class="w-full overflow-x-auto rounded-xl border shadow-sm custom-scroll
                   bg-white dark:bg-dark-card
                   border-slate-200 dark:border-dark-border
                   transition-opacity duration-150">

            <table class="w-full min-w-full divide-y divide-slate-100 dark:divide-dark-border">
                <thead class="bg-slate-50/80 dark:bg-white/[0.03]">
                    <tr>
                        @if($definition)
                            @foreach($definition::allColumns() as $key => $label)
                                @if(in_array($key, $visibleColumns))
                                    <th scope="col"
                                        class="px-4 py-3.5 text-left text-[11px] font-bold
                                               uppercase tracking-wider
                                               text-slate-500 dark:text-slate-400">
                                        {{ $label }}
                                    </th>
                                @endif
                            @endforeach
                        @endif
                        <th class="px-4 py-3.5 text-right text-[11px] font-bold uppercase
                                   tracking-wider text-slate-500 dark:text-slate-400">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dark-border">
                    {{ $slot }}
                </tbody>
            </table>
        </div>

        {{-- ── Nivel 2: overlay con blur + badge (solo si tarda > 200ms) ──
             wire:loading.delay.long = 500ms según Livewire.
             El usuario no lo ve en peticiones rápidas — sin flash molesto.
        ─────────────────────────────────────────────────────────────── --}}
        <div
            wire:loading.flex.delay.long
            wire:target="filters,perPage,toggleColumn,resetColumns,gotoPage,nextPage,previousPage,clearFilter,clearAllFilters"
            style="display:none;"
            class="absolute inset-0 rounded-xl z-20
                   flex items-center justify-center
                   cursor-wait
                   bg-white/50 dark:bg-dark-card/50
                   backdrop-blur-[2px]">

            {{-- Badge flotante centrado --}}
            <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-2xl
                        bg-white dark:bg-dark-card
                        border border-slate-200 dark:border-dark-border
                        shadow-xl shadow-black/10 dark:shadow-black/30">

                {{-- Spinner de trazo fino --}}
                <svg class="w-4 h-4 text-orvian-orange animate-spin flex-shrink-0"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="3"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>

                <span class="text-[11px] font-bold uppercase tracking-widest
                             text-slate-500 dark:text-slate-400">
                    Actualizando
                </span>
            </div>
        </div>

    </div>{{-- /relative --}}

    {{-- ══════════════════════════════════════════════════
         FOOTER — contador + paginación
         Separado del relative para no ser cubierto por el overlay.
    ═══════════════════════════════════════════════════ --}}
    @if($items && ($items->hasPages() || $items->total() > 0))
        {{ $items->links() }}
    @endif

</div>