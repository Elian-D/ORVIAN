{{--
    resources/views/components/data-table/filter-date-range.blade.php
    ------------------------------------------------------------------
    Rango de fechas. Se usa dentro de filter-container.
    Dos inputs date: desde / hasta, con validación visual si desde > hasta.

    PROPS:
      label   — título del grupo
      fromKey — clave en $filters para la fecha de inicio
      toKey   — clave en $filters para la fecha de fin

    USO:
      <x-data-table.filter-date-range
          label="Último acceso"
          fromKey="date_from"
          toKey="date_to"
      />
--}}

@props([
    'label'   => '',
    'fromKey' => 'date_from',
    'toKey'   => 'date_to',
])

<div class="space-y-2">
    @if($label)
        <label class="block text-[11px] font-bold uppercase tracking-wider
                      text-slate-400 dark:text-slate-500">
            {{ $label }}
        </label>
    @endif

    <div class="grid grid-cols-2 gap-2">
        {{-- Desde --}}
        <div class="space-y-1">
            <p class="text-[10px] font-medium text-slate-400 dark:text-slate-500">Desde</p>
            <input
                type="date"
                wire:model.live="filters.{{ $fromKey }}"
                class="w-full px-3 py-2 text-sm rounded-xl border
                       transition-all duration-200 focus:outline-none focus:ring-0
                       bg-white dark:bg-dark-bg
                       border-slate-200 dark:border-dark-border
                       text-slate-700 dark:text-slate-200
                       focus:border-orvian-orange/50 dark:focus:border-orvian-orange/40
                       [color-scheme:light] dark:[color-scheme:dark]"
            />
        </div>

        {{-- Hasta --}}
        <div class="space-y-1">
            <p class="text-[10px] font-medium text-slate-400 dark:text-slate-500">Hasta</p>
            <input
                type="date"
                wire:model.live="filters.{{ $toKey }}"
                class="w-full px-3 py-2 text-sm rounded-xl border
                       transition-all duration-200 focus:outline-none focus:ring-0
                       bg-white dark:bg-dark-bg
                       border-slate-200 dark:border-dark-border
                       text-slate-700 dark:text-slate-200
                       focus:border-orvian-orange/50 dark:focus:border-orvian-orange/40
                       [color-scheme:light] dark:[color-scheme:dark]"
            />
        </div>
    </div>

    {{-- Hint de limpieza si hay valores --}}
    <div x-show="$wire.filters?.{{ $fromKey }} || $wire.filters?.{{ $toKey }}"
         x-cloak
         class="flex justify-end">
        <button
            wire:click="$set('filters.{{ $fromKey }}', ''); $set('filters.{{ $toKey }}', '')"
            class="text-[11px] font-medium text-slate-400 dark:text-slate-500
                   hover:text-orvian-orange dark:hover:text-orvian-orange
                   transition-colors duration-200">
            Limpiar fechas
        </button>
    </div>
</div>