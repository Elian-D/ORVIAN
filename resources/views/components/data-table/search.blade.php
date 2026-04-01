{{--
    resources/views/components/data-table/search.blade.php
    -------------------------------------------------------
    Buscador de la toolbar. Ocupa el espacio disponible (flex-1).

    PROPS:
      placeholder — texto del input (default: 'Buscar...')
      filterKey   — clave en $filters (default: 'search')

    USO:
      <x-data-table.search />
      <x-data-table.search placeholder="Buscar por nombre..." />
--}}

@props([
    'placeholder' => 'Buscar...',
    'filterKey'   => 'search',
])

<div class="relative group flex-1 min-w-0 max-w-sm">
    <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none
                 text-slate-400 dark:text-slate-500
                 group-focus-within:text-orvian-orange transition-colors duration-200">
        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
    </span>

    <input
        type="text"
        wire:model.live.debounce.300ms="filters.{{ $filterKey }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        class="w-full pl-9 pr-8 py-2 text-sm rounded-xl border
               transition-all duration-200 focus:outline-none focus:ring-0
               bg-white dark:bg-dark-card
               border-slate-200 dark:border-dark-border
               text-slate-700 dark:text-slate-200
               placeholder:text-slate-400 dark:placeholder:text-slate-500
               focus:border-orvian-orange/50 dark:focus:border-orvian-orange/40"
    />

    {{-- Limpiar: visible solo si hay texto (Alpine lee la propiedad Livewire) --}}
    <button
        x-show="$wire.filters?.{{ $filterKey }} !== '' && $wire.filters?.{{ $filterKey }} != null"
        x-cloak
        wire:click="$set('filters.{{ $filterKey }}', '')"
        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 rounded
               text-slate-300 dark:text-slate-600
               hover:text-orvian-orange dark:hover:text-orvian-orange
               transition-colors duration-200"
        title="Limpiar búsqueda">
        <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
    </button>
</div>