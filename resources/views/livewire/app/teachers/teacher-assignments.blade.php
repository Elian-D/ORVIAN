<div>
    <x-app.module-toolbar>
        <x-slot:actions>
            <x-ui.button
                variant="secondary"
                size="sm"
                iconLeft="heroicon-s-arrow-left"
                :href="route('app.academic.teachers.show', $teacher)"
            >
                Volver al Perfil
            </x-ui.button>
        </x-slot:actions>
    </x-app.module-toolbar>

    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header
            title="Asignaciones de {{ $teacher->full_name }}"
            description="Gestiona las materias y secciones asignadas al maestro para el año escolar activo."
        />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- ── PANEL IZQUIERDO: Asignaciones actuales ────────────────── --}}
            <div class="bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-gray-800 p-5 space-y-4">
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide">
                    Asignaciones Activas
                </h3>

                @forelse($this->currentAssignments as $sectionId => $assignments)
                    @php $section = $assignments->first()->section; @endphp
                    <div class="space-y-2">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                            {{ $section->full_label ?? 'Sección' }}
                        </p>
                        @foreach($assignments as $assignment)
                            <div class="flex items-center justify-between py-2 px-3 bg-slate-50 dark:bg-dark-bg rounded-xl">
                                <div class="flex items-center gap-2">
                                    {{-- Dot de color de la materia --}}
                                    <span
                                        class="w-3 h-3 rounded-full flex-shrink-0"
                                        style="background-color: {{ $assignment->subject->color ?? '#64748B' }}"
                                    ></span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ $assignment->subject->name }}
                                    </span>
                                </div>
                                <x-ui.button
                                    variant="error"
                                    type="ghost"
                                    size="sm"
                                    icon="heroicon-o-trash"
                                    {{-- Guardamos el ID a eliminar y abrimos el modal --}}
                                    wire:click="$set('assignmentToDelete', {{ $assignment->id }})"
                                    x-on:click="$dispatch('open-modal', 'confirm-assignment-deletion')"
                                    title="Eliminar asignación"
                                />
                            </div>
                        @endforeach
                    </div>
                @empty
                    <x-ui.empty-state
                        variant="simple"
                        icon="heroicon-o-academic-cap"
                        title="Sin asignaciones"
                        description="Este maestro aún no tiene materias asignadas para el año activo."
                    />
                @endforelse
            </div>

            {{-- ── PANEL DERECHO: Agregar nueva asignación ───────────────── --}}
            <div class="bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-gray-800 p-5 space-y-5">
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide">
                    Nueva Asignación
                </h3>

                {{-- Selector de Sección --}}
                <x-ui.forms.select
                    label="Sección / Grado"
                    wire:model.live="selectedSectionId"
                    :error="$errors->first('selectedSectionId')"
                >
                    <option value="0">Selecciona una sección...</option>
                    @foreach($this->sections as $section)
                        <option value="{{ $section->id }}">{{ $section->full_label }}</option>
                    @endforeach
                </x-ui.forms.select>

                {{-- Selector de Materia (filtrado reactivamente por sección) --}}
                    <x-ui.forms.select
                        label="Materia / Módulo"
                        wire:model.live="selectedSubjectId" {{-- CRÍTICO: Agregar .live aquí --}}
                        :disabled="!$selectedSectionId"
                        :error="$errors->first('selectedSubjectId')"
                    >
                    <option value="0">
                        {{ $selectedSectionId ? 'Selecciona una materia...' : 'Primero selecciona una sección' }}
                    </option>
                    @foreach($this->availableSubjects as $subject)
                        <option value="{{ $subject->id }}">
                            {{ $subject->code }} — {{ $subject->name }}
                        </option>
                    @endforeach
                </x-ui.forms.select>

                @if($selectedSectionId && $this->availableSubjects->isEmpty())
                    <p class="text-xs text-amber-600 dark:text-amber-400">
                        ⚠ Todas las materias disponibles para esta sección ya están asignadas.
                    </p>
                @endif

                <div class="pt-2">
                    <x-ui.button
                        variant="primary"
                        wire:click="assign"
                        wire:loading.attr="disabled"
                        :disabled="!$selectedSubjectId || !$selectedSectionId"
                        class="w-full"
                    >
                        <span wire:loading.remove wire:target="assign">Asignar Materia</span>
                        <span wire:loading wire:target="assign">Asignando...</span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
    {{-- ... (Resto de tu vista) ... --}}
    
    {{-- ── MODAL DE CONFIRMACIÓN DE ELIMINACIÓN ────────────────── --}}
    <x-modal name="confirm-assignment-deletion" maxWidth="sm">
        <div class="p-6">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200">
                Confirmar Eliminación
            </h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                ¿Estás seguro de que deseas eliminar esta asignación? Si la materia ya tiene registros de asistencia vinculados, la asignación solo se desactivará de forma segura.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button
                    variant="secondary"
                    type="outline"
                    x-on:click="$dispatch('close-modal', 'confirm-assignment-deletion')"
                >
                    Cancelar
                </x-ui.button>

                <x-ui.button
                    variant="error"
                    wire:click="removeConfirmed"
                    wire:loading.attr="disabled"
                    wire:target="removeConfirmed"
                >
                    <span wire:loading.remove wire:target="removeConfirmed">Sí, eliminar</span>
                    <span wire:loading wire:target="removeConfirmed">Procesando...</span>
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>
</div>