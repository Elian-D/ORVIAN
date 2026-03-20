{{--
    resources/views/components/data-table/filter-range.blade.php
    ------------------------------------------------------------
    Rango numérico (precio, cantidad, etc.). Se usa dentro de filter-container.

    PROPS:
      label   — título del grupo
      fromKey — clave en $filters para el mínimo
      toKey   — clave en $filters para el máximo
      prefix  — prefijo antes del número (ej: 'RD$')
      suffix  — sufijo después del número (ej: 'kg', '%')
      min     — valor mínimo del input (default: 0)
      step    — paso del input (default: 1)

    USO:
      <x-data-table.filter-range
          label="Precio"
          fromKey="price_min"
          toKey="price_max"
          prefix="RD$"
      />
--}}

@props([
    'label'   => '',
    'fromKey' => 'range_min',
    'toKey'   => 'range_max',
    'prefix'  => null,
    'suffix'  => null,
    'min'     => 0,
    'step'    => 1,
])

<div class="space-y-2">
    @if($label)
        <label class="block text-[11px] font-bold uppercase tracking-wider
                      text-slate-400 dark:text-slate-500">
            {{ $label }}
        </label>
    @endif

    <div class="grid grid-cols-2 gap-2">
        {{-- Mínimo --}}
        <div class="space-y-1">
            <p class="text-[10px] font-medium text-slate-400 dark:text-slate-500">Mínimo</p>
            <div class="relative">
                @if($prefix)
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs
                                 text-slate-400 dark:text-slate-500 pointer-events-none">
                        {{ $prefix }}
                    </span>
                @endif
                <input
                    type="number"
                    wire:model.live="filters.{{ $fromKey }}"
                    min="{{ $min }}"
                    step="{{ $step }}"
                    placeholder="0"
                    class="w-full py-2 text-sm rounded-xl border
                           transition-all duration-200 focus:outline-none focus:ring-0
                           bg-white dark:bg-dark-bg
                           border-slate-200 dark:border-dark-border
                           text-slate-700 dark:text-slate-200
                           focus:border-orvian-orange/50 dark:focus:border-orvian-orange/40
                           {{ $prefix ? 'pl-8 pr-3' : 'px-3' }}
                           {{ $suffix ? 'pr-8' : '' }}"
                />
                @if($suffix)
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs
                                 text-slate-400 dark:text-slate-500 pointer-events-none">
                        {{ $suffix }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Máximo --}}
        <div class="space-y-1">
            <p class="text-[10px] font-medium text-slate-400 dark:text-slate-500">Máximo</p>
            <div class="relative">
                @if($prefix)
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs
                                 text-slate-400 dark:text-slate-500 pointer-events-none">
                        {{ $prefix }}
                    </span>
                @endif
                <input
                    type="number"
                    wire:model.live="filters.{{ $toKey }}"
                    min="{{ $min }}"
                    step="{{ $step }}"
                    placeholder="∞"
                    class="w-full py-2 text-sm rounded-xl border
                           transition-all duration-200 focus:outline-none focus:ring-0
                           bg-white dark:bg-dark-bg
                           border-slate-200 dark:border-dark-border
                           text-slate-700 dark:text-slate-200
                           focus:border-orvian-orange/50 dark:focus:border-orvian-orange/40
                           {{ $prefix ? 'pl-8 pr-3' : 'px-3' }}
                           {{ $suffix ? 'pr-8' : '' }}"
                />
                @if($suffix)
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs
                                 text-slate-400 dark:text-slate-500 pointer-events-none">
                        {{ $suffix }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Limpiar --}}
    <div x-show="$wire.filters?.{{ $fromKey }} || $wire.filters?.{{ $toKey }}"
         x-cloak class="flex justify-end">
        <button
            wire:click="$set('filters.{{ $fromKey }}', ''); $set('filters.{{ $toKey }}', '')"
            class="text-[11px] font-medium text-slate-400 dark:text-slate-500
                   hover:text-orvian-orange dark:hover:text-orvian-orange transition-colors">
            Limpiar rango
        </button>
    </div>
</div>