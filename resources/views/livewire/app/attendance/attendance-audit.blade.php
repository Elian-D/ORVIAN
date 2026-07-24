<div class="p-6 space-y-6 h-full">
    {{-- Header con Información de Sesión --}}
    <div class="mb-6 space-y-4">
        {{-- Banner de Advertencia Minimalista --}}
        @if($session->date->isToday())
            <div class="flex items-start gap-2.5 rounded-orvian border border-amber-300 bg-amber-50 p-3 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10">
                {{-- Icono con efecto de pulso sutil para llamar la atención --}}
                <div class="flex shrink-0 items-center justify-center rounded-lg bg-amber-100 p-1.5 dark:bg-amber-500/20">
                    <x-heroicon-s-exclamation-triangle class="h-4 w-4 text-amber-600 animate-pulse dark:text-amber-400" />
                </div>

                {{-- Título + las dos transiciones separadas, para que se lean de un vistazo --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-amber-950 dark:text-amber-100">
                        Modo Auditoría Activo
                    </p>
                    <ul class="mt-1 space-y-1 text-xs font-medium text-amber-900/80 dark:text-amber-200/80">
                        <li class="flex items-center gap-1.5">
                            <x-heroicon-s-arrow-long-right class="h-3.5 w-3.5 flex-shrink-0 text-amber-500" />
                            <span><strong class="font-semibold">Ausente → Tardanza</strong>, si llegó tarde sin haber avisado.</span>
                        </li>
                        <li class="flex items-center gap-1.5">
                            <x-heroicon-s-arrow-long-right class="h-3.5 w-3.5 flex-shrink-0 text-amber-500" />
                            <span><strong class="font-semibold">Excusado → Presente</strong>, si el kiosko no alcanzó a registrar su llegada.</span>
                        </li>
                    </ul>
                </div>
            </div>
        @else
            <div class="flex items-center gap-2.5 rounded-orvian border border-slate-200 bg-slate-50 p-2.5 shadow-sm dark:border-dark-border dark:bg-white/5">
                <div class="flex shrink-0 items-center justify-center rounded-lg bg-slate-100 p-1.5 dark:bg-white/10">
                    <x-heroicon-s-lock-closed class="h-4 w-4 text-slate-500 dark:text-slate-400" />
                </div>

                <div class="text-xs flex-1 min-w-0">
                    <p class="text-slate-700 dark:text-slate-300">
                        <span class="font-bold">Solo Lectura:</span>
                        <span class="font-medium text-slate-600/80 dark:text-slate-400/80">
                            Esta sesión ya no es de hoy. Los registros de fechas pasadas no pueden modificarse.
                        </span>
                    </p>
                </div>
            </div>
        @endif

        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    Auditoría de Asistencia
                </h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    Sesión del {{ $session->date->format('d/m/Y') }} - {{ $session->shift->type }}
                </p>
            </div>

            <x-ui.button 
                href="{{ route('app.attendance.hub') }}" 
                variant="secondary" 
                type="ghost"
                iconLeft="heroicon-o-arrow-left">
                Volver al Hub
            </x-ui.button>
        </div>
    </div>

    {{-- Filtros por Estado --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        {{-- Filtro: Todos --}}
        <button 
            wire:click="setFilter('all')"
            @class([
                'group relative overflow-hidden rounded-xl border-2 p-4 text-left transition-all duration-200',
                'border-slate-200 bg-white hover:border-slate-300 dark:border-dark-border dark:bg-dark-card dark:hover:border-slate-700' => $activeFilter !== 'all',
                'border-orvian-orange bg-orvian-orange/5 dark:bg-orvian-orange/10' => $activeFilter === 'all',
            ])>
            <div class="relative z-10">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Todos
                </p>
                <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">
                    {{ $stats['all'] }}
                </p>
            </div>
            
            @if($activeFilter === 'all')
                <div class="absolute inset-0 bg-gradient-to-br from-orvian-orange/5 to-transparent"></div>
            @endif
        </button>

        {{-- Filtro: Presentes --}}
        <button 
            wire:click="setFilter('present')"
            @class([
                'group relative overflow-hidden rounded-xl border-2 p-4 text-left transition-all duration-200',
                'border-slate-200 bg-white hover:border-emerald-300 dark:border-dark-border dark:bg-dark-card dark:hover:border-emerald-700' => $activeFilter !== 'present',
                'border-emerald-500 bg-emerald-500/5 dark:bg-emerald-500/10' => $activeFilter === 'present',
            ])>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Presentes
                    </p>
                    <p class="mt-1 text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $stats['present'] }}
                    </p>
                </div>
                <div class="rounded-lg bg-emerald-500/10 p-2">
                    <x-heroicon-s-check-circle class="h-5 w-5 text-emerald-500" />
                </div>
            </div>
            
            @if($activeFilter === 'present')
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent"></div>
            @endif
        </button>

        {{-- Filtro: Tardanzas --}}
        <button 
            wire:click="setFilter('late')"
            @class([
                'group relative overflow-hidden rounded-xl border-2 p-4 text-left transition-all duration-200',
                'border-slate-200 bg-white hover:border-amber-300 dark:border-dark-border dark:bg-dark-card dark:hover:border-amber-700' => $activeFilter !== 'late',
                'border-amber-500 bg-amber-500/5 dark:bg-amber-500/10' => $activeFilter === 'late',
            ])>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Tardanzas
                    </p>
                    <p class="mt-1 text-3xl font-bold text-amber-600 dark:text-amber-400">
                        {{ $stats['late'] }}
                    </p>
                </div>
                <div class="rounded-lg bg-amber-500/10 p-2">
                    <x-heroicon-s-clock class="h-5 w-5 text-amber-500" />
                </div>
            </div>
            
            @if($activeFilter === 'late')
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent"></div>
            @endif
        </button>

        {{-- Filtro: Ausentes --}}
        <button 
            wire:click="setFilter('absent')"
            @class([
                'group relative overflow-hidden rounded-xl border-2 p-4 text-left transition-all duration-200',
                'border-slate-200 bg-white hover:border-red-300 dark:border-dark-border dark:bg-dark-card dark:hover:border-red-700' => $activeFilter !== 'absent',
                'border-red-500 bg-red-500/5 dark:bg-red-500/10' => $activeFilter === 'absent',
            ])>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Ausentes
                    </p>
                    <p class="mt-1 text-3xl font-bold text-red-600 dark:text-red-400">
                        {{ $stats['absent'] }}
                    </p>
                </div>
                <div class="rounded-lg bg-red-500/10 p-2">
                    <x-heroicon-s-x-circle class="h-5 w-5 text-red-500" />
                </div>
            </div>
            
            @if($activeFilter === 'absent')
                <div class="absolute inset-0 bg-gradient-to-br from-red-500/5 to-transparent"></div>
            @endif
        </button>

        {{-- Filtro: Excusados --}}
        <button 
            wire:click="setFilter('excused')"
            @class([
                'group relative overflow-hidden rounded-xl border-2 p-4 text-left transition-all duration-200',
                'border-slate-200 bg-white hover:border-blue-300 dark:border-dark-border dark:bg-dark-card dark:hover:border-blue-700' => $activeFilter !== 'excused',
                'border-blue-500 bg-blue-500/5 dark:bg-blue-500/10' => $activeFilter === 'excused',
            ])>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-600 dark:text-slate-400">
                        Excusados
                    </p>
                    <p class="mt-1 text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $stats['excused'] }}
                    </p>
                </div>
                <div class="rounded-lg bg-blue-500/10 p-2">
                    <x-heroicon-s-information-circle class="h-5 w-5 text-blue-500" />
                </div>
            </div>
            
            @if($activeFilter === 'excused')
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent"></div>
            @endif
        </button>
    </div>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="w-full sm:max-w-xs">
            <x-data-table.search 
                placeholder="Buscar estudiante..." 
                filterKey="search" 
            />
        </div>
        
        {{-- Aquí podrías poner un contador de resultados o un botón de exportar --}}
        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
            Mostrando {{ $this->filteredRecords->count() }} estudiantes
        </div>
    </div>


    {{-- Grid de Estudiantes --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($this->filteredRecords as $record)
            @php
                $colors = $this->statusColor[$record->status] ?? [];
            @endphp

            <div 
                wire:key="record-{{ $record->id }}"
                tabindex="0"
                @class([
                    'group relative overflow-hidden rounded-xl border-2 p-4 transition-all duration-300 focus:outline-none',
                    $colors['bg'] ?? 'bg-white dark:bg-dark-card',
                    $colors['border'] ?? 'border-slate-200 dark:border-dark-border',
                ])>
                
                {{-- Contenido Base de la Card --}}
                <div class="flex items-center gap-3">
                    {{-- Avatar del Estudiante --}}
                    <x-ui.student-avatar 
                        :student="$record->student" 
                        size="lg"
                    />

                    {{-- Información del Estudiante --}}
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                            {{ $record->student->full_name }}
                        </h3>
                        <p class="mt-0.5 truncate text-xs text-slate-600 dark:text-slate-400">
                            {{ $record->student->current_grade }}
                        </p>

                        {{-- Indicador de Estado Actual --}}
                        <div class="mt-1 flex items-center gap-1.5">
                            @if($record->status === 'present')
                                <x-heroicon-s-check-circle class="h-4 w-4 {{ $colors['icon'] }}" />
                                <span class="text-xs font-medium {{ $colors['text'] }}">Presente</span>
                            @elseif($record->status === 'late')
                                <x-heroicon-s-clock class="h-4 w-4 {{ $colors['icon'] }}" />
                                <span class="text-xs font-medium {{ $colors['text'] }}">Tardanza</span>
                            @elseif($record->status === 'absent')
                                <x-heroicon-s-x-circle class="h-4 w-4 {{ $colors['icon'] }}" />
                                <span class="text-xs font-medium {{ $colors['text'] }}">Ausente</span>
                            @elseif($record->status === 'excused')
                                <x-heroicon-s-information-circle class="h-4 w-4 {{ $colors['icon'] }}" />
                                <span class="text-xs font-medium {{ $colors['text'] }}">Excusado</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Overlay Flotante (Aparece en Hover o Focus) — únicas acciones permitidas --}}
                @if($this->canMarkAsLate($record))
                    <div class="absolute inset-0 z-10 flex items-center justify-center opacity-0 backdrop-blur-[2px] transition-all duration-300 group-hover:opacity-100 group-focus:opacity-100 bg-white/50 dark:bg-slate-900/60">
                        <button
                            wire:click="confirmMarkAsLate({{ $record->id }})"
                            class="flex scale-95 h-10 items-center justify-center gap-2 rounded-xl bg-white px-4 text-xs font-bold text-amber-600 shadow-xl ring-1 ring-slate-900/5 transition-all duration-300 hover:bg-amber-50 group-hover:scale-100 group-focus:scale-100 dark:bg-dark-bg dark:text-amber-400 dark:ring-white/10 dark:hover:bg-amber-500/10"
                            title="Marcar como Tardanza">
                            <x-heroicon-s-clock class="h-4 w-4" />
                            <span>Marcar como Tardanza</span>
                        </button>
                    </div>
                @elseif($this->canMarkAsPresent($record))
                    @can('manage_excuses')
                        <div class="absolute inset-0 z-10 flex items-center justify-center opacity-0 backdrop-blur-[2px] transition-all duration-300 group-hover:opacity-100 group-focus:opacity-100 bg-white/50 dark:bg-slate-900/60">
                            <button
                                wire:click="confirmMarkAsPresent({{ $record->id }})"
                                class="flex scale-95 h-10 items-center justify-center gap-2 rounded-xl bg-white px-4 text-xs font-bold text-blue-600 shadow-xl ring-1 ring-slate-900/5 transition-all duration-300 hover:bg-blue-50 group-hover:scale-100 group-focus:scale-100 dark:bg-dark-bg dark:text-blue-400 dark:ring-white/10 dark:hover:bg-blue-500/10"
                                title="Marcar como Presente">
                                <x-heroicon-s-check-circle class="h-4 w-4" />
                                <span>Marcar como Presente</span>
                            </button>
                        </div>
                    @endcan
                @endif
            </div>
        @empty
            {{-- Estado Vacío --}}
            <div class="col-span-full">
                <x-ui.empty-state
                    variant="simple"
                    title="No hay estudiantes con este filtro"
                    variant="dashed"
                    icon="heroicon-o-user-group"
                    description="Intenta seleccionar otro filtro para ver más registros."
                />
            </div>
        @endforelse
    </div>

    {{-- ══════════════════════════════════════════
        MODAL: Confirmar corrección a Tardanza (tipo advertencia)
    ══════════════════════════════════════════ --}}
    <x-modal wire:model="showMarkLateModal" name="confirm-mark-late" maxWidth="md">
        <div class="px-6 py-5 bg-white dark:bg-dark-card">
            <div class="flex items-center gap-4">
                {{-- Foto grande del estudiante — ayuda a identificarlo de un vistazo --}}
                <div class="w-20 h-20 rounded-2xl overflow-hidden ring-2 ring-amber-200 dark:ring-amber-500/30 shadow-sm bg-slate-100 dark:bg-dark-bg flex-shrink-0">
                    @if($this->recordToMarkLate?->student?->photo_path)
                        <img
                            src="{{ asset('storage/' . $this->recordToMarkLate->student->photo_path) }}"
                            alt="{{ $this->recordToMarkLate->student->full_name }}"
                            class="w-full h-full object-cover"
                        >
                    @else
                        <div class="w-full h-full flex items-center justify-center text-2xl font-black text-slate-300 dark:text-slate-600 uppercase">
                            {{ $this->recordToMarkLate ? Illuminate\Support\Str::substr($this->recordToMarkLate->student->first_name, 0, 1) . Illuminate\Support\Str::substr($this->recordToMarkLate->student->last_name, 0, 1) : '?' }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="flex-shrink-0 w-6 h-6 rounded-lg bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5" />
                        </span>
                        <h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight">
                            ¿Marcar como Tardanza?
                        </h3>
                    </div>
                    @if($this->recordToMarkLate)
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 truncate">
                            {{ $this->recordToMarkLate->student->full_name }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex items-start gap-2.5 rounded-orvian border border-amber-300 bg-amber-50 p-3 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10">
                <x-heroicon-s-light-bulb class="h-4 w-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                <p class="text-xs text-amber-900 dark:text-amber-200">
                    Antes de confirmar, asegúrate de tener al estudiante frente a ti — esta corrección
                    es solo para quien llegó tarde sin avisar, no para justificar una ausencia real.
                </p>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-white/5">
                <x-ui.button
                    variant="secondary"
                    size="sm"
                    x-on:click="show = false"
                >
                    Cancelar
                </x-ui.button>
                <x-ui.button
                    variant="warning"
                    size="sm"
                    wire:click="markAsLate"
                    wire:loading.attr="disabled"
                    iconLeft="heroicon-s-clock"
                >
                    Sí, marcar como Tardanza
                </x-ui.button>
            </div>
        </div>
    </x-modal>

    {{-- ══════════════════════════════════════════
        MODAL: Confirmar corrección de Excusado a Presente
    ══════════════════════════════════════════ --}}
    <x-modal wire:model="showMarkPresentModal" name="confirm-mark-present" maxWidth="md">
        <div class="px-6 py-5 bg-white dark:bg-dark-card">
            <div class="flex items-center gap-4">
                {{-- Foto grande del estudiante — ayuda a identificarlo de un vistazo --}}
                <div class="w-20 h-20 rounded-2xl overflow-hidden ring-2 ring-blue-200 dark:ring-blue-500/30 shadow-sm bg-slate-100 dark:bg-dark-bg flex-shrink-0">
                    @if($this->recordToMarkPresent?->student?->photo_path)
                        <img
                            src="{{ asset('storage/' . $this->recordToMarkPresent->student->photo_path) }}"
                            alt="{{ $this->recordToMarkPresent->student->full_name }}"
                            class="w-full h-full object-cover"
                        >
                    @else
                        <div class="w-full h-full flex items-center justify-center text-2xl font-black text-slate-300 dark:text-slate-600 uppercase">
                            {{ $this->recordToMarkPresent ? Illuminate\Support\Str::substr($this->recordToMarkPresent->student->first_name, 0, 1) . Illuminate\Support\Str::substr($this->recordToMarkPresent->student->last_name, 0, 1) : '?' }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="flex-shrink-0 w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                        </span>
                        <h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight">
                            ¿Marcar como Presente?
                        </h3>
                    </div>
                    @if($this->recordToMarkPresent)
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 truncate">
                            {{ $this->recordToMarkPresent->student->full_name }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex items-start gap-2.5 rounded-orvian border border-blue-300 bg-blue-50 p-3 shadow-sm dark:border-blue-500/30 dark:bg-blue-500/10">
                <x-heroicon-s-light-bulb class="h-4 w-4 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                <p class="text-xs text-blue-900 dark:text-blue-200">
                    Antes de confirmar, verifica que el estudiante esté frente a ti — este ajuste es para
                    cuando la sesión cerró antes de registrar su llegada real, no para invalidar una excusa
                    que ya no aplica.
                </p>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-white/5">
                <x-ui.button
                    variant="secondary"
                    size="sm"
                    x-on:click="show = false"
                >
                    Cancelar
                </x-ui.button>
                <x-ui.button
                    variant="info"
                    size="sm"
                    wire:click="markAsPresent"
                    wire:loading.attr="disabled"
                    iconLeft="heroicon-s-check-circle"
                >
                    Sí, marcar como Presente
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>