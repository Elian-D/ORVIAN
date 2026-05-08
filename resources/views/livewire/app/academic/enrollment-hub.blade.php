<div>
    <x-app.module-toolbar>
        <x-slot:title>Hub de Matriculación</x-slot:title>
        <x-slot:actions>
            @if($this->unassignedStudents->total() > 0)
                <x-ui.badge variant="warning" size="sm">
                    {{ $this->unassignedStudents->total() }} en Sala de Espera
                </x-ui.badge>
            @endif
        </x-slot:actions>
    </x-app.module-toolbar>

    {{-- Contenedor Principal: flex-col en móviles, flex-row en lg. Altura auto en móvil, fija en escritorio --}}
    <div class="flex flex-col lg:flex-row gap-6 h-auto lg:h-[calc(100vh-9rem)]">

        {{-- ══ PANEL IZQUIERDO: Sala de Espera ══ --}}
        {{-- w-full en móvil, w-1/2 en lg. Altura fija en móvil para que el scroll interno funcione --}}
        <div class="w-full lg:w-1/2 flex flex-col bg-white dark:bg-dark-card rounded-2xl border
                    border-slate-200 dark:border-white/10 overflow-hidden h-[500px] lg:h-full">

            {{-- Header del panel --}}
            <div class="p-4 border-b border-slate-200 dark:border-white/10 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700 dark:text-white">
                        Estudiantes Sin Asignar
                    </h3>
                    <x-ui.forms.checkbox
                        label="Seleccionar todos"
                        name="selectAll"
                        wire:model.live="selectAll" />
                </div>

                {{-- Buscador --}}
                <x-ui.forms.input
                    wire:model.live.debounce.300ms="searchUnassigned"
                    placeholder="Buscar por nombre o cédula..."
                    iconLeft="heroicon-o-magnifying-glass"
                    size="sm" />

                {{-- Filtros rápidos por sigerd_section --}}
                @if($this->sigerdSectionGroups->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        <button wire:click="$set('filterSigerdSection', '')"
                                class="px-2 py-1 rounded-lg text-[10px] font-bold transition-all
                                       {{ empty($filterSigerdSection)
                                           ? 'bg-orvian-orange text-white'
                                           : 'bg-slate-100 dark:bg-white/8 text-slate-500 hover:bg-slate-200' }}">
                            Todos
                        </button>
                        @foreach($this->sigerdSectionGroups as $group)
                            <button
                                wire:click="$set('filterSigerdSection', '{{ $group->sigerd_section }}')"
                                class="px-2 py-1 rounded-lg text-[10px] font-bold transition-all
                                       {{ $filterSigerdSection === $group->sigerd_section
                                           ? 'bg-orvian-orange text-white'
                                           : 'bg-slate-100 dark:bg-white/8 text-slate-500 hover:bg-slate-200' }}">
                                {{ $group->sigerd_section }}
                                <span class="ml-1 opacity-70">({{ $group->total }})</span>
                            </button>
                        @endforeach
                    </div>

                    @if($filterSigerdSection)
                        <button wire:click="selectBySigerdSection('{{ $filterSigerdSection }}')"
                                class="text-xs text-orvian-orange hover:underline font-semibold">
                            + Seleccionar todos los de "{{ $filterSigerdSection }}"
                        </button>
                    @endif
                @endif
            </div>

            {{-- Lista de estudiantes --}}
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-white/5">
                @forelse($this->unassignedStudents as $student)
                {{-- Eliminamos las clases de cambio de color de texto y simplificamos el hover --}}
                <label class="group flex items-center gap-3 p-3 cursor-pointer transition-colors
                            hover:bg-slate-50 dark:hover:bg-slate-800/50
                            {{ in_array($student->id, $selectedStudentIds)
                                ? 'bg-orvian-orange/5 dark:bg-orvian-orange/10'
                                : '' }}">
                    
                    <input type="checkbox"
                        wire:click="toggleStudent({{ $student->id }})"
                        @checked(in_array($student->id, $selectedStudentIds))
                        class="rounded border-slate-300 text-orvian-orange focus:ring-orvian-orange" />

                    <x-ui.student-avatar :student="$student" size="sm" />

                    <div class="flex-1 min-w-0">
                        {{-- Texto se queda blanco en dark mode, el fondo hover ahora es slate-800/50 --}}
                        <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                            {{ $student->full_name }}
                        </p>
                        
                        <div class="flex items-center gap-2">
                            @if($student->rnc)
                                <span class="text-[10px] text-slate-400 font-mono">
                                    {{ $student->rnc }}
                                </span>
                            @endif
                            
                            @if($student->metadata['sigerd_section'] ?? null)
                                <x-ui.badge variant="slate" size="xs">
                                    {{ $student->metadata['sigerd_section'] }}
                                </x-ui.badge>
                            @endif
                        </div>
                    </div>
                </label>
                @empty
                    <div class="flex flex-col items-center justify-center h-48 text-center p-6">
                        <x-heroicon-o-check-circle class="w-10 h-10 text-green-400 mb-2" />
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                            ¡Sala de Espera vacía!
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            Todos los estudiantes tienen sección asignada.
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Paginación --}}
            @if($this->unassignedStudents->hasPages())
                <div class="p-3 border-t border-slate-200 dark:border-white/10">
                    {{ $this->unassignedStudents->links('pagination.orvian-compact') }}
                </div>
            @endif
        </div>

        {{-- ══ PANEL DERECHO: Árbol de Secciones ══ --}}
        <div class="w-full lg:w-1/2 flex flex-col bg-white dark:bg-dark-card rounded-2xl border
                    border-slate-200 dark:border-white/10 overflow-hidden h-[500px] lg:h-full mt-6 lg:mt-0">

            {{-- Header del panel --}}
            <div class="p-4 border-b border-slate-200 dark:border-white/10 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700 dark:text-white">
                        Seleccionar Destino
                    </h3>
                    @if(count($selectedStudentIds) > 0)
                        <x-ui.badge variant="info" size="sm">
                            {{ count($selectedStudentIds) }} seleccionados
                        </x-ui.badge>
                    @endif
                </div>

                {{-- Filtro por tanda --}}
                <div class="flex flex-wrap gap-1.5">
                    <button wire:click="$set('targetShiftId', null)"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all
                                   {{ is_null($targetShiftId)
                                       ? 'bg-orvian-orange text-white'
                                       : 'bg-slate-100 dark:bg-white/8 text-slate-500' }}">
                        Todas
                    </button>
                    @foreach($this->shifts as $shift)
                        <button wire:click="$set('targetShiftId', {{ $shift->id }})"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all
                                       {{ $targetShiftId === $shift->id
                                           ? 'bg-orvian-orange text-white'
                                           : 'bg-slate-100 dark:bg-white/8 text-slate-500' }}">
                            {{ $shift->type }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Árbol de secciones (selección por click) --}}
            <div class="flex-1 overflow-y-auto p-4 lg:p-5 space-y-8 custom-scrollbar">
                @foreach($this->sectionTree as $levelName => $sections)
                    <div>
                        {{-- Encabezado de Nivel --}}
                        <div class="flex items-center gap-3 mb-4">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                                {{ $levelName }}
                            </span>
                            <div class="h-px flex-1 bg-slate-100 dark:bg-white/5"></div>
                        </div>

                        {{-- Responsividad en la grilla: 1 columna en móvil pequeño, 2 en sm, 3 en lg --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                            @foreach($sections->sortBy(fn ($s) => $s->grade->name . $s->label) as $section)
                                <button
                                    wire:click="$set('targetSectionId', {{ $section->id }})"
                                    @class([
                                        'relative p-4 rounded-2xl border-2 text-left transition-all group',
                                        'border-orvian-orange bg-orvian-orange/[0.03] ring-4 ring-orvian-orange/10' => $targetSectionId === $section->id,
                                        'border-slate-100 dark:border-white/5 bg-white dark:bg-white/[0.02] hover:border-slate-300 dark:hover:border-white/20' => $targetSectionId !== $section->id,
                                    ])>
                                    
                                    @if($targetSectionId === $section->id)
                                        <div class="absolute -top-2 -right-2 bg-orvian-orange text-white rounded-full p-1 shadow-lg ring-2 ring-white dark:ring-dark-card">
                                            <x-heroicon-s-check class="w-3 h-3" />
                                        </div>
                                    @endif

                                    <div class="flex justify-between items-start mb-1">
                                        <span class="text-xl font-black text-slate-800 dark:text-white group-hover:text-orvian-orange transition-colors">
                                            {{ $section->label }}
                                        </span>
                                        <div class="text-right">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Estudiantes</span>
                                            <span class="text-xs font-black text-slate-600 dark:text-slate-300">
                                                {{ $section->students_count ?? 0 }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">
                                        {{ $section->grade->name }}
                                    </div>

                                    @if($section->technical_title_id)
                                        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-white/5">
                                            <div class="flex items-center gap-1.5 text-orvian-orange">
                                                <x-heroicon-s-academic-cap class="w-3 h-3" />
                                                <span class="text-[9px] font-black uppercase tracking-tight leading-none truncate" title="{{ $section->technicalTitle->short_name ?? $section->technicalTitle->name }}">
                                                    {{ $section->technicalTitle->short_name ?? $section->technicalTitle->name }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif

                                    @if(!$targetShiftId)
                                        <div class="mt-1 text-[9px] font-medium text-slate-400 italic">
                                            Tanda {{ $section->shift->type ?? 'N/A' }}
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Botón de asignación --}}
            <div class="p-4 border-t border-slate-200 dark:border-white/10">
                <x-ui.button
                    wire:click="confirmAssign"
                    variant="primary"
                    :fullWidth="true"
                    :disabled="empty($selectedStudentIds) || !$targetSectionId">
                    Asignar {{ count($selectedStudentIds) > 0 ? count($selectedStudentIds) . ' estudiante(s)' : '' }}
                    {{ $this->targetSection ? 'a ' . $this->targetSection->full_label : '' }}
                </x-ui.button>
            </div>
        </div>
    </div>

    {{-- Modal de confirmación --}}
    <x-modal name="confirm-assign" maxWidth="sm">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-orvian-orange/10 text-orvian-orange rounded-lg">
                    <x-heroicon-s-user-group class="w-5 h-5" />
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-white">
                    Confirmar Asignación Masiva
                </h3>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Estás a punto de asignar
                <strong class="text-slate-800 dark:text-white">{{ count($selectedStudentIds) }} estudiante(s)</strong>
                a la sección seleccionada. Esta acción se puede revertir editando cada estudiante.
            </p>

            <div class="flex gap-3 justify-end mt-6">
                <x-ui.button
                    x-on:click="$dispatch('close-modal', 'confirm-assign')"
                    variant="secondary"
                    type="ghost"
                    size="sm">
                    Cancelar
                </x-ui.button>
                <x-ui.button
                    wire:click="executeAssignment"
                    variant="primary"
                    size="sm"
                    wire:loading.attr="disabled"
                    wire:target="executeAssignment">
                    <span wire:loading.remove wire:target="executeAssignment">Confirmar</span>
                    <span wire:loading wire:target="executeAssignment">Asignando...</span>
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>