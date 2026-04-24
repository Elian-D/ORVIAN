<div class="flex min-h-screen bg-slate-50 dark:bg-dark-bg">
    {{-- ══════════════════════════════════════════════════
         CONTENIDO PRINCIPAL
    ═══════════════════════════════════════════════════ --}}
    <main class="flex-1 flex flex-col">
        
        {{-- Header Principal --}}
        <header class="bg-white dark:bg-dark-card border-b border-slate-200 dark:border-white/10 px-4 md:px-8 py-4 md:py-6">

            {{-- Page Header --}}
            <x-ui.page-header
                title="Asistencia Manual"
                description="Maneja la asistencia manual de los estudiantes."
                :count="$students->total()"
                countLabel="estudiantes"
            >
                <x-slot:actions>
                    @if($activeSession)
                        {{-- Botón cuando hay una sesión activa --}}
                        <x-ui.button
                            variant="error"
                            type="outline"
                            size="sm"
                            iconLeft="heroicon-s-stop"
                            :href="route('app.attendance.session')" 
                        >
                            <span class="hidden sm:inline">Cerrar Sesión</span>
                            <span class="sm:hidden">Cerrar</span>
                        </x-ui.button>

                        <x-ui.button
                            variant="secondary"
                            type="outline"
                            size="sm"
                            iconLeft="heroicon-s-cpu-chip"
                            :href="route('app.attendance.scanner')"
                        >
                            <span class="hidden sm:inline">Ir a Escáner</span>
                            <span class="sm:hidden">Escáner</span>
                        </x-ui.button>
                    @else
                        {{-- Botón cuando no hay sesión activa --}}
                        <x-ui.button
                            variant="primary"
                            size="sm"
                            iconLeft="heroicon-s-play" 
                            :href="route('app.attendance.session')"
                        >
                            <span class="hidden sm:inline">Abrir Sesión</span>
                            <span class="sm:hidden">Abrir</span>
                        </x-ui.button>  
                    @endif
                </x-slot:actions>
            </x-ui.page-header>

            {{-- Barra de búsqueda y filtros --}}
            <div class="mt-6 flex flex-col gap-6">

                {{-- 1. Selector de Tanda (Centrado y Prioritario) --}}
                <div class="flex justify-center">
                    <div class="inline-flex items-center bg-slate-200/50 dark:bg-white/5 p-1 rounded-xl shadow-inner">
                        @foreach($this->shifts as $shift)
                            <button 
                                wire:click="$set('selectedShiftId', {{ $shift->id }})"
                                @class([
                                    'px-6 py-2 rounded-lg text-xs font-bold transition-all duration-200',
                                    'bg-white dark:bg-orvian-orange shadow-md text-orvian-orange dark:text-white scale-100' => $selectedShiftId == $shift->id,
                                    'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-300/30 dark:hover:bg-white/5' => $selectedShiftId != $shift->id
                                ])
                            >
                                {{ $shift->type }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- 2. Fila de Filtros (Buscador + Selects) --}}
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3 md:gap-4">
                    
                    {{-- Search --}}
                    <div class="flex-1 relative">
                        <x-heroicon-s-magnifying-glass class="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="filters.search"
                            placeholder="Buscar por nombre..."
                            class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-dark-card border border-slate-200 dark:border-white/10 rounded-xl text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-orvian-orange/50 transition-all shadow-sm">
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        {{-- Filtro de Sección --}}
                        <select
                            wire:model.live="filters.section"
                            class="flex-1 sm:flex-none px-3 md:px-4 py-2.5 bg-white dark:bg-dark-card border border-slate-200 dark:border-white/10 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orvian-orange/50 transition-all shadow-sm">
                            <option value="">Todas las Secciones</option>
                            @foreach($this->sections as $section)
                                <option value="{{ $section->id }}">
                                    {{ $section->full_label }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Filtro de Estado --}}
                        <select
                            wire:model.live="filters.status_filter"
                            class="flex-1 sm:flex-none px-3 md:px-4 py-2.5 bg-white dark:bg-dark-card border border-slate-200 dark:border-white/10 rounded-xl text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orvian-orange/50 transition-all shadow-sm">
                            <option value="">Todos los Estados</option>
                            <option value="pending">Pendientes</option>
                            <option value="registered">Registrados</option>
                        </select>

                        @if(collect($filters)->except('hide_excused')->filter()->isNotEmpty() || $filters['hide_excused'] !== true)
                            <x-ui.button 
                                type="ghost" 
                                variant="secondary" 
                                size="sm"
                                wire:click="clearAllFilters"
                                iconLeft="heroicon-m-funnel"
                                class="text-slate-500 hover:text-red-600 transition-colors shrink-0"
                            >
                                <span class="hidden sm:inline">Limpiar</span>
                            </x-ui.button>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        {{-- Banner sin sesión --}}
        @if(!$activeSession)
            <div class="mx-4 md:mx-8 mt-4 md:mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 rounded-lg flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                <div class="flex-1">
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                        No hay ninguna sesión activa para esta tanda.
                    </p>
                    <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                        Un administrador debe abrir la sesión de asistencia antes de registrar las entradas.
                    </p>
                </div>
                <a href="{{ route('app.attendance.session') }}" 
                   class="w-full sm:w-auto text-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg transition-all">
                    Abrir Sesión
                </a>
            </div>
        @endif

        @if($activeSession)
            {{-- Tabla --}}
            <div class="flex-1 overflow-auto px-4 md:px-8 py-4 md:py-6"
                x-data="{
                    focusedIndex: 0,
                    rowCount: {{ $students->count() }},
                    justRegistered: null,
                    
                    init() {
                        // Listener de eventos Livewire
                        Livewire.on('student-recorded', (event) => {
                            this.justRegistered = event.studentId;
                            setTimeout(() => {
                                this.justRegistered = null;
                            }, 2000);
                        });
                    },
                    
                    handleKeydown(e) {
                        // Ignorar si está escribiendo en input
                        if (['input', 'textarea', 'select'].includes(document.activeElement.tagName.toLowerCase())) return;

                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            this.focusedIndex = (this.focusedIndex + 1) % this.rowCount;
                            this.scrollToFocused();
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            this.focusedIndex = (this.focusedIndex - 1 + this.rowCount) % this.rowCount;
                            this.scrollToFocused();
                        } else if (['1', '2', '3'].includes(e.key)) {
                            e.preventDefault();
                            const row = document.getElementById('row-' + this.focusedIndex);
                            if (row) {
                                const studentId = parseInt(row.dataset.id);
                                const status = e.key === '1' ? 'present' : (e.key === '2' ? 'late' : 'absent');
                                $wire.record(studentId, status);
                                
                                // Auto-avanzar
                                if (this.focusedIndex < this.rowCount - 1) {
                                    this.focusedIndex++;
                                    this.scrollToFocused();
                                }
                            }
                        }
                    },
                    
                    scrollToFocused() {
                        this.$nextTick(() => {
                            const el = document.getElementById('row-' + this.focusedIndex);
                            if (el) el.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        });
                    }
                }"
                @keydown.window="handleKeydown">

                @if($students->isEmpty())
                    <div class="col-span-full">
                        <x-ui.empty-state
                            icon="heroicon-o-users"
                            title="No se encontraron estudiantes"
                            description="No hay registros que coincidan con los filtros aplicados. Intenta buscar con otros términos o limpia los filtros actuales."
                            variant="dashed"
                            class="bg-white dark:bg-dark-card"
                        />
                    </div>
                @else
                    <div class="bg-white dark:bg-dark-card rounded-xl border border-slate-200 dark:border-white/10 overflow-hidden">
                        {{-- Vista de tabla para pantallas medianas y grandes --}}
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-dark-card border-b border-slate-200 dark:border-white/10">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Detalles del Estudiante</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Entrada Manual</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    @foreach($students as $index => $student)
                                        <tr id="row-{{ $index }}"
                                            data-id="{{ $student->id }}"
                                            @click="focusedIndex = {{ $index }}"
                                            :class="{
                                                'bg-green-50 dark:bg-green-900/10': justRegistered === {{ $student->id }},
                                                'bg-slate-50 dark:bg-slate-800/30': focusedIndex === {{ $index }} && justRegistered !== {{ $student->id }}
                                            }"
                                            class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-all cursor-pointer"
                                            x-transition>
                                            
                                            {{-- Student Details --}}
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    {{-- Avatar --}}
                                                    <div class="flex-shrink-0">
                                                        <x-ui.student-avatar :student="$student" size="md"/>
                                                    </div>
                                                    
                                                    {{-- Info --}}
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                                            {{ $student->first_name }} {{ $student->last_name }}
                                                        </p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                                            {{ $student->section?->full_label }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- Manual Entry --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    @if($student->attendance_status)
                                                        {{-- Ya registrado - mostrar estado actual --}}
                                                        @php
                                                            $config = match($student->attendance_status) {
                                                                'present' => ['bg' => 'bg-green-500', 'label' => 'P', 'class' => 'bg-green-500 text-white'],
                                                                'late' => ['bg' => 'bg-amber-500', 'label' => 'T', 'class' => 'bg-amber-500 text-white'],
                                                                'absent' => ['bg' => 'bg-red-500', 'label' => 'A', 'class' => 'bg-red-500 text-white'],
                                                                default => ['bg' => 'bg-slate-400', 'label' => '?', 'class' => 'bg-slate-400 text-white'],
                                                            };
                                                        @endphp
                                                        
                                                        <span class="{{ $config['class'] }} w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold">
                                                            {{ $config['label'] }}
                                                        </span>
                                                        
                                                        @if($student->attendance_time)
                                                            <span class="text-xs text-slate-400 ml-2">{{ $student->attendance_time }}</span>
                                                        @endif
                                                    @else
                                                        {{-- Botones de registro --}}
                                                        <button
                                                            wire:click="record({{ $student->id }}, 'present')"
                                                            @class([
                                                                'group relative w-8 h-8 rounded-lg font-bold text-sm transition-all',
                                                                'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-500 hover:text-white',
                                                                'ring-2 ring-green-500' => $index === 0,
                                                            ])
                                                            title="Present (1)">
                                                            P
                                                            <span class="absolute -top-6 left-1/2 -translate-x-1/2 hidden group-hover:block bg-black text-white text-[10px] px-1.5 rounded whitespace-nowrap">
                                                                Press 1
                                                            </span>
                                                        </button>

                                                        <button
                                                            wire:click="record({{ $student->id }}, 'late')"
                                                            class="group relative w-8 h-8 rounded-lg font-bold text-sm bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 hover:bg-amber-500 hover:text-white transition-all"
                                                            title="Tardanza (2)">
                                                            T
                                                            <span class="absolute -top-6 left-1/2 -translate-x-1/2 hidden group-hover:block bg-black text-white text-[10px] px-1.5 rounded whitespace-nowrap">
                                                                Press 2
                                                            </span>
                                                        </button>

                                                        <button
                                                            wire:click="record({{ $student->id }}, 'absent')"
                                                            class="group relative w-8 h-8 rounded-lg font-bold text-sm bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-500 hover:text-white transition-all"
                                                            title="Absent (3)">
                                                            A
                                                            <span class="absolute -top-6 left-1/2 -translate-x-1/2 hidden group-hover:block bg-black text-white text-[10px] px-1.5 rounded whitespace-nowrap">
                                                                Press 3
                                                            </span>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Vista de tarjetas para móviles --}}
                        <div class="md:hidden divide-y divide-slate-100 dark:divide-white/5">
                            @foreach($students as $index => $student)
                                <div id="row-{{ $index }}"
                                    data-id="{{ $student->id }}"
                                    @click="focusedIndex = {{ $index }}"
                                    :class="{
                                        'bg-green-50 dark:bg-green-900/10': justRegistered === {{ $student->id }},
                                        'bg-slate-50 dark:bg-slate-800/30': focusedIndex === {{ $index }} && justRegistered !== {{ $student->id }}
                                    }"
                                    class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-all"
                                    x-transition>
                                    
                                    {{-- Student Info --}}
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <x-ui.student-avatar :student="$student" size="sm"/>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                                    {{ $student->first_name }} {{ $student->last_name }}
                                                </p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                    {{ $student->section?->full_label }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="flex items-center gap-2">
                                        @if($student->attendance_status)
                                            @php
                                                $config = match($student->attendance_status) {
                                                    'present' => ['bg' => 'bg-green-500', 'label' => 'Presente', 'class' => 'bg-green-500 text-white'],
                                                    'late' => ['bg' => 'bg-amber-500', 'label' => 'Tarde', 'class' => 'bg-amber-500 text-white'],
                                                    'absent' => ['bg' => 'bg-red-500', 'label' => 'Ausente', 'class' => 'bg-red-500 text-white'],
                                                    default => ['bg' => 'bg-slate-400', 'label' => 'Desconocido', 'class' => 'bg-slate-400 text-white'],
                                                };
                                            @endphp
                                            
                                            <div class="flex items-center gap-2 flex-1">
                                                <span class="{{ $config['class'] }} px-3 py-1.5 rounded-lg text-xs font-bold">
                                                    {{ $config['label'] }}
                                                </span>
                                                @if($student->attendance_time)
                                                    <span class="text-xs text-slate-400">{{ $student->attendance_time }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <button
                                                wire:click="record({{ $student->id }}, 'present')"
                                                class="flex-1 py-2 rounded-lg font-semibold text-sm bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 active:bg-green-500 active:text-white transition-all">
                                                Presente
                                            </button>

                                            <button
                                                wire:click="record({{ $student->id }}, 'late')"
                                                class="flex-1 py-2 rounded-lg font-semibold text-sm bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 active:bg-amber-500 active:text-white transition-all">
                                                Tarde
                                            </button>

                                            <button
                                                wire:click="record({{ $student->id }}, 'absent')"
                                                class="flex-1 py-2 rounded-lg font-semibold text-sm bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 active:bg-red-500 active:text-white transition-all">
                                                Ausente
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Paginación --}}
                    <div class="mt-6 md:mt-8">
                        {{ $students->links('pagination.orvian-ledger') }}
                    </div>
                @endif
            </div>
            

            {{-- Footer con estadísticas --}}
            <footer class="bg-white dark:bg-dark-card border-t border-slate-200 dark:border-white/10 px-4 md:px-8 py-4">
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                    
                    {{-- Métricas: Texto + Badge (Responsive scroll) --}}
                    <div class="flex items-center gap-3 md:gap-4 overflow-x-auto pb-2 lg:pb-0 w-full lg:w-auto no-scrollbar">
                        <div class="flex items-center gap-3 md:gap-4 flex-nowrap text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="hidden sm:inline">Total:</span>
                                <span class="sm:hidden">T:</span>
                                <x-ui.badge variant="slate" size="sm"  :dot="false" class="font-black">
                                    {{ $stats['registered'] }}<span class="opacity-40 mx-0.5">/</span>{{ $stats['total'] }}
                                </x-ui.badge>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span class="hidden sm:inline">Presentes:</span>
                                <span class="sm:hidden">P:</span>
                                <x-ui.badge variant="success" size="sm" :dot="false" class="font-black">
                                    {{ $stats['present'] }}
                                </x-ui.badge>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span class="hidden sm:inline">Ausentes:</span>
                                <span class="sm:hidden">A:</span>
                                <x-ui.badge variant="error" size="sm" :dot="false" class="font-black">
                                    {{ $stats['absent'] }}
                                </x-ui.badge>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span class="hidden sm:inline">Tardes:</span>
                                <span class="sm:hidden">T:</span>
                                <x-ui.badge variant="warning" size="sm" :dot="false" class="font-black">
                                    {{ $stats['late'] }}
                                </x-ui.badge>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span class="hidden sm:inline">Excusas:</span>
                                <span class="sm:hidden">E:</span>
                                <x-ui.badge variant="info" size="sm" :dot="false" class="font-black">
                                    {{ $stats['excused'] }}
                                </x-ui.badge>
                            </div>
                        </div>
                    </div>

                    {{-- Lado derecho: Barra y Tiempo --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 md:gap-6 w-full lg:w-auto">
                        <div class="flex items-center gap-3 md:gap-4">
                            <div class="flex-1 sm:w-32 md:w-64 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                @php
                                    $percentage = $stats['total'] > 0 ? round(($stats['registered'] / $stats['total']) * 100) : 0;
                                @endphp
                                <div class="h-full bg-green-500 transition-all duration-500" style="width: {{ $percentage }}%"></div>
                            </div>

                            <span class="text-xs text-slate-400 whitespace-nowrap font-semibold">{{ round($percentage) }}%</span>
                        </div>

                        <div class="flex items-center justify-between sm:justify-start gap-3 md:gap-4">
                            <span class="text-xs text-slate-400 whitespace-nowrap">Hora: {{ now()->format('H:i') }}</span>
                        </div>
                    </div>

                </div>
            </footer>
        @endif
    </main>
</div>