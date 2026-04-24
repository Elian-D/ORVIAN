{{--
    Layout: alto fijo = viewport − navbar (3.5rem).
    El toolbar sticky queda arriba, el content scrollea en su propio overflow,
    y el footer siempre visible al fondo sin position:fixed.
--}}
<div class="flex flex-col bg-slate-50 dark:bg-dark-bg">

    {{-- ══ Toolbar ══════════════════════════════════════════════ --}}
    <x-app.module-toolbar>
        <x-slot:title>
            <span class="font-bold">Pase de Lista</span>
            @if($selectedAssignment)
                <span class="hidden sm:inline text-slate-400 dark:text-slate-500 font-normal mx-1">—</span>
                <span class="hidden sm:inline font-semibold truncate max-w-[120px] md:max-w-none">{{ $selectedAssignment->subject->name }}</span>
                <span class="hidden md:inline text-[11px] font-normal text-slate-400 dark:text-slate-500 ml-1">
                    {{ $selectedAssignment->section->full_label }}
                    · {{ now()->isoFormat('D MMM YYYY') }}
                </span>
            @endif
        </x-slot:title>
        <x-slot:actions>
            <button wire:click="toggleSubstituteMode"
                    @class([
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                        'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 ring-1 ring-amber-400/60' => $isSubstituteMode,
                        'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' => ! $isSubstituteMode,
                    ])>
                <x-heroicon-s-arrows-right-left class="w-3.5 h-3.5" />
                {{ $isSubstituteMode ? 'Modo Sustituto' : 'Mis Clases' }}
            </button>
        </x-slot:actions>
    </x-app.module-toolbar>

    {{-- ══ Área scrolleable ═════════════════════════════════════ --}}
    <div class="flex-1 min-h-0 overflow-y-auto px-4 md:px-8 py-4 md:py-6 space-y-4">

        {{-- ── Barra compacta: clase seleccionada / selector ──── --}}
        <div class="bg-white dark:bg-dark-card rounded-xl border border-slate-200 dark:border-white/10 flex items-center justify-between px-4 py-3">

            @if($selectedAssignment)
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                         style="background-color: {{ $selectedAssignment->subject->color ?? '#6366f1' }}"></div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                            {{ $selectedAssignment->subject->name }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $selectedAssignment->section->full_label }}
                            @if($isSubstituteMode)
                                <span class="text-amber-500 ml-1">(sustituto)</span>
                            @endif
                        </p>
                    </div>
                </div>
                <button @click="$dispatch('open-modal', 'class-selector')"
                        class="flex-shrink-0 ml-3 text-xs font-medium text-orvian-orange hover:text-orvian-orange/80 transition-colors">
                    Cambiar
                </button>

            @elseif($isSubstituteMode && $substituteSectionId)
                {{-- Sección escogida pero sin asignación todavía --}}
                <div class="flex items-center gap-3 min-w-0">
                    <x-heroicon-o-academic-cap class="w-4 h-4 text-amber-500 flex-shrink-0" />
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Sección seleccionada — elige la materia a sustituir
                    </p>
                </div>
                <button @click="$dispatch('open-modal', 'class-selector')"
                        class="flex-shrink-0 ml-3 flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-semibold transition-all">
                    Ver clases
                </button>

            @else
                <p class="text-sm text-slate-400 dark:text-slate-500">
                    {{ $isSubstituteMode ? 'Busca la sección a cubrir' : 'Ninguna clase seleccionada' }}
                </p>
                <button @click="$dispatch('open-modal', 'class-selector')"
                        class="flex-shrink-0 ml-3 flex items-center gap-1.5 px-3 py-1.5 bg-orvian-orange hover:bg-orvian-orange/90 text-white rounded-lg text-xs font-semibold transition-all">
                    <x-heroicon-s-academic-cap class="w-3.5 h-3.5" />
                    {{ $isSubstituteMode ? 'Buscar Sección' : 'Seleccionar Clase' }}
                </button>
            @endif
        </div>

        {{-- ── Botón Cargar Lista ──────────────────────────────── --}}
        @if($selectedAssignmentId && ! $studentsLoaded)
            <div class="flex justify-center pt-2">
                <button wire:click="loadStudents" wire:loading.attr="disabled"
                        class="flex items-center gap-2 px-6 py-2.5 bg-orvian-orange hover:bg-orvian-orange/90 disabled:opacity-60 text-white rounded-xl font-semibold text-sm transition-all shadow-sm">
                    <x-heroicon-s-users class="w-4 h-4" />
                    <span wire:loading.remove>Cargar Lista de Estudiantes</span>
                    <span wire:loading class="hidden" wire:loading.class.remove="hidden">Cargando...</span>
                </button>
            </div>
        @endif

        {{-- ── Aviso: sin registros de plantel hoy ───────────────── --}}
        @if(! $hasPlantelRecordsToday)
            <div class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700/50 rounded-xl">
                <x-heroicon-s-information-circle class="w-5 h-5 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-semibold text-blue-800 dark:text-blue-300">
                        Sin registros de entrada hoy
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                        No se han registrado entradas en el plantel para la fecha de hoy.
                        Los estudiantes aparecerán como presentes por defecto hasta que el portero registre la sesión.
                    </p>
                </div>
            </div>
        @endif

        {{-- ── Alerta de Pasilleo ──────────────────────────────── --}}
        @if($studentsLoaded && $pasilleoCount > 0)
            <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700/50 rounded-xl">
                <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                        Alerta de pasilleo:
                        {{ $pasilleoCount }} {{ $pasilleoCount === 1 ? 'estudiante marcado' : 'estudiantes marcados' }}
                        como Ausente en el aula, pero registrado en el plantel.
                    </p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                        Estos estudiantes entraron al colegio pero no aparecen en tu clase. Verifica antes de guardar.
                    </p>
                </div>
            </div>
        @endif

        {{-- ── Lista de Estudiantes (Cards) ────────────────────── --}}
        @if($studentsLoaded)
            <div class="space-y-2">
                @forelse($students as $student)
                    @php
                        $current       = $studentStatuses[$student->id] ?? 'present';
                        $plantelStatus = $plantelStatuses[$student->id] ?? null;
                        $locked        = in_array($student->id, $lockedStudentIds);
                    @endphp

                    <div @class([
                        'flex items-center gap-3 p-3.5 rounded-xl border transition-all',
                        'bg-white dark:bg-dark-card border-slate-200 dark:border-white/10',
                        'opacity-50' => $locked,
                    ])>
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <x-ui.student-avatar :student="$student" size="sm" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                                    {{ $student->full_name }}
                                </p>
                                @if($locked)
                                    <p class="text-xs mt-0.5">
                                        @if($plantelStatus === 'absent')
                                            <span class="text-red-500 dark:text-red-400">🔒 Ausente en Plantel</span>
                                        @elseif($plantelStatus === 'excused')
                                            <span class="text-blue-500 dark:text-blue-400">🛡️ Excusa Aprobada</span>
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>

                        @if($locked)
                            <div class="flex-shrink-0">
                                <span @class([
                                    'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold',
                                    'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'    => $plantelStatus === 'absent',
                                    'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' => $plantelStatus === 'excused',
                                ])>
                                    {{ $plantelStatus === 'absent' ? 'Ausente' : 'Justificado' }}
                                </span>
                            </div>
                        @else
                            {{-- Segmented control estilo iOS --}}
                            <div class="flex-shrink-0 flex rounded-lg overflow-hidden border border-slate-200 dark:border-white/10 text-xs font-semibold">
                                <button wire:click="setStatus({{ $student->id }}, 'present')"
                                        @class([
                                            'px-3 py-1.5 transition-colors',
                                            'bg-green-500 text-white'                                                                                     => $current === 'present',
                                            'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-green-50 dark:hover:bg-green-900/20' => $current !== 'present',
                                        ])>
                                    Presente
                                </button>
                                <button wire:click="setStatus({{ $student->id }}, 'late')"
                                        @class([
                                            'px-3 py-1.5 transition-colors border-l border-r border-slate-200 dark:border-white/10',
                                            'bg-amber-500 text-white'                                                                                     => $current === 'late',
                                            'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-amber-50 dark:hover:bg-amber-900/20' => $current !== 'late',
                                        ])>
                                    Tardanza
                                </button>
                                <button wire:click="setStatus({{ $student->id }}, 'absent')"
                                        @class([
                                            'px-3 py-1.5 transition-colors',
                                            'bg-red-500 text-white'                                                                                       => $current === 'absent',
                                            'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-red-50 dark:hover:bg-red-900/20'     => $current !== 'absent',
                                        ])>
                                    Ausente
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state
                        icon="heroicon-o-users"
                        title="Sin estudiantes"
                        description="No se encontraron estudiantes activos en esta sección."
                        variant="dashed"
                        class="bg-white dark:bg-dark-card"
                    />
                @endforelse
            </div>
        @endif

    </div>{{-- fin área scrolleable --}}

    {{-- ══ Footer: siempre al fondo (no fixed) ════════════════ --}}
    @if($studentsLoaded && ! $submitted)
        <div class="shrink-0 bg-white/95 dark:bg-dark-card/95 backdrop-blur-sm border-t border-slate-200 dark:border-white/10 px-4 py-3">
            <div class="max-w-3xl mx-auto flex items-center justify-between gap-4">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ count($studentStatuses) }} estudiantes ·
                    <span class="text-green-600 dark:text-green-400 font-semibold">
                        {{ collect($studentStatuses)->filter(fn($s) => $s === 'present')->count() }} presentes
                    </span>
                    @if(collect($studentStatuses)->filter(fn($s) => $s === 'absent')->count() > 0)
                        ·
                        <span class="text-red-500 dark:text-red-400 font-semibold">
                            {{ collect($studentStatuses)->filter(fn($s) => $s === 'absent')->count() }} ausentes
                        </span>
                    @endif
                </p>
                <x-ui.button wire:click="saveAttendance" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Guardar Pase de Lista</span>
                    <span wire:loading class="hidden" wire:loading.class.remove="hidden">Guardando...</span>
                </x-ui.button>
            </div>
        </div>
    @endif

    {{-- ══ Slide-over: selector combinado (normal + sustituto) ═ --}}
    <x-ui.slide-over
        name="class-selector"
        :title="$isSubstituteMode ? 'Buscar Sección (Sustituto)' : 'Mis Clases'"
        maxWidth="sm"
    >
        @if(! $isSubstituteMode)
            {{-- ── Modo Normal ─────────────────────────────────── --}}
            @forelse($myAssignments as $assignment)
                <button
                    @click="$wire.selectAssignment({{ $assignment->id }}); show = false;"
                    @class([
                        'w-full flex items-center gap-3 px-3 py-3.5 rounded-xl text-left border transition-all mb-2',
                        'border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20'          => $selectedAssignmentId === $assignment->id,
                        'border-slate-100 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-white/5' => $selectedAssignmentId !== $assignment->id,
                    ])>
                    <div class="w-3 h-3 rounded-full flex-shrink-0"
                         style="background-color: {{ $assignment->subject->color ?? '#6366f1' }}"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                            {{ $assignment->subject->name }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $assignment->section->full_label }}
                        </p>
                    </div>
                    @if($selectedAssignmentId === $assignment->id)
                        <x-heroicon-s-check-circle class="w-5 h-5 text-blue-500 flex-shrink-0" />
                    @endif
                </button>
            @empty
                <div class="py-10 text-center">
                    <x-heroicon-o-academic-cap class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
                    <p class="text-sm text-slate-400">No tienes clases asignadas este año académico.</p>
                </div>
            @endforelse

        @else
            {{-- ── Modo Sustituto ──────────────────────────────── --}}

            {{-- Buscador --}}
            <div class="relative mb-4">
                <x-heroicon-s-magnifying-glass class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                    wire:model.live.debounce.400ms="substituteSearch"
                    type="text"
                    placeholder="Buscar grado o sección (ej: 1ro, A, Matutino)..."
                    class="w-full pl-9 pr-4 py-2.5 bg-slate-50 dark:bg-dark-bg border border-slate-200 dark:border-white/10 rounded-lg text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-orvian-orange/50 transition-all"
                />
            </div>

            {{-- Resultados de secciones --}}
            @if($substituteSections->isNotEmpty())
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    Secciones encontradas
                </p>
                <div class="space-y-1 mb-4">
                    @foreach($substituteSections as $section)
                        <button wire:click="selectSubstituteSection({{ $section->id }})"
                                @class([
                                    'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left border transition-all',
                                    'border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20' => $substituteSectionId === $section->id,
                                    'border-transparent hover:bg-slate-50 dark:hover:bg-white/5'               => $substituteSectionId !== $section->id,
                                ])>
                            <x-heroicon-o-academic-cap class="w-4 h-4 text-slate-400 flex-shrink-0" />
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ $section->full_label }}
                            </span>
                            @if($substituteSectionId === $section->id)
                                <x-heroicon-s-check-circle class="w-4 h-4 text-amber-500 ml-auto" />
                            @endif
                        </button>
                    @endforeach
                </div>
            @elseif(strlen($substituteSearch) >= 2)
                <p class="text-xs text-slate-400 text-center py-4">
                    Sin resultados para "{{ $substituteSearch }}"
                </p>
            @elseif(strlen($substituteSearch) === 0)
                <div class="py-6 text-center text-slate-400">
                    <x-heroicon-o-magnifying-glass class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600" />
                    <p class="text-sm">Escribe al menos 2 caracteres para buscar.</p>
                </div>
            @endif

            {{-- Asignaciones de la sección seleccionada --}}
            @if($sectionAssignments->isNotEmpty())
                <div class="pt-3 border-t border-slate-100 dark:border-white/5">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                        Clases disponibles en esta sección
                    </p>
                    <div class="space-y-1">
                        @foreach($sectionAssignments as $assignment)
                            <button
                                @click="$wire.selectAssignment({{ $assignment->id }}); show = false;"
                                @class([
                                    'w-full flex items-center gap-3 px-3 py-3 rounded-xl text-left border transition-all',
                                    'border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20'          => $selectedAssignmentId === $assignment->id,
                                    'border-slate-100 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-white/5' => $selectedAssignmentId !== $assignment->id,
                                ])>
                                <div class="w-3 h-3 rounded-full flex-shrink-0"
                                     style="background-color: {{ $assignment->subject->color ?? '#6366f1' }}"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                                        {{ $assignment->subject->name }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        Prof. {{ $assignment->teacher?->full_name }}
                                    </p>
                                </div>
                                @if($selectedAssignmentId === $assignment->id)
                                    <x-heroicon-s-check-circle class="w-5 h-5 text-blue-500 flex-shrink-0" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

        @endif
    </x-ui.slide-over>

</div>
