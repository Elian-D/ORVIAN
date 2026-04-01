{{--
    resources/views/components/data-table/filter-select.blade.php
    -------------------------------------------------------------
    Select de filtro. Se usa dentro de x-data-table.filter-container.
    Visualmente diferente al per-page-selector: tiene label encima,
    ocupa el ancho completo del dropdown, y el estado activo es naranja.

    PROPS:
      label     — texto del label
      filterKey — clave en $filters del componente Livewire
      options   — array asociativo ['valor' => 'Label visible']
      placeholder — opción vacía (default: 'Todos')

    USO:
      <x-data-table.filter-select
          label="Rol"
          filterKey="role"
          :options="['Owner' => 'Owner', 'TechnicalSupport' => 'Soporte']"
      />
--}}

@props([
    'label'       => '',
    'filterKey'   => '',
    'options'     => [],
    'placeholder' => 'Todos',
])

<div class="space-y-1.5">
    @if($label)
        <label class="block text-[11px] font-bold uppercase tracking-wider
                      text-slate-400 dark:text-slate-500">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <select
            wire:model.live="filters.{{ $filterKey }}"
            class="w-full appearance-none pl-3 pr-8 py-2.5 text-sm rounded-xl border
                   cursor-pointer transition-all duration-200 focus:outline-none focus:ring-0
                   bg-white dark:bg-dark-bg
                   border-slate-200 dark:border-dark-border
                   text-slate-700 dark:text-slate-200
                   focus:border-orvian-orange/50 dark:focus:border-orvian-orange/40"
            :class="$wire.filters?.{{ $filterKey }} !== '' && $wire.filters?.{{ $filterKey }} != null
                ? 'border-orvian-orange/40 bg-orvian-orange/5  text-orvian-orange font-semibold'
                : ''"
        >
            <option value="">{{ $placeholder }}</option>
            @foreach($options as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>