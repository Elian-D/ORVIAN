<div class="w-full max-w-7xl mx-auto p-4 md:p-8 space-y-6" wire:poll.60s="refresh">

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <x-ui.page-header
        title="Dashboard de Asistencia"
        description="Vista operativa dual: Plantel y Aula en tiempo real.">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">

                {{-- Filtro: Fecha — dropdown calendario --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button"
                        class="flex items-center gap-2 h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-700 dark:text-slate-200 hover:border-slate-300 dark:hover:border-white/20 transition-colors font-medium">
                        <x-heroicon-s-calendar class="w-4 h-4 text-slate-400 flex-shrink-0" />
                        <span>{{ \Carbon\Carbon::parse($selectedDate)->isoFormat('D MMM, YYYY') }}</span>
                        <x-heroicon-s-chevron-down 
                            class="w-3 h-3 text-slate-400 transition-transform duration-200" 
                            x-bind:class="open ? 'rotate-180' : ''" 
                        />
                    </button>

                    <div x-show="open"
                         @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                         class="absolute top-full left-0 mt-2 z-50 w-72 bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-dark-border shadow-xl p-4"
                         style="display: none;">

                        {{-- Navegación de mes --}}
                        <div class="flex items-center justify-between mb-4">
                            <button wire:click="previousCalendarMonth" type="button"
                                class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                <x-heroicon-s-chevron-left class="w-4 h-4" />
                            </button>
                            <span class="text-sm font-bold text-slate-800 dark:text-white capitalize">
                                {{ $calendarLabel }}
                            </span>
                            <button wire:click="nextCalendarMonth" type="button"
                                class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                <x-heroicon-s-chevron-right class="w-4 h-4" />
                            </button>
                        </div>

                        {{-- Cabecera días de semana --}}
                        <div class="grid grid-cols-7 gap-1 mb-1">
                            @foreach(['L','M','X','J','V','S','D'] as $d)
                                <div class="text-center text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase">{{ $d }}</div>
                            @endforeach
                        </div>

                        {{-- Grid de días --}}
                        <div class="grid grid-cols-7 gap-1">
                            @foreach($calendarDays as $day)
                                <button
                                    wire:click="selectDate('{{ $day['date'] }}')"
                                    @click="open = false"
                                    type="button"
                                    @class([
                                        'relative aspect-square flex flex-col items-center justify-center rounded-lg text-xs font-bold transition-all',
                                        'opacity-25 pointer-events-none'                                                              => !$day['is_current_month'],
                                        'bg-orvian-orange text-white shadow-md'                                                       => $day['is_selected'],
                                        'ring-2 ring-orvian-orange ring-offset-1 dark:ring-offset-dark-card text-orvian-orange'       => $day['is_today'] && !$day['is_selected'],
                                        'hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300'               => !$day['is_selected'] && $day['is_current_month'],
                                    ])
                                >
                                    {{ $day['day'] }}
                                    @if($day['has_sessions'])
                                        <span @class([
                                            'absolute bottom-0.5 w-1 h-1 rounded-full',
                                            'bg-emerald-500' => $day['status'] === 'success',
                                            'bg-amber-500'   => $day['status'] === 'warning',
                                            'bg-red-500'     => $day['status'] === 'error',
                                            'opacity-0'      => $day['is_selected'],
                                        ])></span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        {{-- Leyenda --}}
                        <div class="mt-3 pt-3 border-t border-slate-100 dark:border-white/5 flex flex-wrap gap-x-3 gap-y-1.5">
                            <div class="flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                Sesiones cerradas
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                                Sesión activa
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                Alta ausencia (&gt;20%)
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filtro: Tanda --}}
                <select wire:model.live="selectedShift"
                    class="h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                    <option value="">Todas las tandas</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->type }}</option>
                    @endforeach
                </select>

                {{-- Filtro: Sección --}}
                <select wire:model.live="selectedSection"
                    class="h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/40">
                    <option value="">Todas las secciones</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->full_label }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- ── Sección 1: Banners de Estado ───────────────────────────────────── --}}

    {{-- Banner: Sin sesión abierta --}}
    @if(!$activeSession)
        <div class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/40">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 p-2 rounded-xl bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-sm font-bold text-amber-800 dark:text-amber-300">No se ha abierto la asistencia de hoy</p>
                    <p class="text-xs text-amber-600 dark:text-amber-500">Los registros de plantel no están disponibles hasta que se abra una sesión.</p>
                </div>
            </div>
            <a href="{{ route('app.attendance.session') }}"
                class="flex-shrink-0 flex items-center gap-1.5 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                <x-heroicon-s-play class="w-3.5 h-3.5" />
                Abrir Sesión
            </a>
        </div>
    @else
        {{-- Banner: Sesión activa --}}
        <div class="flex items-center justify-between gap-4 p-4 rounded-2xl bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800/40">
            <div class="flex items-center gap-3">
                <div class="relative flex-shrink-0">
                    <div class="w-2.5 h-2.5 rounded-full {{ $activeSession['is_open'] ? 'bg-green-500' : 'bg-slate-400' }}"></div>
                    @if($activeSession['is_open'])
                        <div class="absolute inset-0 rounded-full bg-green-500 animate-ping opacity-60"></div>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-bold text-green-800 dark:text-green-300">
                        Sesión {{ $activeSession['is_open'] ? 'activa' : 'cerrada' }} — {{ $activeSession['shift'] }}
                    </p>
                    <p class="text-xs text-green-600 dark:text-green-500">
                        Abierta a las {{ $activeSession['opened_at'] }} · {{ $activeSession['total_expected'] }} estudiantes esperados
                    </p>
                </div>
            </div>
            <a href="{{ route('app.attendance.hub') }}"
                class="flex-shrink-0 flex items-center gap-1.5 px-4 py-2 bg-white dark:bg-dark-card hover:bg-green-50 dark:hover:bg-green-900/20 text-green-700 dark:text-green-400 text-xs font-bold rounded-xl border border-green-200 dark:border-green-800/40 transition-colors">
                <x-heroicon-s-cog-6-tooth class="w-3.5 h-3.5" />
                Gestionar
            </a>
        </div>
    @endif

    {{-- Banner: Modo local --}}
    @if(config('app.mode') === 'local')
        <div class="flex items-center gap-3 p-3 rounded-2xl bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/40">
            <div class="flex-shrink-0 p-1.5 rounded-lg bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">
                <x-heroicon-s-signal class="w-4 h-4" />
            </div>
            <p class="text-xs font-medium text-blue-700 dark:text-blue-400">
                Modo local activo — los datos se sincronizan con el servidor cloud cada 5 minutos.
            </p>
        </div>
    @endif

    {{-- ── Sección 2: Métricas Duales ──────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Panel Plantel --}}
        <div class="bg-white dark:bg-dark-card rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-dark-border">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">
                        <x-heroicon-s-building-office-2 class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm">Asistencia de Plantel</h3>
                        <p class="text-xs text-slate-400">Registro de entrada institucional</p>
                    </div>
                </div>
                <span class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ $plantelStats['rate'] }}%</span>
            </div>

            {{-- Barra de progreso segmentada --}}
            @php
                $pt = $plantelStats['total'];
                $pPresent = $pt > 0 ? round(($plantelStats['present'] / $pt) * 100, 1) : 0;
                $pLate    = $pt > 0 ? round(($plantelStats['late']    / $pt) * 100, 1) : 0;
                $pAbsent  = $pt > 0 ? round(($plantelStats['absent']  / $pt) * 100, 1) : 0;
                $pExcused = $pt > 0 ? round(($plantelStats['excused'] / $pt) * 100, 1) : 0;
            @endphp
            <div class="flex h-2.5 rounded-full overflow-hidden gap-px mb-5 bg-slate-100 dark:bg-white/10">
                @if($pt > 0)
                    @if($pPresent > 0)<div class="bg-emerald-500 transition-all" style="width: {{ $pPresent }}%"></div>@endif
                    @if($pLate > 0)<div class="bg-amber-400 transition-all"  style="width: {{ $pLate }}%"></div>@endif
                    @if($pAbsent > 0)<div class="bg-red-500 transition-all"   style="width: {{ $pAbsent }}%"></div>@endif
                    @if($pExcused > 0)<div class="bg-blue-400 transition-all"  style="width: {{ $pExcused }}%"></div>@endif
                @endif
            </div>

            {{-- Métricas --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="text-center p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10">
                    <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ $plantelStats['present'] }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-500/80 mt-0.5">Presentes</p>
                </div>
                <div class="text-center p-3 rounded-2xl bg-amber-50 dark:bg-amber-500/10">
                    <p class="text-xl font-black text-amber-500 dark:text-amber-400">{{ $plantelStats['late'] }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-400/80 mt-0.5">Tardanzas</p>
                </div>
                <div class="text-center p-3 rounded-2xl bg-red-50 dark:bg-red-500/10">
                    <p class="text-xl font-black text-red-500 dark:text-red-400">{{ $plantelStats['absent'] }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-red-400/80 mt-0.5">Ausentes</p>
                </div>
                <div class="text-center p-3 rounded-2xl bg-blue-50 dark:bg-blue-500/10">
                    <p class="text-xl font-black text-blue-500 dark:text-blue-400">{{ $plantelStats['excused'] }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-blue-400/80 mt-0.5">Excusados</p>
                </div>
            </div>

            <p class="mt-4 text-xs text-slate-400 text-right">Total esperado: <span class="font-bold text-slate-600 dark:text-slate-300">{{ $activeSession['total_expected'] ?? $plantelStats['total'] }}</span></p>
        </div>

        {{-- Panel Aula --}}
        <div class="bg-white dark:bg-dark-card rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-dark-border">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400">
                        <x-heroicon-s-academic-cap class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm">Asistencia de Aula</h3>
                        <p class="text-xs text-slate-400">Pase de lista por clase</p>
                    </div>
                </div>
                <span class="text-2xl font-black text-purple-600 dark:text-purple-400">{{ $classroomStats['total'] }}</span>
            </div>

            {{-- Barra de progreso segmentada --}}
            @php
                $ct = $classroomStats['total'];
                $cPresent = $ct > 0 ? round(($classroomStats['present'] / $ct) * 100, 1) : 0;
                $cLate    = $ct > 0 ? round(($classroomStats['late']    / $ct) * 100, 1) : 0;
                $cAbsent  = $ct > 0 ? round(($classroomStats['absent']  / $ct) * 100, 1) : 0;
                $cExcused = $ct > 0 ? round(($classroomStats['excused'] / $ct) * 100, 1) : 0;
            @endphp
            <div class="flex h-2.5 rounded-full overflow-hidden gap-px mb-5 bg-slate-100 dark:bg-white/10">
                @if($ct > 0)
                    @if($cPresent > 0)<div class="bg-emerald-500 transition-all" style="width: {{ $cPresent }}%"></div>@endif
                    @if($cLate > 0)<div class="bg-amber-400 transition-all"  style="width: {{ $cLate }}%"></div>@endif
                    @if($cAbsent > 0)<div class="bg-red-500 transition-all"   style="width: {{ $cAbsent }}%"></div>@endif
                    @if($cExcused > 0)<div class="bg-blue-400 transition-all"  style="width: {{ $cExcused }}%"></div>@endif
                @endif
            </div>

            {{-- Métricas --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="text-center p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10">
                    <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ $classroomStats['present'] }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-500/80 mt-0.5">Presentes</p>
                </div>
                <div class="text-center p-3 rounded-2xl bg-amber-50 dark:bg-amber-500/10">
                    <p class="text-xl font-black text-amber-500 dark:text-amber-400">{{ $classroomStats['late'] }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-400/80 mt-0.5">Tardanzas</p>
                </div>
                <div class="text-center p-3 rounded-2xl bg-red-50 dark:bg-red-500/10">
                    <p class="text-xl font-black text-red-500 dark:text-red-400">{{ $classroomStats['absent'] }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-red-400/80 mt-0.5">Ausentes</p>
                </div>
                <div class="text-center p-3 rounded-2xl bg-blue-50 dark:bg-blue-500/10">
                    <p class="text-xl font-black text-blue-500 dark:text-blue-400">{{ $classroomStats['excused'] }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-blue-400/80 mt-0.5">Excusados</p>
                </div>
            </div>

            <p class="mt-4 text-xs text-slate-400 text-right">Total registros de clase: <span class="font-bold text-slate-600 dark:text-slate-300">{{ $classroomStats['total'] }}</span></p>
        </div>
    </div>

    {{-- ── Sección 3: Panel de Discrepancias (Pasilleo) ────────────────────── --}}
    @if(count($discrepancies) > 0)
        <div class="bg-white dark:bg-dark-card rounded-3xl shadow-sm border border-red-100 dark:border-red-900/40 overflow-hidden">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-red-50 dark:border-red-900/30 bg-red-50/50 dark:bg-red-900/10">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400">
                        <x-heroicon-s-exclamation-circle class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-red-900 dark:text-red-300 text-sm">Alertas de Pasilleo</h3>
                        <p class="text-xs text-red-500 dark:text-red-400">Estudiantes presentes en plantel con ausencias en clase</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full bg-red-500 text-white text-xs font-black">
                    {{ count($discrepancies) }}
                </span>
            </div>

            {{-- Tabla --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-dark-border">
                            <th class="text-left px-6 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Estudiante</th>
                            <th class="text-center px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Estado Plantel</th>
                            <th class="text-center px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">Clases Ausente</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                        @foreach($discrepancies as $item)
                            <tr class="hover:bg-red-50/30 dark:hover:bg-red-900/5 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl overflow-hidden flex-shrink-0 bg-slate-100 dark:bg-white/10">
                                            @if($item['photo'])
                                                <img src="{{ Storage::url($item['photo']) }}" class="w-full h-full object-cover" alt="">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <x-heroicon-s-user class="w-4 h-4 text-slate-400" />
                                                </div>
                                            @endif
                                        </div>
                                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $item['student_name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $plantelVariant = match($item['plantel_status']) {
                                            'present' => 'success',
                                            'late'    => 'warning',
                                            'absent'  => 'error',
                                            'excused' => 'info',
                                            default   => 'slate',
                                        };
                                    @endphp
                                    <x-ui.badge :variant="$plantelVariant" size="sm" :dot="false">
                                        {{ ucfirst($item['plantel_status']) }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400 text-xs font-black">
                                        {{ $item['absent_classes'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('app.attendance.audit', ['sessionId' => $activeSession['id'] ?? 0]) }}"
                                        class="text-xs font-semibold text-red-500 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                                        Ver Detalle →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── Sección 4: Gráficos ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Donut: Distribución de estados --}}
        <div class="bg-white dark:bg-dark-card rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-dark-border">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-500">
                    <x-heroicon-s-chart-pie class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">Distribución de Plantel</h3>
                    <p class="text-xs text-slate-400">Estados del día actual</p>
                </div>
            </div>
            <div wire:ignore x-data="attendanceDonutChart(@js($plantelStats))"></div>
        </div>

        {{-- Línea: Tasa de asistencia 7 días --}}
        <div class="bg-white dark:bg-dark-card rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-dark-border">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-500">
                    <x-heroicon-s-chart-bar class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">Tendencia Semanal</h3>
                    <p class="text-xs text-slate-400">Tasa de asistencia últimos 7 días</p>
                </div>
            </div>
            <div wire:ignore x-data="attendanceLineChart(@js($weeklyStats))"></div>
        </div>
    </div>

    {{-- ── Sección 5: Timeline de Actividad Reciente ───────────────────────── --}}
    <div class="bg-white dark:bg-dark-card rounded-3xl shadow-sm border border-slate-100 dark:border-dark-border overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-dark-border">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-xl bg-slate-100 dark:bg-white/10 text-slate-500 dark:text-slate-300">
                    <x-heroicon-s-clock class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">Actividad Reciente</h3>
                    <p class="text-xs text-slate-400">Últimas 15 entradas al plantel</p>
                </div>
            </div>
            <a href="{{ route('app.attendance.scanner') }}"
                class="text-xs font-semibold text-blue-500 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                Ir al Escáner →
            </a>
        </div>

        {{-- Lista --}}
        @if(count($recentActivity) > 0)
            <ul class="divide-y divide-slate-50 dark:divide-white/5">
                @foreach($recentActivity as $entry)
                    <li class="flex items-center gap-4 px-6 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">

                        {{-- Avatar --}}
                        <div class="w-9 h-9 rounded-2xl overflow-hidden flex-shrink-0 bg-slate-100 dark:bg-white/10">
                            @if($entry['photo'])
                                <img src="{{ Storage::url($entry['photo']) }}" class="w-full h-full object-cover" alt="">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <x-heroicon-s-user class="w-5 h-5 text-slate-400" />
                                </div>
                            @endif
                        </div>

                        {{-- Nombre --}}
                        <p class="flex-1 font-semibold text-sm text-slate-800 dark:text-slate-200 truncate">
                            {{ $entry['student_name'] }}
                        </p>

                        {{-- Hora --}}
                        <span class="text-xs text-slate-400 font-mono flex-shrink-0">{{ $entry['time'] }}</span>

                        {{-- Método --}}
                        @php
                            $methodVariant = match($entry['method']) {
                                'qr'     => 'info',
                                'facial' => 'primary',
                                default  => 'slate',
                            };
                        @endphp
                        <x-ui.badge :variant="$methodVariant" size="sm" :dot="false">
                            {{ $entry['method_label'] }}
                        </x-ui.badge>

                        {{-- Estado --}}
                        @php
                            $statusVariant = match($entry['status']) {
                                'present' => 'success',
                                'late'    => 'warning',
                                'absent'  => 'error',
                                'excused' => 'info',
                                default   => 'slate',
                            };
                        @endphp
                        <x-ui.badge :variant="$statusVariant" size="sm" :dot="false">
                            {{ $entry['status_label'] }}
                        </x-ui.badge>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="flex flex-col items-center justify-center py-16 gap-3 text-slate-400">
                <x-heroicon-o-clock class="w-10 h-10 opacity-30" />
                <p class="text-sm">No hay registros de entrada para hoy.</p>
            </div>
        @endif
    </div>

</div>
