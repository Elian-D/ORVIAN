@props(['plan', 'showActions' => true])

<div @class([
    'relative flex flex-col h-full rounded-[2rem] transition-all duration-500 border-2 group',
    'bg-white dark:bg-dark-card border-slate-100 dark:border-white/5 shadow-sm hover:shadow-xl' => !$plan->is_featured,
    'bg-white dark:bg-dark-card border-orvian-orange shadow-2xl scale-[1.02] z-10' => $plan->is_featured,
])>

    {{-- Badge de "Destacado" --}}
    @if($plan->is_featured)
        <div class="absolute whitespace-nowrap -top-4 left-1/2 -translate-x-1/2 bg-orvian-orange text-white text-[10px] font-black uppercase tracking-[0.2em] px-6 py-1.5 rounded-full shadow-lg shadow-orvian-orange/30 animate-bounce-subtle">
            Más Popular
        </div>
    @endif

    {{-- Header del Plan --}}
    <div class="p-8 pb-0">
        <div class="flex justify-between items-start mb-6">
            {{-- Icono representativo con el color del plan --}}
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110 duration-500" 
                 style="background-color: {{ $plan->bg_color }}15;">
                <x-heroicon-o-sparkles class="w-7 h-7" style="color: {{ $plan->bg_color }};" />
            </div>

            <x-ui.badge :hex="$plan->text_color" variant="slate" size="sm" class="font-black tracking-widest uppercase opacity-70">
                {{ $plan->const_name }}
            </x-ui.badge>
        </div>

        <h3 class="text-2xl font-black text-slate-800 dark:text-white leading-tight">
            {{ $plan->name }}
        </h3>

        <div class="mt-4 flex items-baseline gap-1">
            <span class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter">
                USD$ {{ number_format($plan->price, 0) }}
            </span>
            <span class="text-sm text-slate-400 font-bold uppercase tracking-widest">/ mes</span>
        </div>
    </div>

    {{-- Cuerpo: Límites y Features --}}
    <div class="p-8 flex-1">
        <div class="space-y-5">
            {{-- Límites Core --}}
            <div class="grid grid-cols-2 gap-3 mb-8">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-slate-100 dark:border-white/5">
                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-wider mb-1">Estudiantes</p>
                    <p class="text-lg font-black text-slate-700 dark:text-white">{{ number_format($plan->limit_students) }}</p>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-slate-100 dark:border-white/5">
                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-wider mb-1">Staff</p>
                    <p class="text-lg font-black text-slate-700 dark:text-white">{{ number_format($plan->limit_users) }}</p>
                </div>
            </div>

            {{-- Listado de Features con Iconos Dinámicos --}}
            <div class="space-y-4">
                @foreach($plan->features as $feature)
                    <div class="flex items-center gap-4 group/item">
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-slate-50 dark:bg-white/5 flex items-center justify-center transition-colors group-hover/item:bg-white group-hover/item:shadow-sm">
                            <x-dynamic-component :component="$feature->getIcon()" class="w-4 h-4 text-slate-400 dark:text-slate-500 group-hover/item:text-orvian-blue transition-colors" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ $feature->name }}</span>
                            <span class="text-[10px] text-slate-400 uppercase font-medium tracking-tight">{{ $feature->module }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Footer con Botón o Acción --}}
    @if($showActions)
        <div class="p-8 pt-0 mt-auto">
            {{ $slot ?? '' }}
            
            {{-- Si no hay slot, mostramos el botón por defecto de configuración --}}
            @if(!$slot->isNotEmpty())
                <x-ui.button 
                    variant="secondary" 
                    size="md" 
                    class="w-full rounded-2xl font-black py-4"
                    wire:click="edit({{ $plan->id }})"
                >
                    Editar Configuración
                </x-ui.button>
            @endif
        </div>
    @endif
</div>

<style>
    @keyframes bounce-subtle {
        0%, 100% { transform: translateY(0) translateX(-50%); }
        50% { transform: translateY(-5px) translateX(-50%); }
    }
    .animate-bounce-subtle {
        animation: bounce-subtle 3s infinite ease-in-out;
    }
</style>