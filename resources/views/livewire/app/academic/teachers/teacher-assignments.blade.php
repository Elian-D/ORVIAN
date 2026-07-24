<div>
    <div class="px-4 md:px-6 pt-4">
        <x-ui.page-header title="Asignación de Materias">
            <x-slot:actions>
                <x-ui.button :href="route('app.academic.teachers.show', $teacher)" variant="ghost" size="sm"
                    iconLeft="heroicon-o-arrow-left">
                    Volver
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    {{-- Banner del maestro --}}
    <div class="mx-4 mt-4 md:mx-6 mb-4 flex items-center gap-4 p-4 rounded-2xl bg-white dark:bg-dark-card border border-slate-200 dark:border-white/10 shadow-sm">
        @if($teacher->photo_path)
            <img src="{{ Storage::url($teacher->photo_path) }}" alt="{{ $teacher->full_name }}"
                 class="w-12 h-12 rounded-xl object-cover flex-shrink-0 ring-2 ring-orvian-orange/30">
        @else
            <div class="w-12 h-12 rounded-xl bg-orvian-orange/10 flex items-center justify-center flex-shrink-0">
                <x-heroicon-s-user class="w-6 h-6 text-orvian-orange" />
            </div>
        @endif

        <div class="flex-1 min-w-0">
            <h2 class="text-base font-black text-slate-800 dark:text-white truncate">
                {{ $teacher->full_name }}
            </h2>
            <div class="flex items-center flex-wrap gap-x-3 gap-y-1 mt-0.5">
                <span class="text-[11px] text-slate-500 dark:text-slate-400">
                    {{ $teacher->school->name ?? '' }}
                </span>
                @php
                    $totalAssigned = $teacher->assignments->where('is_active', true)->count();
                    $sectionsWithAssignments = $teacher->assignments->where('is_active', true)->pluck('school_section_id')->unique()->count();
                @endphp
                @if($totalAssigned > 0)
                    <x-ui.badge variant="warning" size="sm">
                        {{ $totalAssigned }} {{ Str::plural('materia', $totalAssigned) }} · {{ $sectionsWithAssignments }} {{ Str::plural('sección', $sectionsWithAssignments) }}
                    </x-ui.badge>
                @else
                    <x-ui.badge variant="slate" size="sm">Sin asignaciones</x-ui.badge>
                @endif
            </div>
        </div>

        <div class="hidden sm:flex items-center gap-2 flex-shrink-0">
            <div class="text-center px-4 py-2 bg-slate-50 dark:bg-white/5 rounded-xl">
                <p class="text-xl font-black text-orvian-orange leading-none">{{ $totalAssigned }}</p>
                <p class="text-[9px] uppercase font-bold text-slate-400 mt-0.5 tracking-wide">Materias</p>
            </div>
            <div class="text-center px-4 py-2 bg-slate-50 dark:bg-white/5 rounded-xl">
                <p class="text-xl font-black text-slate-700 dark:text-white leading-none">{{ $sectionsWithAssignments }}</p>
                <p class="text-[9px] uppercase font-bold text-slate-400 mt-0.5 tracking-wide">Secciones</p>
            </div>
        </div>
    </div>

    {{-- Contenedor principal con altura fija para alojar los paneles con scroll interno --}}
    <div class="flex flex-col lg:flex-row gap-6 p-4 md:p-6 h-[calc(100vh-13rem)]">

        {{-- ══ PANEL IZQUIERDO: Secciones ══ --}}
        <div class="w-full lg:w-80 flex flex-col bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-white/10 overflow-hidden h-[45vh] lg:h-full flex-shrink-0">
            
            {{-- Header y Buscador de Secciones --}}
            <div class="p-4 border-b border-slate-200 dark:border-white/10 bg-slate-50/50 dark:bg-dark-bg/50 space-y-3">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                    Secciones del Centro
                </p>
                <x-ui.forms.input
                    wire:model.live.debounce.300ms="searchSection"
                    placeholder="Buscar curso o sección..."
                    iconLeft="heroicon-o-magnifying-glass"
                    size="sm" />

                {{-- Filtro por tanda --}}
                @if($this->shifts->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        <button wire:click="$set('filterShiftId', null)"
                                class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition-all
                                       {{ is_null($filterShiftId)
                                           ? 'bg-orvian-orange text-white'
                                           : 'bg-slate-100 dark:bg-white/8 text-slate-500 hover:bg-slate-200 dark:hover:bg-white/12' }}">
                            Todas
                        </button>
                        @foreach($this->shifts as $shift)
                            <button wire:click="$set('filterShiftId', {{ $shift->id }})"
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition-all
                                           {{ $filterShiftId === $shift->id
                                               ? 'bg-orvian-orange text-white'
                                               : 'bg-slate-100 dark:bg-white/8 text-slate-500 hover:bg-slate-200 dark:hover:bg-white/12' }}">
                                {{ $shift->type }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Lista de Secciones con Scroll --}}
            <div class="flex-1 overflow-y-auto custom-scroll p-3 space-y-1">
                @forelse($this->sections->groupBy(fn ($s) => $s->grade->level->name) as $nivel => $secciones)
                    <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 px-2 pt-3 pb-1">
                        {{ $nivel }}
                    </p>

                    @foreach($secciones as $section)
                        @php
                            $assignedCount = $teacher->assignments
                                ->where('school_section_id', $section->id)
                                ->where('is_active', true)
                                ->count();
                        @endphp

                        <button wire:click="$set('activeSectionId', {{ $section->id }})"
                                class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-left transition-all
                                       {{ $activeSectionId === $section->id
                                           ? 'bg-orvian-orange text-white shadow-md ring-1 ring-orvian-orange/50'
                                           : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 border border-transparent hover:border-slate-200 dark:hover:border-white/10' }}"
                                           title="{{ $section->full_label }}">
                            
                            <div class="min-w-0 pr-2">
                                <p class="text-xs font-bold leading-none truncate">
                                    {{ $section->full_label }}
                                </p>
                                <p class="text-[9px] opacity-80 mt-1 truncate">
                                    {{ $section->technicalTitle->short_name ?? $section->shift->type ?? 'General' }}
                                </p>
                            </div>

                            @if($assignedCount > 0)
                                <div class="flex-shrink-0 text-[10px] font-black px-2 py-0.5 rounded-full
                                            {{ $activeSectionId === $section->id ? 'bg-white/20 text-white' : 'bg-orvian-orange/10 text-orvian-orange' }}">
                                    {{ $assignedCount }}
                                </div>
                            @endif
                        </button>
                    @endforeach
                @empty
                    <div class="text-center py-10 px-4">
                        <x-heroicon-o-folder-open class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                        <p class="text-xs text-slate-500">No se encontraron secciones.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ══ PANEL DERECHO: Grid de Materias ══ --}}
        <div class="flex-1 flex flex-col bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-white/10 overflow-hidden h-[50vh] lg:h-full min-w-0">
            
            @if(! $activeSectionId)
                <div class="flex flex-col items-center justify-center h-full text-center p-6">
                    <div class="p-4 bg-slate-50 dark:bg-white/5 rounded-full mb-4">
                        <x-heroicon-o-hand-raised class="w-10 h-10 text-slate-400" />
                    </div>
                    <h3 class="text-base font-bold text-slate-700 dark:text-white mb-1">Ninguna sección seleccionada</h3>
                    <p class="text-sm text-slate-500">Selecciona un curso en el panel izquierdo para gestionar las materias impartidas por el maestro.</p>
                </div>
            @else
                @php $subjects = $this->subjectsForActiveSection; @endphp

                {{-- Header y Buscador de Materias --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 border-b border-slate-200 dark:border-white/10 bg-slate-50/50 dark:bg-dark-bg/50">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            Catálogo de Materias
                            <x-ui.badge variant="slate" size="sm">Sección seleccionada</x-ui.badge>
                        </h3>
                    </div>
                    <div class="w-full sm:w-64 flex-shrink-0">
                        <x-ui.forms.input 
                            wire:model.live.debounce.300ms="searchSubject"
                            placeholder="Buscar materia o código..." 
                            iconLeft="heroicon-o-magnifying-glass" 
                            size="sm" />
                    </div>
                </div>

                {{-- Contenedor scrolleable de materias --}}
                <div class="flex-1 overflow-y-auto custom-scroll p-4 lg:p-6">
                    
                    {{-- Materias Básicas --}}
                    @if($subjects['basic']->isNotEmpty())
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-book-open class="w-4 h-4 text-slate-400" />
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                                    Materias Básicas / Académicas
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                @foreach($subjects['basic'] as $subject)
                                    <button wire:click="toggleSubject({{ $subject['id'] }})"
                                            class="relative p-4 rounded-2xl border-2 text-left transition-all hover:shadow-md active:scale-[0.98] group
                                                   {{ $subject['is_assigned']
                                                       ? 'border-transparent text-white shadow-sm ring-2 ring-offset-1 dark:ring-offset-dark-card'
                                                       : 'border-slate-200 dark:border-white/10 bg-white dark:bg-dark-card text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-white/20' }}"
                                            style="{{ $subject['is_assigned'] ? 'background-color: ' . $subject['color'] . '; ring-color: ' . $subject['color'] : '' }}">

                                        @if($subject['is_assigned'])
                                            <div class="absolute top-3 right-3 bg-white/20 rounded-full p-0.5">
                                                <x-heroicon-s-check class="w-4 h-4 text-white" />
                                            </div>
                                        @else
                                            <div class="w-2.5 h-2.5 rounded-full mb-3 shadow-sm" style="background-color: {{ $subject['color'] }}"></div>
                                        @endif

                                        <p class="text-sm font-bold leading-tight pr-6 {{ $subject['is_assigned'] ? 'mt-1' : '' }}">
                                            {{ $subject['name'] }}
                                        </p>
                                        <p class="text-[10px] font-mono opacity-70 mt-1">
                                            {{ $subject['code'] }}
                                        </p>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Módulos Técnicos --}}
                    @if($subjects['technical']->isNotEmpty())
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <x-heroicon-o-wrench-screwdriver class="w-4 h-4 text-slate-400" />
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                                    Módulos Técnicos
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                @foreach($subjects['technical'] as $subject)
                                    <button wire:click="toggleSubject({{ $subject['id'] }})"
                                            class="relative p-4 rounded-2xl border-2 text-left transition-all hover:shadow-md active:scale-[0.98] group
                                                   {{ $subject['is_assigned']
                                                       ? 'border-transparent text-white shadow-sm ring-2 ring-offset-1 dark:ring-offset-dark-card'
                                                       : 'border-dashed border-slate-200 dark:border-white/10 bg-white dark:bg-dark-card text-slate-600 dark:text-slate-400 hover:border-solid hover:border-slate-300 dark:hover:border-white/20' }}"
                                            style="{{ $subject['is_assigned'] ? 'background-color: ' . $subject['color'] . '; ring-color: ' . $subject['color'] : '' }}">

                                        @if($subject['is_assigned'])
                                            <div class="absolute top-3 right-3 bg-white/20 rounded-full p-0.5">
                                                <x-heroicon-s-check class="w-4 h-4 text-white" />
                                            </div>
                                        @else
                                            <div class="w-2.5 h-2.5 rounded-full mb-3 opacity-60" style="background-color: {{ $subject['color'] }}"></div>
                                        @endif

                                        <p class="text-sm font-bold leading-tight pr-6 {{ $subject['is_assigned'] ? 'mt-1' : '' }}">
                                            {{ $subject['name'] }}
                                        </p>
                                        <p class="text-[10px] font-mono opacity-70 mt-1">
                                            {{ $subject['code'] }}
                                        </p>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Empty State Global de Búsqueda/Materias --}}
                    @if($subjects['basic']->isEmpty() && $subjects['technical']->isEmpty())
                        <div class="flex flex-col items-center justify-center h-48 text-center mt-10">
                            <x-heroicon-o-document-magnifying-glass class="w-12 h-12 text-slate-300 mb-3" />
                            <p class="text-base font-bold text-slate-600 dark:text-slate-300">
                                No se encontraron materias
                            </p>
                            <p class="text-sm text-slate-400 mt-1 max-w-sm">
                                {{ $searchSubject ? 'Intenta usar otros términos de búsqueda o limpiar el filtro.' : 'Esta escuela no tiene materias registradas que coincidan con esta sección.' }}
                            </p>
                        </div>
                    @endif

                </div>
            @endif
        </div>
    </div>
</div>