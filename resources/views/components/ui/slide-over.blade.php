@props([
    'name', 
    'title' => null, 
    'maxWidth' => 'md'
])

@php
$maxWidthClass = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
][$maxWidth] ?? 'max-w-md';
@endphp

<div
    x-data="{ show: false }"
    x-show="show"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:keydown.escape.window="show = false"
    style="display: none;"
    class="fixed inset-0 z-50 overflow-hidden"
    aria-labelledby="slide-over-title" 
    role="dialog" 
    aria-modal="true"
>
    <div class="absolute inset-0 overflow-hidden">
        {{-- Overlay --}}
        <div 
            x-show="show"
            x-transition:enter="ease-in-out duration-500"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in-out duration-500"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="show = false"
            class="absolute inset-0 bg-dark-card/50 backdrop-blur-sm transition-opacity" 
        ></div>

        {{-- Panel --}}
        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div 
                x-show="show"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="pointer-events-auto w-screen {{ $maxWidthClass }}"
            >
                <div class="flex h-full flex-col bg-white dark:bg-dark-card shadow-2xl border-l border-slate-200 dark:border-dark-border">
                    {{-- Header --}}
                    <div class="px-6 py-6 border-b border-slate-100 dark:border-dark-border">
                        <div class="flex items-start justify-between">
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">
                                {{ $title }}
                            </h2>
                            <div class="ml-3 flex h-7 items-center">
                                <button 
                                    type="button" 
                                    @click="show = false"
                                    class="relative rounded-md text-slate-400 hover:text-orvian-orange transition-colors focus:outline-none"
                                >
                                    <span class="sr-only">Cerrar panel</span>
                                    <x-heroicon-o-x-mark class="h-6 w-6" />
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Contenido con Custom Scroll --}}
                    <div class="relative flex-1 px-6 py-4 overflow-y-auto custom-scroll">
                        {{ $slot }}
                    </div>

                    {{-- Footer (Opcional) --}}
                    @if(isset($footer))
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-dark-border bg-slate-50/50 dark:bg-dark-card/50">
                            {{ $footer }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>