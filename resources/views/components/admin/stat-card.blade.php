@props([
    'title', 
    'value', 
    'icon', 
    'color' => 'text-orvian-blue',
    'limit' => null, // Opcional: Para activar modo "cuota"
    'used' => null   // Opcional: Valor numérico para la barra
])

@php
    // Lógica de alerta si hay límite
    $percentage = ($limit > 0 && $used !== null) ? ($used / $limit) * 100 : 0;
    
    // Cambiar color de la barra según el llenado
    $statusColor = match(true) {
        $percentage >= 90 => 'bg-red-500',
        $percentage >= 70 => 'bg-orvian-orange',
        default => 'bg-orvian-blue',
    };
@endphp

<div class="group relative bg-white dark:bg-dark-card p-6 rounded-3xl border border-gray-100 dark:border-dark-border shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col justify-between min-h-[160px]">
    
    {{-- Icono de Fondo (Decorativo) --}}
    <div class="absolute -right-4 -bottom-4 opacity-[0.03] dark:opacity-[0.04] text-gray-900 dark:text-white group-hover:scale-110 transition-transform duration-500">
        <x-dynamic-component :component="$icon" class="w-32 h-32" />
    </div>

    <div class="relative z-10">
        {{-- Fila Superior: Título e Icono Pequeño --}}
        <div class="flex justify-between items-start">
            <span class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">
                {{ $title }}
            </span>

            <div class="p-2.5 rounded-2xl bg-gray-50 dark:bg-white/5 {{ $color }} shadow-inner">
                <x-dynamic-component :component="$icon" class="w-5 h-5" />
            </div>
        </div>

        {{-- Valor Principal --}}
        <div class="mt-4">
            <h4 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter">
                {{ $value }}
            </h4>
        </div>
    </div>

    {{-- Feedback de Límite (Solo aparece si se pasa la prop 'limit') --}}
    @if($limit)
        <div class="relative z-10 mt-6 space-y-2">
            <div class="flex justify-between items-end text-[10px] font-bold uppercase tracking-tight">
                <span class="text-gray-400">Progreso de Cuota</span>
                <span class="{{ $percentage >= 90 ? 'text-red-500' : 'text-gray-500' }}">
                    {{ round($percentage) }}%
                </span>
            </div>
            {{-- Barra de Progreso --}}
            <div class="w-full h-1.5 bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                <div 
                    class="h-full {{ $statusColor }} transition-all duration-1000 ease-out shadow-[0_0_8px_rgba(0,0,0,0.1)]"
                    style="width: {{ $percentage }}%"
                ></div>
            </div>
            @if($percentage >= 90)
                <p class="text-[9px] font-black text-red-500 uppercase animate-pulse">
                    Límite casi alcanzado
                </p>
            @endif
        </div>
    @endif
</div>