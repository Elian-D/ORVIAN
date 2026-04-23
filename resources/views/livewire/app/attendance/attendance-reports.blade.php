<div>

    {{-- ── Alpine: Calendario de fecha ───────────────────────────────────── --}}
    @script
    <script>
        Alpine.data('calendarDatePicker', (initial, wireKey) => ({
            open: false,
            selectedDate: initial || null,
            viewYear: initial ? parseInt(initial.split('-')[0]) : new Date().getFullYear(),
            viewMonth: initial ? parseInt(initial.split('-')[1]) - 1 : new Date().getMonth(),
            get monthLabel() {
                return new Date(this.viewYear, this.viewMonth, 1).toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
            },
            get days() {
                const firstDay = new Date(this.viewYear, this.viewMonth, 1);
                const lastDay  = new Date(this.viewYear, this.viewMonth + 1, 0);
                let startDow   = firstDay.getDay() - 1;
                if (startDow < 0) startDow = 6;
                const days = [];
                const now  = new Date();
                const todayStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
                for (let i = startDow; i > 0; i--) { const d = new Date(this.viewYear, this.viewMonth, 1-i); days.push({date:this.fmt(d),day:d.getDate(),curr:false,today:false,sel:false}); }
                for (let d = 1; d <= lastDay.getDate(); d++) { const date=new Date(this.viewYear,this.viewMonth,d); const str=this.fmt(date); days.push({date:str,day:d,curr:true,today:str===todayStr,sel:str===this.selectedDate}); }
                let next=1; while(days.length<42){const d=new Date(this.viewYear,this.viewMonth+1,next++);days.push({date:this.fmt(d),day:d.getDate(),curr:false,today:false,sel:false});}
                return days;
            },
            prevMonth() { if(this.viewMonth===0){this.viewMonth=11;this.viewYear--;}else this.viewMonth--; },
            nextMonth() { if(this.viewMonth===11){this.viewMonth=0;this.viewYear++;}else this.viewMonth++; },
            fmt(d) { return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; },
            fmtDisplay(s) { if(!s)return''; const[y,m,d]=s.split('-'); return new Date(y,m-1,d).toLocaleDateString('es-ES',{day:'numeric',month:'short',year:'numeric'}); },
            selectDate(date) { this.selectedDate=date; this.open=false; $wire.set(wireKey, date); },
            clearDate()      { this.selectedDate=null; $wire.set(wireKey,''); },
        }));
    </script>
    @endscript

    {{-- ── Toolbar ─────────────────────────────────────────────────────────── --}}
    <x-app.module-toolbar>
        <x-slot:actions>
            @if($reportGenerated && !empty($reportData))
                <x-ui.button variant="secondary" type="ghost" size="sm" iconLeft="heroicon-s-table-cells"
                    wire:click="exportExcel" wire:loading.attr="disabled">
                    Excel
                </x-ui.button>
                <x-ui.button variant="secondary" type="ghost" size="sm" iconLeft="heroicon-s-document-arrow-down"
                    wire:click="exportPdf" wire:loading.attr="disabled">
                    PDF
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-app.module-toolbar>

    <div class="p-4 md:p-6 flex flex-col gap-6">

        {{-- ── Selector de tipo de reporte ────────────────────────────────── --}}
        <div class="bg-white dark:bg-dark-card rounded-3xl p-1.5 shadow-sm border border-slate-100 dark:border-dark-border">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-1">
                @php
                    $types = [
                        'summary'       => ['label' => 'Resumen General',    'icon' => 'heroicon-s-chart-bar'],
                        'student'       => ['label' => 'Por Estudiante',     'icon' => 'heroicon-s-user'],
                        'discrepancies' => ['label' => 'Discrepancias',      'icon' => 'heroicon-s-exclamation-circle'],
                        'teacher'       => ['label' => 'Por Maestro',        'icon' => 'heroicon-s-academic-cap'],
                    ];
                @endphp
                @foreach($types as $key => $info)
                    <button wire:click="$set('reportType', '{{ $key }}')" type="button"
                        @class([
                            'flex items-center justify-center gap-2 py-2.5 px-3 rounded-2xl text-sm font-semibold transition-all',
                            'bg-orvian-orange text-white shadow-sm' => $reportType === $key,
                            'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5' => $reportType !== $key,
                        ])>
                        <x-dynamic-component :component="$info['icon']" class="w-4 h-4 flex-shrink-0" />
                        <span class="hidden sm:inline">{{ $info['label'] }}</span>
                        <span class="sm:hidden">{{ explode(' ', $info['label'])[0] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ── Panel de filtros ────────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-dark-card rounded-3xl p-5 shadow-sm border border-slate-100 dark:border-dark-border">
            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-4">
                Configurar Reporte —
                <span class="text-orvian-orange">{{ $types[$reportType]['label'] }}</span>
            </h3>

            <div class="flex flex-wrap items-end gap-4">

                {{-- Período: Desde --}}
                <div>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Desde</p>
                    <div x-data="calendarDatePicker(@js($dateFrom ?: null), 'dateFrom')"
                         class="relative flex items-center gap-1">
                        <button @click="open = !open" type="button"
                            class="flex items-center gap-2 h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-700 dark:text-slate-200 hover:border-slate-300 transition-colors font-medium">
                            <x-heroicon-s-calendar class="w-4 h-4 text-slate-400 flex-shrink-0" />
                            <span x-text="selectedDate ? fmtDisplay(selectedDate) : 'Seleccionar'"></span>
                        </button>
                        <button x-show="selectedDate" @click="clearDate()" type="button"
                            class="flex items-center justify-center w-6 h-6 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                            style="display:none;">
                            <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
                        </button>
                        @include('livewire.app.attendance._calendar-dropdown')
                    </div>
                    @error('dateFrom') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <span class="text-slate-300 dark:text-slate-600 pb-1 select-none">→</span>

                {{-- Período: Hasta --}}
                <div>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Hasta</p>
                    <div x-data="calendarDatePicker(@js($dateTo ?: null), 'dateTo')"
                         class="relative flex items-center gap-1">
                        <button @click="open = !open" type="button"
                            class="flex items-center gap-2 h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-700 dark:text-slate-200 hover:border-slate-300 transition-colors font-medium">
                            <x-heroicon-s-calendar class="w-4 h-4 text-slate-400 flex-shrink-0" />
                            <span x-text="selectedDate ? fmtDisplay(selectedDate) : 'Seleccionar'"></span>
                        </button>
                        <button x-show="selectedDate" @click="clearDate()" type="button"
                            class="flex items-center justify-center w-6 h-6 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                            style="display:none;">
                            <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
                        </button>
                        @include('livewire.app.attendance._calendar-dropdown')
                    </div>
                    @error('dateTo') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Filtro: Sección (solo en Resumen) --}}
                @if($reportType === 'summary')
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Sección (opcional)</p>
                        <select wire:model="sectionId"
                            class="h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orvian-orange/40 transition-colors">
                            <option value="">Todas las secciones</option>
                            @foreach($sectionOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Filtro: Estudiante (solo en Por Estudiante) --}}
                @if($reportType === 'student')
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Estudiante</p>
                        <select wire:model="studentId"
                            class="h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orvian-orange/40 transition-colors min-w-[220px]">
                            <option value="">Seleccionar estudiante...</option>
                            @foreach($studentOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('studentId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Filtro: Maestro (solo en Por Maestro) --}}
                @if($reportType === 'teacher')
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Maestro (opcional)</p>
                        <select wire:model="teacherId"
                            class="h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orvian-orange/40 transition-colors min-w-[200px]">
                            <option value="">Todos los maestros</option>
                            @foreach($teacherOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Botón Generar --}}
                <div class="flex-shrink-0">
                    <p class="text-xs font-medium text-transparent mb-1.5">·</p>
                    <x-ui.button variant="primary" size="sm" iconLeft="heroicon-s-bolt"
                        wire:click="generate" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="generate">Generar Reporte</span>
                        <span wire:loading wire:target="generate">Generando...</span>
                    </x-ui.button>
                </div>
            </div>
        </div>

        {{-- ── Vista previa del reporte ─────────────────────────────────────── --}}
        @if($reportGenerated)

            {{-- ── Tarjetas de resumen (varía por tipo) ────────────────────── --}}
            @if($reportType === 'summary' && !empty($reportMeta))
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-emerald-50 dark:bg-emerald-500/10 rounded-2xl p-4 text-center">
                        <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $reportMeta['present'] }}</p>
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-500/80 mt-1">Presentes</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-500/10 rounded-2xl p-4 text-center">
                        <p class="text-2xl font-black text-red-500 dark:text-red-400">{{ $reportMeta['absent'] }}</p>
                        <p class="text-xs font-bold uppercase tracking-wider text-red-400/80 mt-1">Ausentes</p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-500/10 rounded-2xl p-4 text-center">
                        <p class="text-2xl font-black text-blue-500 dark:text-blue-400">{{ $reportMeta['excused'] }}</p>
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-400/80 mt-1">Justificados</p>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-500/10 rounded-2xl p-4 text-center">
                        <p class="text-2xl font-black text-orvian-orange">{{ $reportMeta['overall_rate'] }}%</p>
                        <p class="text-xs font-bold uppercase tracking-wider text-orange-400/80 mt-1">Tasa general</p>
                    </div>
                </div>
            @endif

            @if($reportType === 'student' && !empty($reportMeta))
                <div class="bg-white dark:bg-dark-card rounded-2xl p-5 border border-slate-100 dark:border-dark-border shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-white">{{ $reportMeta['student_name'] }}</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $reportMeta['section'] }}</p>
                        </div>
                        <div class="flex gap-4">
                            <div class="text-center">
                                <p class="text-xl font-black text-emerald-600">{{ $reportMeta['present'] }}</p>
                                <p class="text-[10px] uppercase tracking-wider text-slate-400">Presentes</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xl font-black text-red-500">{{ $reportMeta['absent'] }}</p>
                                <p class="text-[10px] uppercase tracking-wider text-slate-400">Ausentes</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xl font-black text-blue-500">{{ $reportMeta['excused'] }}</p>
                                <p class="text-[10px] uppercase tracking-wider text-slate-400">Justificados</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xl font-black text-orvian-orange">{{ $reportMeta['rate'] }}%</p>
                                <p class="text-[10px] uppercase tracking-wider text-slate-400">Asistencia</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($reportType === 'discrepancies' && !empty($reportMeta))
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/40">
                    <div class="flex-shrink-0 p-1.5 rounded-lg bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400">
                        <x-heroicon-s-exclamation-circle class="w-4 h-4" />
                    </div>
                    <p class="text-sm font-semibold text-red-700 dark:text-red-400">
                        {{ $reportMeta['total_events'] }} evento(s) de pasilleo detectados en el período.
                    </p>
                </div>
            @endif

            @if($reportType === 'teacher' && !empty($reportMeta))
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-dark-border">
                    <x-heroicon-s-academic-cap class="w-4 h-4 text-slate-400 flex-shrink-0" />
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $reportMeta['total_assignments'] }}</span>
                        asignaciones · <span class="font-bold text-slate-800 dark:text-slate-200">{{ $reportMeta['weekdays'] }}</span>
                        días hábiles en el período.
                    </p>
                </div>
            @endif

            {{-- ── Tabla de datos ──────────────────────────────────────────── --}}
            @if(!empty($reportData))
                <div class="bg-white dark:bg-dark-card rounded-3xl shadow-sm border border-slate-100 dark:border-dark-border overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-dark-border">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                            Vista previa
                            <span class="ml-2 text-xs font-normal text-slate-400">{{ count($reportData) }} filas</span>
                        </h3>
                        <div class="flex gap-2">
                            <x-ui.button variant="secondary" type="ghost" size="sm"
                                iconLeft="heroicon-s-table-cells"
                                wire:click="exportExcel" wire:loading.attr="disabled">
                                Excel
                            </x-ui.button>
                            <x-ui.button variant="secondary" type="ghost" size="sm"
                                iconLeft="heroicon-s-document-arrow-down"
                                wire:click="exportPdf" wire:loading.attr="disabled">
                                PDF
                            </x-ui.button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-dark-border">
                                    @foreach(array_keys($reportData[0]) as $heading)
                                        <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                @foreach($reportData as $row)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors">
                                        @foreach(array_values($row) as $i => $cell)
                                            @php
                                                $heading = array_keys($reportData[0])[$i] ?? '';
                                                $isRate  = str_contains(strtolower($heading), 'tasa') || str_contains(strtolower($heading), 'cobertura');
                                                $isStatus = $heading === 'Estado';
                                            @endphp
                                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">
                                                @if($isRate)
                                                    @php $rate = (float) str_replace('%', '', $cell); @endphp
                                                    <span @class([
                                                        'font-bold',
                                                        'text-emerald-600 dark:text-emerald-400' => $rate >= 85,
                                                        'text-amber-500 dark:text-amber-400'     => $rate >= 70 && $rate < 85,
                                                        'text-red-500 dark:text-red-400'         => $rate < 70,
                                                    ])>{{ $cell }}</span>
                                                @elseif($isStatus)
                                                    @php
                                                        $badgeVariant = match($cell) {
                                                            'Presente', 'Tardanza' => 'success',
                                                            'Ausente'              => 'error',
                                                            'Justificado'          => 'info',
                                                            default                => 'slate',
                                                        };
                                                    @endphp
                                                    <x-ui.badge :variant="$badgeVariant" size="sm" :dot="false">{{ $cell }}</x-ui.badge>
                                                @else
                                                    {{ $cell }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-dark-card rounded-3xl p-12 shadow-sm border border-slate-100 dark:border-dark-border">
                    <x-ui.empty-state variant="simple" title="Sin datos"
                        description="No hay registros para el período y filtros seleccionados." />
                </div>
            @endif

        @else
            {{-- Estado inicial / instrucciones --}}
            <div class="bg-white dark:bg-dark-card rounded-3xl p-12 shadow-sm border border-slate-100 dark:border-dark-border">
                <div class="flex flex-col items-center gap-4 text-center">
                    <div class="p-4 rounded-2xl bg-slate-100 dark:bg-white/10 text-slate-400">
                        <x-heroicon-o-document-chart-bar class="w-10 h-10" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-700 dark:text-slate-200">Configura y genera el reporte</h3>
                        <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">
                            Selecciona el tipo de reporte, establece el período y haz clic en <strong>Generar Reporte</strong>.
                        </p>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
