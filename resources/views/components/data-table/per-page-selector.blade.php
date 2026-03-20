{{--
    resources/views/components/data-table/per-page-selector.blade.php
    ------------------------------------------------------------------
    CORRECCIÓN: wire:model.live="perPage" — el .live es obligatorio para
    que Livewire detecte el cambio en tiempo real sin esperar submit.
    El DataTable base tiene updatedPerPage() que llama resetPage().

    PROPS:
      options — array de enteros (default: [10, 25, 50, 100])
--}}

@props([
    'options' => [10, 25, 50, 100],
])

<div class="flex items-center gap-2 flex-shrink-0">
    <span class="text-xs font-medium text-slate-400 dark:text-slate-500 whitespace-nowrap hidden sm:block">
        Mostrar
    </span>

    <div class="relative">
        <select
            wire:model.live="perPage"
            class="appearance-none pl-3 pr-7 py-2 text-sm font-semibold rounded-xl border
                   cursor-pointer transition-all duration-200 focus:outline-none focus:ring-0
                   bg-white dark:bg-dark-card
                   border-slate-200 dark:border-dark-border
                   text-slate-700 dark:text-slate-200
                   hover:border-slate-300 dark:hover:border-white/20
                   focus:border-orvian-orange/50 dark:focus:border-orvian-orange/40">
            @foreach($options as $opt)
                {{--
                    El :selected aquí es innecesario porque wire:model.live
                    ya sincroniza el valor seleccionado desde Livewire.
                    Laravel/Livewire maneja el selected automáticamente.
                --}}
                <option value="{{ $opt }}">{{ $opt }}</option>
            @endforeach
        </select>
    </div>
</div>