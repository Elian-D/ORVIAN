<div class="p-6 space-y-6" x-data="{ selectedShift: null }">
    
    {{-- Header con navegación de fecha --}}
    <x-ui.page-header>
        <x-slot:title>
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-gradient-to-br from-orvian-orange/10 to-orvian-blue/10 
                            border border-orvian-orange/20 dark:border-white/10">
                    <x-heroicon-s-calendar class="w-6 h-6 text-orvian-orange" />
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-800 dark:text-white leading-none">
                        Hub de Asistencia
                    </h1>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                        Gestión por excepción y auditoría
                    </p>
                </div>
            </div>
        </x-slot:title>

        <x-slot:actions>
            <x-ui.button 
                variant="secondary" 
                type="ghost" 
                size="sm" 
                iconLeft="heroicon-o-clock"
                wire:click="goToToday">
                Hoy
            </x-ui.button>

            <x-ui.button 
                href="{{ route('app.attendance.session') }}" 
                variant="primary" 
                size="sm" 
                iconLeft="heroicon-s-plus">
                Abrir Sesión
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- ══════════════════════════════════════════
            Columna Izquierda: Calendario
        ══════════════════════════════════════════ --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-white/10 
                        shadow-sm p-5 backdrop-blur-sm">
                
                {{-- Header del calendario --}}
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                        {{ $currentMonth }}
                    </h3>
                    <div class="flex gap-1">
                        <button 
                            wire:click="previousMonth"
                            class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 
                                   text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all">
                            <x-heroicon-s-chevron-left class="w-4 h-4" />
                        </button>
                        <button 
                            wire:click="nextMonth"
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
                        @foreach(['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $day)
                            <div class="text-center text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase">
                                {{ $day }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Días del mes --}}
                    <div class="grid grid-cols-7 gap-1">
                        @foreach($this->calendarDays as $day)
                            <button 
                                wire:click="selectDate('{{ $day['date']->toDateString() }}')"
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
                                @if($day['has_sessions'])
                                    <span @class([
                                        'absolute bottom-1 w-1 h-1 rounded-full',
                                        'bg-emerald-500' => $day['status'] === 'success',
                                        'bg-amber-500' => $day['status'] === 'warning',
                                        'bg-red-500' => $day['status'] === 'error',
                                        'opacity-0' => $day['is_selected'], // Ocultar cuando está seleccionado
                                    ])></span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Leyenda --}}
                <div class="mt-5 flex pt-5 border-t border-slate-200 dark:border-white/5 space-y-2">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">Sesiones cerradas</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">Sesiones abiertas</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span class="text-slate-600 dark:text-slate-400">Alta ausencia (\>20%)</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════
            Columna Derecha: Selector de Tandas y Detalle
        ══════════════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-6">
            
            @if($this->sessionsOfDay->isNotEmpty())
                
                {{-- 1. GRID INTELIGENTE DE TANDAS (SELECTOR) --}}
                @php
                    // Dependiendo de la cantidad de tandas, ajustamos las columnas
                    $gridCols = $this->sessionsOfDay->count() === 1 ? 'grid-cols-1 md:grid-cols-2' : 
                               ($this->sessionsOfDay->count() === 2 ? 'grid-cols-2' : 'grid-cols-2 md:grid-cols-3');
                @endphp

                <div class="grid {{ $gridCols }} gap-4">
                    @foreach($this->sessionsOfDay as $session)
                        <button 
                            wire:click="selectSession({{ $session->id }})"
                            @class([
                                'relative flex items-center justify-between p-4 rounded-2xl border text-left transition-all duration-200 overflow-hidden',
                                // Estado Seleccionado
                                'bg-white dark:bg-dark-card border-orvian-orange ring-1 ring-orvian-orange shadow-md' => $this->selectedSessionId === $session->id,
                                // Estado Inactivo
                                'bg-white/50 dark:bg-white/[0.02] border-slate-200 dark:border-white/10 hover:border-slate-300 dark:hover:border-white/20 hover:bg-slate-50 dark:hover:bg-white/[0.04]' => $this->selectedSessionId !== $session->id,
                            ])
                        >
                            {{-- Indicador lateral si está seleccionado --}}
                            @if($this->selectedSessionId === $session->id)
                                <div class="absolute left-0 top-0 w-1 h-full bg-orvian-orange"></div>
                            @endif

                            <div class="flex items-center gap-3">
                                <div @class([
                                    'p-2 rounded-lg flex-shrink-0',
                                    'bg-orvian-orange/10 text-orvian-orange' => $this->selectedSessionId === $session->id,
                                    'bg-slate-100 dark:bg-white/5 text-slate-500' => $this->selectedSessionId !== $session->id,
                                ])>
                                    <x-heroicon-s-clock class="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 @class([
                                        'font-bold leading-tight',
                                        'text-slate-900 dark:text-white' => $this->selectedSessionId === $session->id,
                                        'text-slate-600 dark:text-slate-300' => $this->selectedSessionId !== $session->id,
                                    ])>
                                        {{ $session->shift->type }}
                                    </h4>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <div @class([
                                            'w-1.5 h-1.5 rounded-full',
                                            'bg-state-warning' => is_null($session->closed_at),
                                            'bg-state-success' => !is_null($session->closed_at),
                                        ])></div>
                                        <span class="text-[10px] uppercase font-bold text-slate-400">
                                            {{ is_null($session->closed_at) ? 'Abierta' : 'Cerrada' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- 2. DETALLE DE LA SESIÓN SELECCIONADA --}}
                @if($this->selectedSessionDetail)
                    @php $session = $this->selectedSessionDetail; @endphp
                    
                    <div class="bg-white/50 dark:bg-dark-card backdrop-blur-sm 
                                rounded-2xl border border-slate-200 dark:border-white/10 
                                shadow-sm p-6 space-y-6 animate-fade-in-up">
                        
                        {{-- Header Unificado --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <x-ui.avatar :user="$session->openedBy" size="md" class="ring-2 ring-white dark:ring-dark-bg" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-base font-black text-slate-800 dark:text-white">
                                            Tanda {{ $session->shift->type }}
                                        </h4>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <x-heroicon-s-user class="w-3.5 h-3.5" />
                                            {{ $session->openedBy->name }}
                                        </span>
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <x-heroicon-s-clock class="w-3.5 h-3.5" />
                                            {{ $session->opened_at->format('h:i A') }}
                                        </span>
                                        <span class="text-xs font-bold text-orvian-orange flex items-center gap-1">
                                            <x-heroicon-s-users class="w-3.5 h-3.5" />
                                            {{ $session->total_expected }} <span class="font-normal opacity-70">estudiantes</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center">
                                <x-ui.button 
                                    variant="{{ is_null($session->closed_at) ? 'primary' : 'secondary' }}"
                                    type="{{ is_null($session->closed_at) ? 'solid' : 'outline' }}" 
                                    size="sm" 
                                    iconRight="heroicon-s-chevron-right"
                                    {{-- Cambio dinámico de la ruta --}}
                                    href="{{ is_null($session->closed_at) 
                                        ? route('app.attendance.session') 
                                        : route('app.attendance.audit', ['sessionId' => $session->id]) 
                                    }}"
                                    class="rounded-xl shadow-sm"
                                >
                                    {{ is_null($session->closed_at) ? 'Gestionar' : 'Auditar' }}
                                </x-ui.button>
                            </div>
                        </div>

                        {{-- Barra de progreso segmentada --}}
                        <div class="mb-5">
                            <div class="flex items-end justify-between mb-3">
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                                    Distribución de Asistencia
                                </span>
                                <div class="flex items-center gap-3 text-[10px] font-bold uppercase tracking-wide">
                                    <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        P ({{ $session->total_expected > 0 ? round(($session->total_present / $session->total_expected) * 100) : 0 }}%)
                                    </div>
                                    <div class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        T ({{ $session->total_expected > 0 ? round(($session->total_late / $session->total_expected) * 100) : 0 }}%)
                                    </div>
                                    <div class="flex items-center gap-1.5 text-blue-600 dark:text-blue-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        E ({{ $session->total_expected > 0 ? round(($session->total_excused / $session->total_expected) * 100) : 0 }}%)
                                    </div>
                                    <div class="flex items-center gap-1.5 text-red-600 dark:text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        A ({{ $session->total_expected > 0 ? round(($session->total_absent / $session->total_expected) * 100) : 0 }}%)
                                    </div>
                                </div>
                            </div>

                            <div class="h-2.5 bg-slate-100 dark:bg-white/5 rounded-full overflow-hidden flex shadow-inner">
                                @php
                                    $total = $session->total_expected > 0 ? $session->total_expected : 1;
                                    $presentP = ($session->total_present / $total) * 100;
                                    $lateP = ($session->total_late / $total) * 100;
                                    $excusedP = ($session->total_excused / $total) * 100;
                                    $absentP = ($session->total_absent / $total) * 100;
                                @endphp

                                <div class="bg-emerald-500 transition-all duration-700 ease-out" style="width: {{ $presentP }}%" title="Presentes"></div>
                                <div class="bg-amber-500 transition-all duration-700 ease-out" style="width: {{ $lateP }}%" title="Tardes"></div>
                                <div class="bg-blue-500 transition-all duration-700 ease-out" style="width: {{ $excusedP }}%" title="Excusas"></div>
                                <div class="bg-red-500 transition-all duration-700 ease-out" style="width: {{ $absentP }}%" title="Ausentes"></div>
                            </div>
                        </div>

                        {{-- Métricas Finas --}}
                        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
                            @php
                                $metrics = [
                                    ['label' => 'Presentes', 'value' => $session->total_present, 'color' => 'bg-emerald-500', 'text' => 'text-emerald-600 dark:text-emerald-400', 'border' => 'hover:border-emerald-500/30'],
                                    ['label' => 'Tardanzas', 'value' => $session->total_late, 'color' => 'bg-amber-500', 'text' => 'text-amber-600 dark:text-amber-400', 'border' => 'hover:border-amber-500/30'],
                                    ['label' => 'Ausentes', 'value' => $session->total_absent, 'color' => 'bg-red-500', 'text' => 'text-red-600 dark:text-red-400', 'border' => 'hover:border-red-500/30'],
                                    ['label' => 'Excusados', 'value' => $session->total_excused, 'color' => 'bg-blue-500', 'text' => 'text-blue-600 dark:text-blue-400', 'border' => 'hover:border-blue-500/30'],
                                ];
                            @endphp

                            @foreach($metrics as $metric)
                                <div class="relative group bg-white/50 dark:bg-white/[0.01] border border-slate-200/60 dark:border-white/5 rounded-xl p-3.5 transition-all duration-300 {{ $metric['border'] }}">
                                    <div class="absolute top-3.5 left-0 w-[2px] h-7 {{ $metric['color'] }} rounded-r-full opacity-70 group-hover:opacity-100 transition-opacity"></div>
                                    <div class="pl-2">
                                        <span class="block text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.15em] mb-0.5">
                                            {{ $metric['label'] }}
                                        </span>
                                        <div class="flex items-baseline gap-1.5">
                                            <span class="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight">
                                                {{ $metric['value'] }}
                                            </span>
                                            <span class="text-[10px] font-bold {{ $metric['text'] }} opacity-80">
                                                {{ $session->total_expected > 0 ? round(($metric['value'] / $session->total_expected) * 100) : 0 }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
            @else
                {{-- Estado vacío --}}
                <div class="flex flex-col items-center justify-center py-16 px-6 
                            bg-slate-50 dark:bg-white/5 rounded-2xl border-2 border-dashed 
                            border-slate-200 dark:border-white/10">
                    <div class="p-4 rounded-2xl bg-slate-100 dark:bg-white/5 mb-4">
                        <x-heroicon-o-calendar-days class="w-12 h-12 text-slate-300 dark:text-slate-600" />
                    </div>
                    <h3 class="text-base font-bold text-slate-700 dark:text-slate-300 mb-1">
                        No hay sesiones registradas
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 text-center max-w-md">
                        No se abrió asistencia para el día {{ Carbon\Carbon::parse($date)->isoFormat('D [de] MMMM') }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>