<div>
    {{-- Header del calendario --}}
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">
            {{ $month }}
        </h3>
        <div class="flex gap-1">
            <button
                type="button"
                wire:click="{{ $previousMethod }}"
                class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5
                       text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all">
                <x-heroicon-s-chevron-left class="w-4 h-4" />
            </button>
            <button
                type="button"
                wire:click="{{ $nextMethod }}"
                class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5
                       text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all">
                <x-heroicon-s-chevron-right class="w-4 h-4" />
            </button>
        </div>
    </div>

    {{-- Grid del calendario --}}
    <div class="space-y-2">
        {{-- Días de la semana --}}
        <div class="grid grid-cols-7 gap-1 mb-2">
            @foreach(['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $dayLabel)
                <div class="text-center text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase">
                    {{ $dayLabel }}
                </div>
            @endforeach
        </div>

        {{-- Días del mes --}}
        <div class="grid grid-cols-7 gap-1">
            @foreach($days as $day)
                <button
                    type="button"
                    wire:click="{{ $selectMethod }}('{{ $day['date']->toDateString() }}')"
                    @class([
                        'relative aspect-square flex flex-col items-center justify-center rounded-lg transition-all',
                        'hover:bg-slate-50 dark:hover:bg-white/5',

                        // Día actual (borde naranja)
                        'ring-2 ring-orvian-orange ring-offset-2 dark:ring-offset-dark-card' => $day['is_today'],

                        // Día seleccionado (fondo naranja)
                        'bg-orvian-orange text-white shadow-lg shadow-orvian-orange/30' => $day['is_selected'],

                        // Día fuera del mes actual
                        'opacity-30' => !$day['is_current_month'],

                        // Día del mes actual (no seleccionado)
                        'text-slate-700 dark:text-slate-300' => $day['is_current_month'] && !$day['is_selected'],
                    ])
                >
                    <span class="text-xs font-bold">
                        {{ $day['date']->day }}
                    </span>

                    {{-- Indicador de estado (punto de color) --}}
                    @if($day['status'])
                        <span @class([
                            'absolute bottom-1 w-1 h-1 rounded-full',
                            $getStatusDotClasses($day['status']),
                            'opacity-0' => $day['is_selected'], // Ocultar cuando está seleccionado
                        ])></span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</div>
