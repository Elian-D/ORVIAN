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
                if (this.viewMonth === 0) { this.viewMonth = 11; this.viewYear--; }
                else this.viewMonth--;
            },

            nextMonth() {
                if (this.viewMonth === 11) { this.viewMonth = 0; this.viewYear++; }
                else this.viewMonth++;
            },

            fmt(d) {
                return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            },

            fmtDisplay(s) {
                if (!s) return '';
                const [y, m, d] = s.split('-');
                return new Date(y, m - 1, d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
            },

            selectDate(date) {
                this.selectedDate = date;
                this.open = false;
                $wire.set('filters.' + wireKey, date);
            },

            clearDate() {
                this.selectedDate = null;
                $wire.set('filters.' + wireKey, '');
            },
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
            <x-ui.button
                variant="secondary"
                type="ghost"
                size="sm"
                iconLeft="heroicon-s-document-arrow-down"
                wire:click="exportPdf"
                wire:loading.attr="disabled"
            >
                PDF
            </x-ui.button>
        </x-slot:actions>
    </x-app.module-toolbar>

    <div class="p-4 md:p-6 flex flex-col gap-6">

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
                    <x-heroicon-s-chevron-down
                        class="w-3 h-3 text-slate-400 transition-transform duration-200"
                        x-bind:class="open ? 'rotate-180' : ''"
                        x-show="!selectedDate"
                    />
                </button>

                <button x-show="selectedDate" @click="clearDate()" type="button"
                    class="flex items-center justify-center w-6 h-6 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                    title="Limpiar fecha desde" style="display: none;">
                    <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
                </button>

                {{-- Calendar dropdown --}}
                <div x-show="open" @click.outside="open = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                     class="absolute top-full left-0 mt-2 z-50 w-72 bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-dark-border shadow-xl p-4"
                     style="display: none;">

                    <div class="flex items-center justify-between mb-4">
                        <button @click="prevMonth()" type="button"
                            class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                            <x-heroicon-s-chevron-left class="w-4 h-4" />
                        </button>
                        <span x-text="monthLabel" class="text-sm font-bold text-slate-800 dark:text-white capitalize"></span>
                        <button @click="nextMonth()" type="button"
                            class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                            <x-heroicon-s-chevron-right class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="grid grid-cols-7 gap-1 mb-1">
                        @foreach(['L','M','X','J','V','S','D'] as $day)
                            <div class="text-center text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase">{{ $day }}</div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        <template x-for="day in days" :key="day.date">
                            <button
                                @click="selectDate(day.date)"
                                type="button"
                                :class="{
                                    'relative aspect-square flex items-center justify-center rounded-lg text-xs font-bold transition-all': true,
                                    'opacity-25 pointer-events-none': !day.curr,
                                    'bg-orvian-orange text-white shadow-md': day.sel,
                                    'ring-2 ring-orvian-orange ring-offset-1 dark:ring-offset-dark-card text-orvian-orange': day.today && !day.sel,
                                    'hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300': !day.sel && day.curr,
                                }"
                                x-text="day.day">
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
                    <x-heroicon-s-chevron-down
                        class="w-3 h-3 text-slate-400 transition-transform duration-200"
                        x-bind:class="open ? 'rotate-180' : ''"
                        x-show="!selectedDate"
                    />
                </button>

                <button x-show="selectedDate" @click="clearDate()" type="button"
                    class="flex items-center justify-center w-6 h-6 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors"
                    title="Limpiar fecha hasta" style="display: none;">
                    <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
                </button>

                {{-- Calendar dropdown --}}
                <div x-show="open" @click.outside="open = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                     class="absolute top-full left-0 mt-2 z-50 w-72 bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-dark-border shadow-xl p-4"
                     style="display: none;">

                    <div class="flex items-center justify-between mb-4">
                        <button @click="prevMonth()" type="button"
                            class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                            <x-heroicon-s-chevron-left class="w-4 h-4" />
                        </button>
                        <span x-text="monthLabel" class="text-sm font-bold text-slate-800 dark:text-white capitalize"></span>
                        <button @click="nextMonth()" type="button"
                            class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                            <x-heroicon-s-chevron-right class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="grid grid-cols-7 gap-1 mb-1">
                        @foreach(['L','M','X','J','V','S','D'] as $day)
                            <div class="text-center text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase">{{ $day }}</div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        <template x-for="day in days" :key="day.date">
                            <button
                                @click="selectDate(day.date)"
                                type="button"
                                :class="{
                                    'relative aspect-square flex items-center justify-center rounded-lg text-xs font-bold transition-all': true,
                                    'opacity-25 pointer-events-none': !day.curr,
                                    'bg-orvian-orange text-white shadow-md': day.sel,
                                    'ring-2 ring-orvian-orange ring-offset-1 dark:ring-offset-dark-card text-orvian-orange': day.today && !day.sel,
                                    'hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300': !day.sel && day.curr,
                                }"
                                x-text="day.day">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Limpiar período (visible cuando hay al menos una fecha) --}}
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
            title="Historial de Plantel"
            description="Registros de entrada y verificación de asistencia institucional."
            :count="$records->total()"
            countLabel="registros"
        />

        {{-- ── Tabla ────────────────────────────────────────────────────────── --}}
        <x-data-table.base-table
            :items="$records"
            :definition="\App\Tables\App\Attendance\PlantelAttendanceTableConfig::class"
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

                    <x-data-table.filter-select
                        label="Método"
                        filterKey="method"
                        :options="[
                            'manual' => 'Manual',
                            'qr'     => 'Código QR',
                            'facial' => 'Reconocimiento Facial',
                        ]"
                        placeholder="Todos los métodos"
                    />

                    {{-- <x-data-table.filter-select
                        label="Verificación"
                        filterKey="verified"
                        :options="[
                            'yes' => 'Verificados',
                            'no'  => 'Pendientes',
                        ]"
                        placeholder="Todos"
                    /> --}}
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

                    {{-- Cédula --}}
                    <x-data-table.cell column="rnc" :visible="$visibleColumns">
                        <span class="text-xs font-mono text-slate-500">{{ $record->student->rnc ?? '—' }}</span>
                    </x-data-table.cell>

                    {{-- Sección --}}
                    <x-data-table.cell column="section" :visible="$visibleColumns">
                        @if($record->student->section)
                            <x-ui.badge variant="info" size="sm">
                                {{ $record->student->section->grade?->name }}° - {{ $record->student->section->label }}
                            </x-ui.badge>
                        @else
                            <span class="text-slate-400 text-sm">—</span>
                        @endif
                    </x-data-table.cell>

                    {{-- Tanda --}}
                    <x-data-table.cell column="shift" :visible="$visibleColumns">
                        <span class="text-sm text-slate-600 dark:text-slate-400">
                            {{ $record->shift?->type ?? '—' }}
                        </span>
                    </x-data-table.cell>

                    {{-- Hora --}}
                    <x-data-table.cell column="time" :visible="$visibleColumns">
                        <span class="text-sm font-mono text-slate-600 dark:text-slate-400">
                            {{ $record->time?->format('h:i A') ?? '—' }}
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

                    {{-- Método --}}
                    <x-data-table.cell column="method" :visible="$visibleColumns">
                        @php
                            $methodVariant = match($record->method) {
                                'qr'     => 'info',
                                'facial' => 'primary',
                                default  => 'slate',
                            };
                        @endphp
                        <x-ui.badge :variant="$methodVariant" size="sm" :dot="false">
                            {{ $record->method_label }}
                        </x-ui.badge>
                    </x-data-table.cell>

                    {{-- Registrado Por --}}
                    <x-data-table.cell column="registered_by" :visible="$visibleColumns">
                        <span class="text-sm text-slate-600 dark:text-slate-400">
                            {{ $record->registeredBy?->name ?? '—' }}
                        </span>
                    </x-data-table.cell>

                    {{-- Verificación --}}
                    {{-- <x-data-table.cell column="verified" :visible="$visibleColumns">
                        @if($record->verified_at)
                            <div class="flex items-center gap-1.5">
                                <x-heroicon-s-check-badge class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                                <span class="text-xs text-slate-500 truncate max-w-[120px]" title="{{ $record->verifiedBy?->name }}">
                                    {{ $record->verifiedBy?->name ?? '—' }}
                                </span>
                            </div>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs text-amber-500 font-medium">
                                <x-heroicon-s-clock class="w-3.5 h-3.5" />
                                Pendiente
                            </span>
                        @endif
                    </x-data-table.cell> --}}

                    {{-- Acciones --}}
                    <td class="px-4 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-1">
                            @can('attendance_plantel.verify')
                                {{-- @unless($record->verified_at)
                                    <x-ui.button
                                        variant="success"
                                        type="ghost"
                                        size="sm"
                                        icon="heroicon-o-check-badge"
                                        wire:click="verify({{ $record->id }})"
                                        title="Marcar como verificado"
                                    />
                                @endunless --}}
                                <x-ui.button
                                    variant="secondary"
                                    type="ghost"
                                    size="sm"
                                    icon="heroicon-o-pencil-square"
                                    wire:click="openEdit({{ $record->id }})"
                                    title="Editar registro"
                                />
                            @endcan
                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-12">
                        <x-ui.empty-state
                            variant="simple"
                            title="Sin registros"
                            description="No hay registros de asistencia de plantel para los filtros aplicados."
                        />
                    </td>
                </tr>
            @endforelse

        </x-data-table.base-table>

    </div>

    {{-- ── Modal: Editar registro ───────────────────────────────────────────── --}}
    <x-modal name="edit-record-modal" :show="$showEditModal" x-on:close="$wire.closeModals()" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Editar Registro</h2>

            @if($this->selectedRecord)
                <p class="text-sm text-slate-600 dark:text-slate-300 mb-6">
                    Registro de
                    <span class="font-bold">{{ $this->selectedRecord->student->full_name }}</span>
                    del {{ $this->selectedRecord->date->isoFormat('D MMM, YYYY') }}.
                </p>
            @endif

            <div class="space-y-4">
                <x-ui.forms.select
                    label="Estado"
                    name="editStatus"
                    wire:model="editStatus"
                    :error="$errors->first('editStatus')"
                    required
                >
                    <option value="present">Presente</option>
                    <option value="late">Tardanza</option>
                    <option value="absent">Ausente</option>
                    <option value="excused">Justificado</option>
                </x-ui.forms.select>

                <x-ui.forms.textarea
                    label="Notas"
                    name="editNotes"
                    wire:model="editNotes"
                    placeholder="Notas adicionales sobre este registro..."
                    :error="$errors->first('editNotes')"
                    hint="Opcional. Se guardará en el historial del registro."
                />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button
                    type="button"
                    variant="secondary"
                    type="ghost"
                    x-on:click="$dispatch('close-modal', 'edit-record-modal')"
                >
                    Cancelar
                </x-ui.button>
                <x-ui.button
                    type="solid"
                    variant="primary"
                    wire:click="saveEdit"
                    wire:loading.attr="disabled"
                >
                    Guardar Cambios
                </x-ui.button>
            </div>
        </div>
    </x-modal>

</div>
