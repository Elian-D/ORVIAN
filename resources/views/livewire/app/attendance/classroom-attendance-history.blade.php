<div>

    {{-- ── Alpine: Calendario de fecha reutilizable ───────────────────────── --}}
    @script
    <script>
        Alpine.data('calendarDatePicker', (initial, wireKey) => ({
            open: false,
            selectedDate: initial || null,
            viewYear: initial ? parseInt(initial.split('-')[0]) : new Date().getFullYear(),
            viewMonth: initial ? parseInt(initial.split('-')[1]) - 1 : new Date().getMonth(),

            get monthLabel() {
                return new Date(this.viewYear, this.viewMonth, 1)
                    .toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
            },

            get days() {
                const firstDay = new Date(this.viewYear, this.viewMonth, 1);
                const lastDay  = new Date(this.viewYear, this.viewMonth + 1, 0);
                let startDow   = firstDay.getDay() - 1;
                if (startDow < 0) startDow = 6;
                const days = [];
                const now  = new Date();
                const todayStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
                for (let i = startDow; i > 0; i--) {
                    const d = new Date(this.viewYear, this.viewMonth, 1 - i);
                    days.push({ date: this.fmt(d), day: d.getDate(), curr: false, today: false, sel: false });
                }
                for (let d = 1; d <= lastDay.getDate(); d++) {
                    const date = new Date(this.viewYear, this.viewMonth, d);
                    const str  = this.fmt(date);
                    days.push({ date: str, day: d, curr: true, today: str === todayStr, sel: str === this.selectedDate });
                }
                let next = 1;
                while (days.length < 42) {
                    const d = new Date(this.viewYear, this.viewMonth + 1, next++);
                    days.push({ date: this.fmt(d), day: d.getDate(), curr: false, today: false, sel: false });
                }
                return days;
            },

            prevMonth() {
                if (this.viewMonth === 0) { this.viewMonth = 11; this.viewYear--; } else this.viewMonth--;
            },
            nextMonth() {
                if (this.viewMonth === 11) { this.viewMonth = 0; this.viewYear++; } else this.viewMonth++;
            },
            fmt(d) {
                return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            },
            fmtDisplay(s) {
                if (!s) return '';
                const [y, m, d] = s.split('-');
                return new Date(y, m - 1, d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
            },
            selectDate(date) { this.selectedDate = date; this.open = false; $wire.set('filters.' + wireKey, date); },
            clearDate()      { this.selectedDate = null; $wire.set('filters.' + wireKey, ''); },
        }));
    </script>
    @endscript

    {{-- ── Toolbar ─────────────────────────────────────────────────────────── --}}
    <x-app.module-toolbar>
        <x-slot:actions>
            <x-ui.button
                variant="secondary"
                type="ghost"
                size="sm"
                iconLeft="heroicon-s-table-cells"
                wire:click="exportExcel"
                wire:loading.attr="disabled"
            >
                Excel
            </x-ui.button>
        </x-slot:actions>
    </x-app.module-toolbar>

    <div class="p-4 md:p-6 flex flex-col gap-6">

        {{-- ── Banner de contexto para maestros ───────────────────────────── --}}
        @if($isTeacher)
            <div class="flex items-center gap-3 p-3 rounded-2xl bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800/40">
                <div class="flex-shrink-0 p-1.5 rounded-lg bg-purple-100 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400">
                    <x-heroicon-s-academic-cap class="w-4 h-4" />
                </div>
                <p class="text-xs font-medium text-purple-700 dark:text-purple-400">
                    Estás viendo únicamente los registros de tus clases.
                </p>
            </div>
        @endif

        {{-- ── Selector de período (fuera del dropdown, siempre visible) ──── --}}
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400 flex-shrink-0">Período:</span>

            {{-- Fecha Desde --}}
            <div x-data="calendarDatePicker(@js($filters['date_from'] ?: null), 'date_from')"
                 class="relative flex items-center gap-1">
                <button @click="open = !open" type="button"
                    class="flex items-center gap-2 h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-700 dark:text-slate-200 hover:border-slate-300 dark:hover:border-white/20 transition-colors font-medium">
                    <x-heroicon-s-calendar class="w-4 h-4 text-slate-400 flex-shrink-0" />
                    <span x-text="selectedDate ? fmtDisplay(selectedDate) : 'Desde'"></span>
                    <x-heroicon-s-chevron-down class="w-3 h-3 text-slate-400 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" x-show="!selectedDate" />
                </button>
                <button x-show="selectedDate" @click="clearDate()" type="button"
                    class="flex items-center justify-center w-6 h-6 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                    title="Limpiar fecha desde" style="display: none;">
                    <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
                </button>
                {{-- Calendar dropdown --}}
                <div x-show="open" @click.outside="open = false"
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                     class="absolute top-full left-0 mt-2 z-50 w-72 bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-dark-border shadow-xl p-4"
                     style="display: none;">
                    <div class="flex items-center justify-between mb-4">
                        <button @click="prevMonth()" type="button" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors"><x-heroicon-s-chevron-left class="w-4 h-4" /></button>
                        <span x-text="monthLabel" class="text-sm font-bold text-slate-800 dark:text-white capitalize"></span>
                        <button @click="nextMonth()" type="button" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors"><x-heroicon-s-chevron-right class="w-4 h-4" /></button>
                    </div>
                    <div class="grid grid-cols-7 gap-1 mb-1">
                        @foreach(['L','M','X','J','V','S','D'] as $day)
                            <div class="text-center text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase">{{ $day }}</div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7 gap-1">
                        <template x-for="day in days" :key="day.date">
                            <button @click="selectDate(day.date)" type="button"
                                :class="{
                                    'relative aspect-square flex items-center justify-center rounded-lg text-xs font-bold transition-all': true,
                                    'opacity-25 pointer-events-none': !day.curr,
                                    'bg-orvian-orange text-white shadow-md': day.sel,
                                    'ring-2 ring-orvian-orange ring-offset-1 dark:ring-offset-dark-card text-orvian-orange': day.today && !day.sel,
                                    'hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300': !day.sel && day.curr,
                                }" x-text="day.day">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <span class="text-slate-300 dark:text-slate-600 flex-shrink-0 select-none">→</span>

            {{-- Fecha Hasta --}}
            <div x-data="calendarDatePicker(@js($filters['date_to'] ?: null), 'date_to')"
                 class="relative flex items-center gap-1">
                <button @click="open = !open" type="button"
                    class="flex items-center gap-2 h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-700 dark:text-slate-200 hover:border-slate-300 dark:hover:border-white/20 transition-colors font-medium">
                    <x-heroicon-s-calendar class="w-4 h-4 text-slate-400 flex-shrink-0" />
                    <span x-text="selectedDate ? fmtDisplay(selectedDate) : 'Hasta'"></span>
                    <x-heroicon-s-chevron-down class="w-3 h-3 text-slate-400 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" x-show="!selectedDate" />
                </button>
                <button x-show="selectedDate" @click="clearDate()" type="button"
                    class="flex items-center justify-center w-6 h-6 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                    title="Limpiar fecha hasta" style="display: none;">
                    <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
                </button>
                {{-- Calendar dropdown --}}
                <div x-show="open" @click.outside="open = false"
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                     class="absolute top-full left-0 mt-2 z-50 w-72 bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-dark-border shadow-xl p-4"
                     style="display: none;">
                    <div class="flex items-center justify-between mb-4">
                        <button @click="prevMonth()" type="button" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors"><x-heroicon-s-chevron-left class="w-4 h-4" /></button>
                        <span x-text="monthLabel" class="text-sm font-bold text-slate-800 dark:text-white capitalize"></span>
                        <button @click="nextMonth()" type="button" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors"><x-heroicon-s-chevron-right class="w-4 h-4" /></button>
                    </div>
                    <div class="grid grid-cols-7 gap-1 mb-1">
                        @foreach(['L','M','X','J','V','S','D'] as $day)
                            <div class="text-center text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase">{{ $day }}</div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7 gap-1">
                        <template x-for="day in days" :key="day.date">
                            <button @click="selectDate(day.date)" type="button"
                                :class="{
                                    'relative aspect-square flex items-center justify-center rounded-lg text-xs font-bold transition-all': true,
                                    'opacity-25 pointer-events-none': !day.curr,
                                    'bg-orvian-orange text-white shadow-md': day.sel,
                                    'ring-2 ring-orvian-orange ring-offset-1 dark:ring-offset-dark-card text-orvian-orange': day.today && !day.sel,
                                    'hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300': !day.sel && day.curr,
                                }" x-text="day.day">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            @if($filters['date_from'] || $filters['date_to'])
                <button wire:click="clearDateRange" type="button"
                    class="flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-slate-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl border border-slate-200 dark:border-dark-border transition-colors">
                    <x-heroicon-s-x-mark class="w-3 h-3" />
                    Limpiar período
                </button>
            @endif
        </div>

        {{-- ── Page header ─────────────────────────────────────────────────── --}}
        <x-ui.page-header
            title="Historial de Aula"
            description="Registros de pase de lista por clase y maestro."
            :count="$records->total()"
            countLabel="registros"
        />

        {{-- ── Tabla ────────────────────────────────────────────────────────── --}}
        <x-data-table.base-table
            :items="$records"
            :definition="\App\Tables\App\Attendance\ClassroomAttendanceTableConfig::class"
            :visibleColumns="$visibleColumns"
            :activeChips="$this->getActiveChips()"
            :hasFilters="count(array_filter($filters)) > 0"
        >
            <x-slot:filterSlot>
                <x-data-table.filter-container
                    :activeCount="count(array_filter(array_diff_key($filters, ['date_from' => '', 'date_to' => ''])))">

                    <x-data-table.filter-select
                        label="Sección"
                        filterKey="section_id"
                        :options="$sectionOptions"
                        placeholder="Todas las secciones"
                    />

                    <x-data-table.filter-select
                        label="Materia"
                        filterKey="subject_id"
                        :options="$subjectOptions"
                        placeholder="Todas las materias"
                    />

                    <x-data-table.filter-select
                        label="Estado"
                        filterKey="status"
                        :options="[
                            'present' => 'Presente',
                            'late'    => 'Tardanza',
                            'absent'  => 'Ausente',
                            'excused' => 'Justificado',
                        ]"
                        placeholder="Todos los estados"
                    />

                    {{-- Solo visible para directores / coordinadores --}}
                    @unless($isTeacher)
                        <x-data-table.filter-select
                            label="Maestro"
                            filterKey="teacher_id"
                            :options="$teacherOptions"
                            placeholder="Todos los maestros"
                        />
                    @endunless

                </x-data-table.filter-container>
            </x-slot:filterSlot>

            @forelse($records as $record)
                <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors duration-150">

                    {{-- Fecha --}}
                    <x-data-table.cell column="date" :visible="$visibleColumns">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 whitespace-nowrap">
                            {{ $record->date->isoFormat('D MMM, YYYY') }}
                        </span>
                    </x-data-table.cell>

                    {{-- Estudiante --}}
                    <x-data-table.cell column="student" :visible="$visibleColumns">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl overflow-hidden flex-shrink-0 bg-slate-100 dark:bg-white/10">
                                @if($record->student->photo_path)
                                    <img src="{{ Storage::url($record->student->photo_path) }}"
                                         class="w-full h-full object-cover" alt="">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <x-heroicon-s-user class="w-4 h-4 text-slate-400" />
                                    </div>
                                @endif
                            </div>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                {{ $record->student->full_name }}
                            </span>
                        </div>
                    </x-data-table.cell>

                    {{-- Sección --}}
                    <x-data-table.cell column="section" :visible="$visibleColumns">
                        @php $section = $record->assignment?->section; @endphp
                        @if($section)
                            <x-ui.badge variant="info" size="sm">
                                {{ $section->grade?->name }}° - {{ $section->label }}
                            </x-ui.badge>
                        @else
                            <span class="text-slate-400 text-sm">—</span>
                        @endif
                    </x-data-table.cell>

                    {{-- Materia --}}
                    <x-data-table.cell column="subject" :visible="$visibleColumns">
                        @php $subject = $record->assignment?->subject; @endphp
                        @if($subject)
                            <div class="flex items-center gap-2">
                                @if($subject->color)
                                    <span class="flex-shrink-0 w-2 h-2 rounded-full"
                                          style="background-color: {{ $subject->color }}"></span>
                                @endif
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate max-w-[160px]"
                                      title="{{ $subject->name }}">
                                    {{ $subject->name }}
                                </span>
                            </div>
                        @else
                            <span class="text-slate-400 text-sm">—</span>
                        @endif
                    </x-data-table.cell>

                    {{-- Maestro (oculto cuando la vista pertenece al propio maestro) --}}
                    <x-data-table.cell column="teacher" :visible="$visibleColumns">
                        <span class="text-sm text-slate-600 dark:text-slate-400 whitespace-nowrap">
                            {{ $record->teacher?->full_name ?? '—' }}
                        </span>
                    </x-data-table.cell>

                    {{-- Hora de Clase --}}
                    <x-data-table.cell column="class_time" :visible="$visibleColumns">
                        <span class="text-sm font-mono text-slate-600 dark:text-slate-400">
                            {{ $record->class_time
                                ? \Carbon\Carbon::parse($record->class_time)->format('h:i A')
                                : '—' }}
                        </span>
                    </x-data-table.cell>

                    {{-- Estado --}}
                    <x-data-table.cell column="status" :visible="$visibleColumns">
                        @php
                            $statusVariant = match($record->status) {
                                'present' => 'success',
                                'late'    => 'warning',
                                'absent'  => 'error',
                                'excused' => 'info',
                                default   => 'slate',
                            };
                        @endphp
                        <x-ui.badge :variant="$statusVariant" size="sm" dot>
                            {{ $record->status_label }}
                        </x-ui.badge>
                    </x-data-table.cell>

                    {{-- Notas --}}
                    <x-data-table.cell column="notes" :visible="$visibleColumns">
                        @if($record->teacher_notes)
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[200px]"
                               title="{{ $record->teacher_notes }}">
                                {{ $record->teacher_notes }}
                            </p>
                        @else
                            <span class="text-slate-300 dark:text-slate-600 text-sm">—</span>
                        @endif
                    </x-data-table.cell>

                    {{-- Columna de acciones (solo lectura) --}}
                    <td class="px-4 py-3.5"></td>

                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-12">
                        <x-ui.empty-state
                            variant="simple"
                            title="Sin registros"
                            description="{{ $isTeacher
                                ? 'No hay registros de pase de lista para tus clases con los filtros aplicados.'
                                : 'No hay registros de asistencia de aula para los filtros aplicados.' }}"
                        />
                    </td>
                </tr>
            @endforelse

        </x-data-table.base-table>

    </div>

</div>
