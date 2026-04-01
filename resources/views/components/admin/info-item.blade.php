@props(['label', 'value', 'icon'])

@php
    $hasValue = !empty($value);
    $displayValue = $value ?? 'No asignado';
@endphp

<div 
    x-data="{ showTooltip: false, timeout: null }"
    @mouseenter="timeout = setTimeout(() => { showTooltip = true }, 800)" 
    @mouseleave="clearTimeout(timeout); showTooltip = false"
    {{ $attributes->merge(['class' => 'flex items-center gap-4 group p-2 rounded-2xl transition-all duration-300 hover:bg-slate-50 dark:hover:bg-gray-800/40 relative']) }}
>
    {{-- Contenedor del Icono --}}
    <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl 
                bg-slate-100 dark:bg-gray-900 
                text-slate-400 dark:text-gray-500 
                group-hover:scale-110 group-hover:bg-orvian-orange/10 group-hover:text-orvian-orange 
                transition-all duration-300 shadow-sm dark:shadow-none">
        <x-dynamic-component :component="$icon" class="w-5 h-5" />
    </div>

    {{-- Contenido Textual --}}
    <div class="flex flex-col min-w-0">
        <span class="text-[10px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 transition-colors group-hover:text-slate-500 dark:group-hover:text-gray-400">
            {{ $label }}
        </span>
        
        <span class="text-sm md:text-base font-semibold {{ $hasValue ? 'text-orvian-navy dark:text-white' : 'text-slate-300 dark:text-gray-600 italic' }} truncate transition-colors">
            {{ $displayValue }}
        </span>
    </div>

    {{-- Tooltip Adaptativo (Solo se activa si hay valor largo) --}}
    @if($hasValue)
        <template x-if="showTooltip">
            <div 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="absolute z-50 bottom-full mb-2 left-1/2 -translate-x-1/2 px-3 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs rounded-lg shadow-xl whitespace-normal min-w-[150px] max-w-[250px] text-center border border-slate-700 dark:border-slate-200 pointer-events-none"
            >
                {{ $value }}
                {{-- Triángulo del Tooltip --}}
                <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-slate-900 dark:border-t-white"></div>
            </div>
        </template>
    @endif
</div>