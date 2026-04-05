<div>
    {{-- 1. TOOLBAR --}}
    <x-app.module-toolbar>
        <x-slot:actions>
            @can('teachers.create')
                <x-ui.button
                    variant="primary"
                    size="sm"
                    iconLeft="heroicon-s-plus"
                    :href="route('app.academic.teachers.create')"
                >
                    Nuevo Maestro
                </x-ui.button>
            @endcan
            
        </x-slot:actions>

        <x-slot:secondary>
            <x-ui.button variant="secondary" type="ghost" size="sm" iconLeft="heroicon-s-document-arrow-down">
                Exportar
            </x-ui.button>
        </x-slot:secondary>
    </x-app.module-toolbar>

    <div class="p-4 md:p-6 flex flex-col gap-6">

        <x-ui.page-header
            title="Maestros"
            description="Gestiona el personal docente, sus asignaciones y acceso al sistema."
            :count="$teachers->total()"
            countLabel="maestros"
        />

        {{-- 2. TABLA --}}
        <x-data-table.base-table
            :items="$teachers"
            :definition="\App\Tables\App\TeacherTableConfig::class"
            :visibleColumns="$visibleColumns"
            :activeChips="$this->getActiveChips()"
            :hasFilters="count(array_filter($filters)) > 0"
        >
            <x-slot:filterSlot>
                <x-data-table.filter-container :activeCount="count(array_filter($filters))">
                    <x-data-table.filter-group title="Estado y Contrato">
                        <x-data-table.filter-select
                            label="Estado"
                            filterKey="status"
                            :options="['1' => 'Activos', '0' => 'Inactivos']"
                            placeholder="Todos"
                        />
                        <x-data-table.filter-select
                            label="Tipo de Contrato"
                            filterKey="employment_type"
                            :options="['full_time' => 'Tiempo Completo', 'part_time' => 'Tiempo Parcial']"
                            placeholder="Todos los contratos"
                        />
                        <x-data-table.filter-toggle label="Con acceso al sistema" filterKey="has_user" />
                    </x-data-table.filter-group>
                </x-data-table.filter-container>
            </x-slot:filterSlot>

            @forelse($teachers as $teacher)
                <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors">

                    {{-- Nombre --}}
                    <x-data-table.cell column="full_name" :visible="$visibleColumns">
                        <a href="{{ route('app.academic.teachers.show', $teacher) }}"
                            class="text-sm font-bold text-orvian-orange hover:underline whitespace-nowrap">
                            {{ $teacher->full_name }}
                        </a>
                    </x-data-table.cell>

                    {{-- Código de empleado --}}
                    <x-data-table.cell column="employee_code" :visible="$visibleColumns">
                        <span class="text-xs font-mono text-slate-500">{{ $teacher->employee_code }}</span>
                    </x-data-table.cell>

                    {{-- Especialización --}}
                    <x-data-table.cell column="specialization" :visible="$visibleColumns">
                        <span class="text-sm text-slate-600 dark:text-slate-300">{{ $teacher->specialization ?? '—' }}</span>
                    </x-data-table.cell>

                    {{-- Tipo de contrato --}}
                    <x-data-table.cell column="employment_type" :visible="$visibleColumns">
                        @php
                            $badgeConfig = match($teacher->employment_type) {
                                'Full-Time'  => ['variant' => 'info',    'label' => 'T. Completo'],
                                'Part-Time'  => ['variant' => 'warning', 'label' => 'T. Parcial'],
                                'Substitute' => ['variant' => 'secondary', 'label' => 'Suplente'],
                                default      => ['variant' => 'ghost',   'label' => $teacher->employment_type],
                            };
                        @endphp

                        <x-ui.badge 
                            :variant="$badgeConfig['variant']" 
                            size="sm"
                        >
                            {{ $badgeConfig['label'] }}
                        </x-ui.badge>
                    </x-data-table.cell>
                    
                    {{-- Asignaciones --}}
                    <x-data-table.cell column="assignments_count" :visible="$visibleColumns">
                        <x-ui.badge variant="slate" size="sm">
                            {{ $teacher->assignments_count }} {{ Str::plural('materia', $teacher->assignments_count) }}
                        </x-ui.badge>
                    </x-data-table.cell>

                    {{-- Estado --}}
                    <x-data-table.cell column="status" :visible="$visibleColumns">
                        <x-ui.badge :variant="$teacher->is_active ? 'success' : 'error'" size="sm" dot>
                            {{ $teacher->is_active ? 'Activo' : 'Inactivo' }}
                        </x-ui.badge>
                    </x-data-table.cell>

                    {{-- Acceso al sistema --}}
                    <x-data-table.cell column="has_user_account" :visible="$visibleColumns">
                        @if($teacher->user_id)
                            <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                        @else
                            <x-heroicon-s-x-circle class="w-5 h-5 text-slate-300" />
                        @endif
                    </x-data-table.cell>

                    {{-- Acciones --}}
                    <td class="px-4 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-1">
                            {{-- Gestionar Asignaciones --}}
                            @can('teachers.assign_subjects')
                                <x-ui.button
                                    variant="info"
                                    type="ghost"
                                    size="sm"
                                    icon="heroicon-o-academic-cap"
                                    :href="route('app.academic.teachers.assignments', $teacher)"
                                    title="Gestionar Asignaciones"
                                />
                            @endcan

                            {{-- Editar --}}
                            @can('teachers.edit')
                                <x-ui.button
                                    variant="secondary"
                                    type="ghost"
                                    size="sm"
                                    icon="heroicon-o-pencil-square"
                                    :href="route('app.academic.teachers.edit', $teacher)"
                                />
                            @endcan

                            {{-- Dar de baja / Reactivar --}}
                            @can('teachers.edit')
                                @if($teacher->is_active)
                                    <x-ui.button
                                        variant="error"
                                        type="ghost"
                                        size="sm"
                                        icon="heroicon-o-user-minus"
                                        wire:click="confirmTerminate({{ $teacher->id }})"
                                        title="Dar de baja"
                                    />
                                @else
                                    <x-ui.button
                                        variant="success"
                                        type="ghost"
                                        size="sm"
                                        icon="heroicon-o-user-plus"
                                        wire:click="confirmReactivate({{ $teacher->id }})"
                                        title="Reactivar"
                                    />
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-12">
                        <x-ui.empty-state
                            variant="simple"
                            title="No hay maestros registrados"
                            description="Intenta ajustar los filtros o registra un nuevo maestro."
                        />
                    </td>
                </tr>
            @endforelse
        </x-data-table.base-table>

        {{-- MODAL DE BAJA --}}
        <x-modal name="terminate-modal" :show="$showTerminateModal" x-on:close="$wire.closeModals()" maxWidth="md">
            <form wire:submit.prevent="terminate" class="p-6">
                <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Dar de Baja al Maestro</h2>

                @if($this->selectedTeacher)
                    <p class="text-sm text-slate-600 dark:text-slate-300 mb-6">
                        Estás a punto de dar de baja a
                        <span class="font-bold">{{ $this->selectedTeacher->full_name }}</span>.
                        Sus asignaciones activas serán conservadas pero desactivadas.
                    </p>
                @endif

                <div class="space-y-6">
                    <x-ui.forms.input
                        label="Fecha de Baja"
                        name="termination_date"
                        type="date"
                        iconLeft="heroicon-o-calendar"
                        wire:model="termination_date"
                        :error="$errors->first('termination_date')"
                        required
                    />
                    <x-ui.forms.textarea
                        label="Motivo de la Baja"
                        name="termination_reason"
                        placeholder="Indique la razón (ej. Renuncia voluntaria, fin de contrato...)"
                        wire:model="termination_reason"
                        :error="$errors->first('termination_reason')"
                        hint="Este motivo quedará registrado en el historial del maestro."
                    />
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        type="ghost"
                        x-on:click="$dispatch('close-modal', 'terminate-modal')"
                    >
                        Cancelar
                    </x-ui.button>
                    <x-ui.button
                        variant="error"
                        wire:click="terminate"
                        wire:loading.attr="disabled"
                    >
                        Confirmar Baja
                    </x-ui.button>
                </div>
            </form>
        </x-modal>

        {{-- MODAL DE REACTIVACIÓN --}}
        <x-modal name="reactivate-teacher-modal" :show="$showReactivateModal" x-on:close="$wire.closeModals()" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Reactivar Maestro</h2>
                @if($this->selectedTeacher)
                    <p class="text-sm text-slate-600 dark:text-slate-300 mb-2">
                        ¿Confirmas la reactivación de
                        <span class="font-bold">{{ $this->selectedTeacher->full_name }}</span>?
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">
                        Su registro de baja anterior será limpiado. Verifica que sus asignaciones de materia estén actualizadas.
                    </p>
                @endif
                <div class="mt-6 flex justify-end gap-3">
                    <x-ui.button wire:click="closeModals" variant="slate">Cancelar</x-ui.button>
                    <x-ui.button wire:click="reactivate" variant="success">Confirmar y Reactivar</x-ui.button>
                </div>
            </div>
        </x-modal>

    </div>
</div>