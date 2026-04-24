<div class="p-6 space-y-6 h-full">
    {{-- Header con Información de Sesión --}}
    <div class="mb-6 space-y-4">
        {{-- Banner de Advertencia Minimalista --}}
        <div class="flex items-center gap-2.5 rounded-orvian border border-amber-300 bg-amber-50 p-2.5 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10">
            {{-- Icono con efecto de pulso sutil para llamar la atención --}}
            <div class="flex shrink-0 items-center justify-center rounded-lg bg-amber-100 p-1.5 dark:bg-amber-500/20">
                <x-heroicon-s-exclamation-triangle class="h-4 w-4 text-amber-600 animate-pulse dark:text-amber-400" />
            </div>
            
            {{-- Texto unificado y más fino --}}
            <div class="text-xs flex-1 min-w-0">
                <p class="text-amber-950 dark:text-amber-100">
                    <span class="font-bold">Modo Auditoría Activo:</span>
                    <span class="font-medium text-amber-900/80 dark:text-amber-200/80">
                        Los cambios afectan el reporte final. Modifica registros solo si es necesario.
                    </span>
                </p>
            </div>
        </div>

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

                {{-- Overlay Flotante (Aparece en Hover o Focus) --}}
                <div class="absolute inset-0 z-10 flex items-center justify-center opacity-0 backdrop-blur-[2px] transition-all duration-300 group-hover:opacity-100 group-focus:opacity-100 bg-white/50 dark:bg-slate-900/60">
                    
                    @if($record->status !== 'excused')
                        {{-- Controles de Cambio de Estado (Menú Píldora) --}}
                        <div class="flex scale-95 items-center gap-1 rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-slate-900/5 transition-transform duration-300 group-hover:scale-100 group-focus:scale-100 dark:bg-dark-bg dark:ring-white/10">
                            
                            {{-- Botón Presente --}}
                            <button
                                wire:click="updateStatus({{ $record->id }}, 'present')"
                                @class([
                                    'flex h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-xs font-bold transition-all',
                                    'bg-emerald-500 text-white shadow-sm' => $record->status === 'present',
                                    'text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 dark:text-slate-300 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400' => $record->status !== 'present',
                                ])
                                title="Marcar como Presente">
                                <x-heroicon-s-check-circle class="h-4 w-4" />
                                <span>P</span>
                            </button>

                            {{-- Botón Tardanza --}}
                            <button
                                wire:click="updateStatus({{ $record->id }}, 'late')"
                                @class([
                                    'flex h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-xs font-bold transition-all',
                                    'bg-amber-500 text-white shadow-sm' => $record->status === 'late',
                                    'text-slate-600 hover:bg-amber-50 hover:text-amber-600 dark:text-slate-300 dark:hover:bg-amber-500/10 dark:hover:text-amber-400' => $record->status !== 'late',
                                ])
                                title="Marcar como Tardanza">
                                <x-heroicon-s-clock class="h-4 w-4" />
                                <span>T</span>
                            </button>

                            {{-- Botón Ausente --}}
                            <button
                                wire:click="updateStatus({{ $record->id }}, 'absent')"
                                @class([
                                    'flex h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-xs font-bold transition-all',
                                    'bg-red-500 text-white shadow-sm' => $record->status === 'absent',
                                    'text-slate-600 hover:bg-red-50 hover:text-red-600 dark:text-slate-300 dark:hover:bg-red-500/10 dark:hover:text-red-400' => $record->status !== 'absent',
                                ])
                                title="Marcar como Ausente">
                                <x-heroicon-s-x-circle class="h-4 w-4" />
                                <span>A</span>
                            </button>
                        </div>
                    @else
                        {{-- Mensaje Flotante para Excusados --}}
                        <div class="mx-4 flex scale-95 flex-col items-center justify-center rounded-xl bg-white p-3 shadow-xl ring-1 ring-blue-500/20 transition-transform duration-300 group-hover:scale-100 group-focus:scale-100 dark:bg-dark-bg text-center">
                            <p class="text-xs font-bold text-blue-600 dark:text-blue-400">
                                Registro de Solo Lectura
                            </p>
                            @if($record->notes)
                                <p class="mt-1 text-[10px] leading-tight text-slate-500 dark:text-slate-400">
                                    {{ Str::limit($record->notes, 40) }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
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
</div>