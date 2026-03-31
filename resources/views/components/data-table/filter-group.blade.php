@props([
    'title', 
    'collapsed' => false,
    'activeCount' => 0
])

<div 
    x-data="{ expanded: {{ $collapsed ? 'false' : 'true' }} }" 
    @class([
        'border-b border-slate-100 rounded-xl dark:border-dark-border last:border-0 -mx-1 transition-all duration-300',
        'bg-slate-50/50 dark:bg-white/[0.02]' => $activeCount > 0
    ])
>
    {{-- Header del Grupo --}}
    <button 
        type="button" 
        @click="expanded = !expanded"
        class="w-full flex  items-center justify-between py-3 px-2 text-left group 
               hover:bg-slate-100/50 dark:hover:bg-white/5 transition-all duration-200"
    >
        <div class="flex items-center gap-2.5">
            {{-- Indicador de actividad --}}
            <div @class([
                'flex items-center justify-center w-5 h-5 rounded-lg border transition-all duration-300',
                'bg-orvian-orange/10 border-orvian-orange/20 text-orvian-orange' => $activeCount > 0,
                'bg-slate-100 dark:bg-dark-border border-transparent text-slate-400' => $activeCount === 0,
            ])>
                @if($activeCount > 0)
                    <span class="text-[10px] font-black">{{ $activeCount }}</span>
                @else
                    <x-heroicon-s-tag class="w-3 h-3" />
                @endif
            </div>

            <span @class([
                'text-[11px] font-bold uppercase tracking-wider transition-colors duration-200',
                'text-slate-700 dark:text-slate-200' => $activeCount > 0,
                'text-slate-500 dark:text-slate-400' => $activeCount === 0,
            ])>
                {{ $title }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            <x-heroicon-s-chevron-down 
                class="w-4 h-4 text-slate-400 transition-transform duration-500 cubic-bezier(0.4, 0, 0.2, 1)"
                ::class="expanded ? 'rotate-180 text-orvian-orange' : ''" 
            />
        </div>
    </button>

    {{-- Contenido con Animación x-collapse --}}
    <div 
        x-show="expanded" 
        x-collapse.duration.500ms
        x-cloak
    >
        <div class="space-y-4 pt-1 pb-5 px-3">
            {{ $slot }}
        </div>
    </div>
</div>