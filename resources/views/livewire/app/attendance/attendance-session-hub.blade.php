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
                        Control Diario de Asistencia - {{ Carbon\Carbon::parse($date)->isoFormat('D [de] MMMM') }}
                    </h1>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                        Gestión por excepción y auditoría
                    </p>
                </div>
            </div>
        </x-slot:title>

        <x-slot:actions>
            <x-ui.button
                variant="info"
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


    {{-- ══════════════════════════════════════════
        Selector de Fecha (popover con el calendario)
    ══════════════════════════════════════════ --}}
    <div class="relative inline-block" x-data="{ open: false }">
        <button
            type="button"
            @click="open = !open"
            class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-white/10
                   bg-white dark:bg-dark-card shadow-sm text-sm font-semibold text-slate-700 dark:text-slate-200
                   hover:border-slate-300 dark:hover:border-white/20 transition-all">
            <x-heroicon-s-calendar class="w-4 h-4 text-orvian-orange" />
            <span>{{ $selectedDateLabel }}</span>
            <x-heroicon-s-chevron-down class="w-3.5 h-3.5 text-slate-400 transition-transform" ::class="open && 'rotate-180'" />
        </button>

        <div
            x-show="open"
            @click.away="open = false"
            @calendar-date-selected.window="open = false"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
            x-cloak
            class="absolute left-0 top-full mt-2 w-[320px] z-40 rounded-2xl shadow-xl p-5
                   bg-white dark:bg-dark-card border border-slate-200 dark:border-white/10">

            <x-ui.calendar :days="$this->calendarDays" :month="$currentMonth" />

            {{-- Leyenda --}}
            <div class="mt-5 flex flex-col gap-2 pt-4 border-t border-slate-200 dark:border-white/5">
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
                    <span class="text-slate-600 dark:text-slate-400">Alta ausencia (&gt;20%)</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
        Selector de Tandas y Detalle
    ══════════════════════════════════════════ --}}
    <div class="space-y-6">
        @if($this->sessionsOfDay->isNotEmpty())

            {{-- 1. SELECTOR DE TANDAS — solo tiene sentido elegir cuando hay más
                 de una. Con una sola tanda, mostrar un selector sería ofrecer una
                 decisión que no existe y dejar un hueco de grid a medio llenar;
                 esa misma información (tanda + estado) ya vive en el header del
                 detalle de abajo, así que no se pierde nada al omitirlo. --}}
            @if($this->sessionsOfDay->count() > 1)
                @php
                    $sessionsCount = $this->sessionsOfDay->count();
                    $gridCols = match(true) {
                        $sessionsCount === 2 => 'grid-cols-2',
                        $sessionsCount === 3 => 'grid-cols-2 md:grid-cols-3',
                        default              => 'grid-cols-2 md:grid-cols-4',
                    };
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
            @endif

                {{-- 2. DETALLE DE LA SESIÓN SELECCIONADA --}}
                @if($this->selectedSessionDetail)
                    @php $session = $this->selectedSessionDetail; @endphp

                    <div class="bg-white/50 dark:bg-dark-card backdrop-blur-sm
                                rounded-2xl border border-slate-200 dark:border-white/10
                                shadow-sm p-6 space-y-6 animate-fade-in-up">

                        {{-- Header — la acción (Gestionar/Auditar) es la protagonista:
                             botón sólido, tamaño md y ancho completo en mobile. El
                             resto (quién abrió, hora, cupo) queda deliberadamente
                             secundario en tamaño y peso tipográfico. --}}
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-5">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="relative flex-shrink-0">
                                    <x-ui.avatar :user="$session->openedBy" size="md" class="ring-2 ring-white dark:ring-dark-bg" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-base font-black text-slate-800 dark:text-white">
                                            Tanda {{ $session->shift->type }}
                                        </h4>
                                        <span @class([
                                            'inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide',
                                            'bg-state-warning/10 text-state-warning' => is_null($session->closed_at),
                                            'bg-state-success/10 text-state-success' => !is_null($session->closed_at),
                                        ])>
                                            {{ is_null($session->closed_at) ? 'Abierta' : 'Cerrada' }}
                                        </span>
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
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <x-heroicon-s-users class="w-3.5 h-3.5" />
                                            {{ $session->total_expected }} <span class="opacity-70">estudiantes</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <x-ui.button
                                variant="{{ is_null($session->closed_at) ? 'primary' : 'secondary' }}"
                                type="solid"
                                size="md"
                                iconRight="heroicon-s-chevron-right"
                                {{-- Cambio dinámico de la ruta --}}
                                href="{{ is_null($session->closed_at)
                                    ? route('app.attendance.session')
                                    : route('app.attendance.audit', ['sessionId' => $session->id])
                                }}"
                                class="w-full sm:w-auto rounded-xl shadow-md flex-shrink-0"
                            >
                                {{ is_null($session->closed_at) ? 'Gestionar Sesión' : 'Auditar Sesión' }}
                            </x-ui.button>
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