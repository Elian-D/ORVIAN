{{--
    resources/views/components/app/search.blade.php
    ------------------------------------------------
    Buscador contextual del navbar en estado módulo.
    Componente anónimo — sin clase PHP.

    PROPS:
      module  — nombre del módulo activo (para el placeholder)
      size    — 'sm' (navbar) | 'md' (toolbar expandido). Default: sm

    USO en navbar (dentro de app-module.blade.php):
        <x-app.search :module="$module" />

    USO en toolbar con tamaño mayor:
        <x-app.search :module="$module" size="md" />

    FUTURO — cuando se implemente la búsqueda real, este componente
    puede convertirse en un componente Livewire que recibe resultados
    y los muestra en un dropdown. Por ahora es el input visual.
--}}

@props([
    'module' => null,
    'size'   => 'sm',
])

@php
    $placeholder = $module ? "Buscar en {$module}..." : 'Buscar...';

    $inputClasses = match($size) {
        'md'    => 'pl-9 pr-4 py-2 text-sm rounded-xl',
        default => 'pl-8 pr-3 py-1.5 text-xs rounded-lg',
    };

    $iconClasses = match($size) {
        'md'    => 'left-3 w-4 h-4',
        default => 'left-2.5 w-3.5 h-3.5',
    };
@endphp

<div {{ $attributes->class(['relative group w-full']) }}>
    {{-- Ícono lupa --}}
    <x-heroicon-o-magnifying-glass
        class="absolute top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500
               group-focus-within:text-orvian-orange transition-colors pointer-events-none
               {{ $iconClasses }}"
    />

    {{-- Input --}}
    <input
        type="text"
        placeholder="{{ $placeholder }}"
        class="w-full border transition-all duration-200 focus:outline-none focus:ring-0
               bg-slate-100 dark:bg-white/5
               border-slate-200 dark:border-slate-600
               text-slate-700 dark:text-slate-200
               placeholder:text-slate-400 dark:placeholder:text-slate-500
               focus:border-orvian-orange/40
               focus:bg-white dark:focus:bg-white/8
               {{ $inputClasses }}"
    />

    {{-- Atajo de teclado — decorativo por ahora --}}
    <div class="absolute right-2.5 top-1/2 -translate-y-1/2
                hidden group-focus-within:hidden sm:flex
                items-center gap-0.5 pointer-events-none">
        <kbd class="text-[10px] font-medium text-slate-300 dark:text-slate-600
                    px-1 py-0.5 rounded border border-slate-200 dark:border-slate-600
                    bg-white dark:bg-white/5 leading-none">
            ⌘K
        </kbd>
    </div>
</div>