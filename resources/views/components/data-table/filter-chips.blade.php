{{--
    resources/views/components/data-table/filter-chips.blade.php
    -------------------------------------------------------------
    Chips de filtros activos. Se renderiza automáticamente debajo de la toolbar.
    No renderiza nada si no hay filtros activos.

    PROPS:
      chips      — array de [['key', 'label', 'value'], ...] — viene de getActiveChips()
      hasFilters — bool — viene de count(array_filter($filters)) > 0

    USO (en base-table.blade.php):
      <x-data-table.filter-chips
          :chips="$activeChips"
          :hasFilters="$hasFilters"
      />
--}}

@props([
    'chips'      => [],
    'hasFilters' => false,
])

@if($hasFilters && count($chips) > 0)
    <div class="flex flex-wrap items-center gap-2 px-1 pb-3 -mt-1">

        {{-- Chip individual --}}
        @foreach($chips as $chip)
            <span class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1
                         rounded-full border text-xs font-semibold
                         bg-orvian-orange/8 dark:bg-orvian-orange/10
                         border-orvian-orange/25 dark:border-orvian-orange/20
                         text-orvian-orange">

                {{-- Label: Valor --}}
                <span class="text-orvian-orange/60 font-medium">{{ $chip['label'] }}:</span>
                <span>{{ $chip['value'] }}</span>

                {{-- Botón × para limpiar este filtro --}}
                <button
                    wire:click="clearFilter('{{ $chip['key'] }}')"
                    class="flex items-center justify-center w-4 h-4 rounded-full
                           text-orvian-orange/60 hover:text-orvian-orange
                           hover:bg-orvian-orange/15
                           transition-all duration-150"
                    title="Quitar filtro {{ $chip['label'] }}">
                    <x-heroicon-s-x-mark class="w-2.5 h-2.5" />
                </button>
            </span>
        @endforeach

        {{-- Limpiar todo —— separado visualmente del último chip --}}
        <button
            wire:click="clearAllFilters"
            class="text-xs font-semibold text-slate-400 dark:text-slate-500
                   hover:text-orvian-orange dark:hover:text-orvian-orange
                   transition-colors duration-200 ml-1">
            Limpiar todo
        </button>

    </div>
@endif