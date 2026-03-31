@props([
    'label'       => '',
    'filterKey'   => '',
    'description' => null,
])

<div class="flex items-center justify-between gap-4 py-0.5"
     x-data="{ 
        {{-- Entrelazamos el valor con Livewire en modo .live para tiempo real --}}
        value: $wire.entangle('filters.{{ $filterKey }}').live 
     }">

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
        @click="value = !value"
        class="relative flex-shrink-0 w-10 h-6 rounded-full border-2 transition-all duration-300
               focus:outline-none focus:ring-2 focus:ring-orvian-orange/30 focus:ring-offset-1
               dark:focus:ring-offset-dark-card"
        :class="value
            ? 'bg-orvian-orange border-orvian-orange'
            : 'bg-slate-200 dark:bg-dark-border border-slate-200 dark:border-dark-border'"
    >
        <span
            class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm
                   transition-transform duration-300"
            :class="value ? 'translate-x-4' : 'translate-x-0'"
        ></span>
    </button>
</div>