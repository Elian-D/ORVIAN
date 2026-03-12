@props([
    'icon' => 'heroicon-o-circle-stacka',
    'title' => 'No hay datos para mostrar',
    'description' => 'Parece que aún no hay información registrada en esta sección.',
    'actionLabel' => null,
    'actionClick' => null,
    'variant' => 'dashed' // dashed o simple
])

<div {{ $attributes->merge([
    'class' => 'flex flex-col items-center justify-center py-16 px-6 ' . 
               ($variant === 'dashed' ? 'border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl' : '')
]) }}>
    {{-- Icono con el círculo de fondo --}}
    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800/50 rounded-full flex items-center justify-center mb-6">
        <x-dynamic-component :component="$icon" class="w-8 h-8 text-slate-400 dark:text-slate-500" />
    </div>

    {{-- Texto --}}
    <h4 class="text-xl font-bold text-orvian-navy dark:text-slate-100 mb-2">{{ $title }}</h4>
    <p class="text-slate-500 dark:text-slate-400 text-center max-w-sm text-sm leading-relaxed">
        {{ $description }}
    </p>

    {{-- Botón de Acción (Usando tu nuevo x-ui.button) --}}
    @if($actionLabel)
        <div class="mt-8">
            <x-ui.button 
                variant="primary" 
                size="md" 
                hoverEffect 
                iconLeft="heroicon-s-plus-circle"
                wire:click="{{ $actionClick }}"
            >
                {{ $actionLabel }}
            </x-ui.button>
        </div>
    @endif
</div>