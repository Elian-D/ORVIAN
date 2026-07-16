{{-- resources/views/livewire/app/academic/course-index.blade.php --}}
<div>
    <div class="p-4 md:p-6">
        <x-ui.page-header title="Gestión de Cursos" description="Supervisión y organización de niveles académicos.">
            <x-slot:actions>
                <x-ui.button href="{{ route('app.academic.courses.create') }}"
                    variant="primary" size="sm" iconLeft="heroicon-o-plus">
                    Nuevo Curso
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Selector de Tanda (Centrado y Prioritario) --}}
        <div class="inline-flex items-center bg-slate-200/50 dark:bg-white/5 p-1 rounded-xl shadow-inner border border-slate-200 dark:border-white/10 mb-8">
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

        <div class="flex gap-6 items-start">
            {{-- ══ Contenido principal ══ --}}
            <div class="flex-1 min-w-0 space-y-10">

                @forelse($this->structure as $level)
                    <section>
                        <div class="flex items-center gap-3 mb-5">
                            <h2 class="text-xs font-black uppercase tracking-widest
                                       text-slate-400 dark:text-slate-500 whitespace-nowrap">
                                {{ $level['name'] }}
                            </h2>
                            <div class="flex-grow border-t border-slate-200 dark:border-dark-border"></div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($level['grades'] as $grade)

                                {{-- Card académica --}}
                                @if($grade['academic']->isNotEmpty() || $grade['technical_groups']->isEmpty())
                                    <x-academic.course-card
                                        :grade="$grade"
                                        :sections="$grade['academic']"
                                        type="academic" />
                                @endif

                                {{-- Cards técnicas --}}
                                @foreach($grade['technical_groups'] as $techGroup)
                                    <x-academic.course-card
                                        :grade="$grade"
                                        :sections="$techGroup['sections']"
                                        :tech-group="$techGroup"
                                        type="technical" />
                                @endforeach

                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="flex flex-col items-center justify-center py-24 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-white/5
                                    flex items-center justify-center mb-4">
                            <x-heroicon-o-academic-cap class="w-8 h-8 text-slate-300 dark:text-slate-600" />
                        </div>
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">
                            No hay cursos configurados
                        </p>
                        <p class="text-xs text-slate-400 dark:text-slate-600 mt-1">
                            Crea el primer curso para comenzar a organizar los estudiantes.
                        </p>
                        <div class="mt-5">
                            <x-ui.button href="{{ route('app.academic.courses.create') }}"
                                variant="primary" size="sm" iconLeft="heroicon-o-plus">
                                Crear primer curso
                            </x-ui.button>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- ══ Sidebar ══ --}}
            <aside class="hidden lg:flex flex-col gap-4 w-[17rem] flex-shrink-0">
                <div class="bg-white dark:bg-dark-card rounded-2xl
                            border border-slate-200 dark:border-dark-border p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <x-heroicon-s-chart-bar class="w-4 h-4 text-orvian-orange flex-shrink-0" />
                        <h3 class="text-[10px] font-black uppercase tracking-widest
                                   text-slate-700 dark:text-white">
                            Resumen Académico
                        </h3>
                    </div>
                    <div class="space-y-2.5">
                        <div class="rounded-xl p-3.5 bg-slate-50 dark:bg-white/5
                                    border border-slate-100 dark:border-dark-border">
                            <p class="text-[9px] font-black uppercase tracking-widest mb-1
                                       text-slate-400 dark:text-slate-600">Total Estudiantes</p>
                            <p class="text-2xl font-black leading-none text-slate-800 dark:text-white">
                                {{ number_format($this->stats['total_students']) }}
                            </p>
                        </div>
                        <div class="rounded-xl p-3.5 bg-slate-50 dark:bg-white/5
                                    border border-slate-100 dark:border-dark-border">
                            <p class="text-[9px] font-black uppercase tracking-widest mb-1
                                       text-slate-400 dark:text-slate-600">Secciones Activas</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl font-black leading-none text-slate-800 dark:text-white">
                                    {{ $this->stats['total_active'] }}
                                </p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-600">
                                    en {{ collect($this->structure)->count() }} niveles
                                </p>
                            </div>
                            @if($this->stats['total_inactive'] > 0)
                                <p class="text-[9px] mt-1 text-slate-400 dark:text-slate-600">
                                    + {{ $this->stats['total_inactive'] }} inactivas
                                </p>
                            @endif
                        </div>
                        @php
                            $year = \App\Models\Tenant\Academic\AcademicYear::where('school_id', Auth::user()->school_id)
                                ->where('is_active', true)->first();
                        @endphp
                        @if($year)
                            <div class="rounded-xl p-3.5
                                        bg-orvian-orange/8 dark:bg-orvian-orange/10
                                        border border-orvian-orange/15 dark:border-orvian-orange/12">
                                <p class="text-[9px] font-black uppercase tracking-widest mb-1
                                           text-orvian-orange/70">Año Escolar</p>
                                <p class="text-lg font-black leading-none text-orvian-orange">
                                    {{ $year->year_name ?? $year->name }}
                                </p>
                                @if($year->start_date && $year->end_date)
                                    @php
                                        $start     = \Carbon\Carbon::parse($year->start_date);
                                        $end       = \Carbon\Carbon::parse($year->end_date);
                                        $totalDays = max($start->diffInDays($end), 1);
                                        $elapsed   = min($start->diffInDays(now()), $totalDays);
                                        $progress  = round(($elapsed / $totalDays) * 100);
                                    @endphp
                                    <div class="mt-2.5">
                                        <div class="w-full h-1.5 rounded-full bg-orvian-orange/20">
                                            <div class="h-full rounded-full bg-orvian-orange"
                                                 style="width: {{ $progress }}%"></div>
                                        </div>
                                        <p class="text-[9px] text-orvian-orange/60 mt-1 text-right">
                                            {{ $progress }}% completado
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- <div class="bg-white dark:bg-dark-card rounded-2xl
                            border border-slate-200 dark:border-dark-border p-5">
                    <h3 class="text-[10px] font-black uppercase tracking-widest mb-3
                               text-slate-700 dark:text-white">Acciones Rápidas</h3>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([
                            ['icon' => 'heroicon-o-document-text', 'label' => 'Listados'],
                            ['icon' => 'heroicon-o-arrow-up-tray', 'label' => 'Importar'],
                            ['icon' => 'heroicon-o-envelope',      'label' => 'Circular'],
                            ['icon' => 'heroicon-o-cog-6-tooth',   'label' => 'Config'],
                        ] as $action)
                            <button class="flex flex-col items-center gap-2 p-3.5 rounded-xl
                                           text-center group transition-all
                                           bg-slate-50 dark:bg-white/5
                                           border border-slate-100 dark:border-dark-border
                                           hover:bg-slate-100 dark:hover:bg-white/8
                                           hover:border-slate-200 dark:hover:border-white/15">
                                <x-dynamic-component :component="$action['icon']"
                                    class="w-5 h-5 transition-colors
                                           text-slate-400 dark:text-slate-600
                                           group-hover:text-slate-600 dark:group-hover:text-slate-400" />
                                <span class="text-[10px] font-semibold transition-colors
                                             text-slate-500 dark:text-slate-500
                                             group-hover:text-slate-700 dark:group-hover:text-slate-300">
                                    {{ $action['label'] }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div> --}}
            </aside>
        </div>
    </div>

    {{-- Modal confirmación de eliminación --}}
    <x-modal name="delete-section-confirm" maxWidth="sm">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-950/40
                            flex items-center justify-center flex-shrink-0">
                    <x-heroicon-o-trash class="w-5 h-5 text-red-500 dark:text-red-400" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                        Eliminar sección
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Esta acción no se puede deshacer.
                    </p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-5">
                La sección no tiene estudiantes y puede eliminarse del sistema.
                Si en el futuro necesitas esta combinación, deberás crearla nuevamente.
            </p>
            <div class="flex gap-3 justify-end">
                <x-ui.button
                    x-on:click="$dispatch('close-modal', 'delete-section-confirm')"
                    wire:click="$set('showDeleteConfirm', false)"
                    variant="ghost" size="sm">
                    Cancelar
                </x-ui.button>
                <x-ui.button
                    wire:click="executeDelete"
                    variant="danger" size="sm"
                    wire:loading.attr="disabled" wire:target="executeDelete">
                    <span wire:loading.remove wire:target="executeDelete">Eliminar</span>
                    <span wire:loading wire:target="executeDelete">Eliminando...</span>
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>