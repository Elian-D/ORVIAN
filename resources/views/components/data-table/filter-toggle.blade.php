{{--
    resources/views/components/data-table/filter-toggle.blade.php
    -------------------------------------------------------------
    Toggle de filtro booleano. Se usa dentro de filter-container.
    Diseño horizontal: label a la izquierda, toggle a la derecha.
    Visualmente distinto de filter-select: no tiene select, solo un switch.

    PROPS:
      label       — descripción del filtro
      filterKey   — clave en $filters del componente Livewire
      description — texto auxiliar pequeño bajo el label (opcional)

    USO:
      <x-data-table.filter-toggle
          label="Solo activos"
          filterKey="only_active"
          description="Ocultar usuarios desactivados"
      />
--}}

@props([
    'label'       => '',
    'filterKey'   => '',
    'description' => null,
])

<div class="flex items-center justify-between gap-4 py-0.5"
     x-data="{ value: $wire.filters?.{{ $filterKey }} ?? false }">

    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 leading-none">
            {{ $label }}
        </p>
        @if($description)
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                {{ $description }}
            </p>
        @endif
    </div>

    {{-- Toggle visual --}}
    <button
        type="button"
        wire:click="$set('filters.{{ $filterKey }}', !{{ json_encode((bool) false) }})"
        x-on:click="value = !value"
        class="relative flex-shrink-0 w-10 h-6 rounded-full border-2 transition-all duration-300
               focus:outline-none focus:ring-2 focus:ring-orvian-orange/30 focus:ring-offset-1
               dark:focus:ring-offset-dark-card"
        :class="value
            ? 'bg-orvian-orange border-orvian-orange'
            : 'bg-slate-200 dark:bg-dark-border border-slate-200 dark:border-dark-border'"
        x-init="$watch('$wire.filters.{{ $filterKey }}', v => value = v)"
    >
        <span
            class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm
                   transition-transform duration-300"
            :class="value ? 'translate-x-4' : 'translate-x-0'"
        ></span>
    </button>
</div>