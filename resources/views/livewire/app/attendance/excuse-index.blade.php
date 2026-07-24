<div class="p-4 md:p-6 flex flex-col gap-4">
    
    {{-- Page Header --}}
    <x-ui.page-header
        title="Gestión de Excusas"
        description="Administra las justificaciones de ausencias y tardanzas de los estudiantes"
        :count="$excuses->total()"
        countLabel="excusas"
    >
        <x-slot:actions>
            @can('manage_excuses')
                <x-ui.button
                    variant="primary"
                    size="sm"
                    iconLeft="heroicon-s-plus"
                    href="{{ route('app.attendance.excuses.create') }}"
                >
                    Registrar Excusa
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Data Table --}}
    <x-data-table.base-table
        :items="$excuses"
        :definition="\App\Tables\App\Attendance\ExcuseTableConfig::class"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="count(array_filter($filters)) > 0"
    >
        {{-- Slot de Filtros --}}
        <x-slot:filterSlot>
            <x-data-table.filter-container
                :activeCount="count(array_filter($filters))"
            >
                <x-data-table.filter-select
                    label="Estado"
                    filterKey="status"
                    :options="[
                        'pending'   => 'Pendientes',
                        'confirmed' => 'Confirmadas',
                        'cancelled' => 'Canceladas',
                    ]"
                    placeholder="Todos los estados"
                />

                {{-- Filtro de Rango de Fechas --}}
        {{-- Importante: Usamos la notación de punto para que Livewire cree el array interno --}}
        <x-data-table.filter-date-range
            label="Rango de Fechas"
            fromKey="date_range.from"
            toKey="date_range.to"
        />
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        {{-- Filas --}}
        @forelse($excuses as $excuse)
            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors duration-150">
                
                {{-- Columna: Estudiante --}}
                <x-data-table.cell column="student" :visible="$visibleColumns">
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="$excuse->student->first_name" size="sm" class="rounded-lg" />
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-white leading-none">
                                {{ $excuse->student->first_name }} {{ $excuse->student->last_name }}
                            </p>
                            <p class="text-[11px] text-slate-500 mt-1">
                                {{ $excuse->student->full_section_name }}
                            </p>
                        </div>
                    </div>
                </x-data-table.cell>

                {{-- Columna: Rango de Fechas --}}
                <x-data-table.cell column="date_range" :visible="$visibleColumns">
                    <div class="text-sm text-slate-600 dark:text-slate-300">
                        {{ \Carbon\Carbon::parse($excuse->date_start)->format('d/m/Y') }}
                        @if($excuse->date_start !== $excuse->date_end)
                            <span class="text-slate-400 mx-1">al</span>
                            {{ \Carbon\Carbon::parse($excuse->date_end)->format('d/m/Y') }}
                        @endif
                    </div>
                </x-data-table.cell>

                {{-- Columna: Tipo --}}
                <x-data-table.cell column="type" :visible="$visibleColumns">
                    @php
                        $types = [
                            \App\Models\Tenant\AttendanceExcuse::TYPE_MEDICAL                => ['label' => 'Médico', 'color' => 'error'],
                            \App\Models\Tenant\AttendanceExcuse::TYPE_PERSONAL               => ['label' => 'Personal', 'color' => 'warning'],
                        ];
                        $typeData = $types[$excuse->type] ?? ['label' => 'Desconocido', 'color' => 'slate'];
                    @endphp
                    <x-ui.badge variant="{{ $typeData['color'] }}" :dot="false" size="sm">
                        {{ $typeData['label'] }}
                    </x-ui.badge>
                </x-data-table.cell>

                {{-- Columna: Estado --}}
                <x-data-table.cell column="status" :visible="$visibleColumns">
                    @if($excuse->status === 'confirmed')
                        <x-ui.badge variant="success" size="sm">Confirmada</x-ui.badge>
                    @elseif($excuse->status === 'cancelled')
                        <x-ui.badge variant="error" size="sm">Cancelada</x-ui.badge>
                    @else
                        <x-ui.badge variant="warning" size="sm">Pendiente</x-ui.badge>
                    @endif
                </x-data-table.cell>

                {{-- Columna: Registrado por --}}
                <x-data-table.cell column="submitted" :visible="$visibleColumns">
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        {{ $excuse->submittedBy->name ?? 'Sistema' }}
                    </span>
                </x-data-table.cell>

                {{-- Columna: Fecha Registro --}}
                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        {{ $excuse->created_at->format('d/m/Y g:i A') }}
                    </span>
                </x-data-table.cell>

                {{-- Acciones --}}
                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-2">
                        @if($excuse->attachment_path)
                            <x-ui.button 
                                variant="secondary" 
                                type="ghost" 
                                size="sm"
                                icon="heroicon-o-paper-clip"
                                href="{{ Storage::url($excuse->attachment_path) }}"
                                target="_blank"
                                title="Ver documento adjunto"
                            />
                        @endif

                        @if($excuse->status === 'pending')
                            @can('manage_excuses')
                                <x-ui.button
                                    variant="secondary"
                                    type="ghost"
                                    size="sm"
                                    icon="heroicon-o-pencil-square"
                                    href="{{ route('app.attendance.excuses.edit', $excuse) }}"
                                    title="Editar Excusa"
                                />
                                <x-ui.button
                                    variant="success"
                                    type="ghost"
                                    size="sm"
                                    icon="heroicon-o-check-circle"
                                    wire:click="openConfirm({{ $excuse->id }})"
                                    title="Confirmar Excusa"
                                />
                            @endcan
                        @elseif($excuse->status === 'confirmed')
                            @can('manage_excuses')
                                <x-ui.button
                                    variant="error"
                                    type="ghost"
                                    size="sm"
                                    icon="heroicon-o-x-circle"
                                    wire:click="openCancel({{ $excuse->id }})"
                                    title="Cancelar Excusa"
                                />
                            @endcan
                        @else
                            <x-ui.button
                                variant="secondary"
                                type="ghost"
                                size="sm"
                                icon="heroicon-o-eye"
                                title="Ver detalles"
                            />
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="100%" class="px-6 py-12">
                    <x-ui.empty-state
                        variant="simple"
                        title="No hay excusas registradas"
                        description="Cuando los estudiantes o el staff registren excusas, aparecerán aquí."
                    />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>

    {{-- ========================================== --}}
    {{-- MODAL DE CONFIRMACIÓN (pending → confirmed) --}}
    {{-- Resumen completo como segundo factor antes de confirmar --}}
    {{-- ========================================== --}}
    <x-modal wire:model="showConfirmModal" name="confirm-excuse" maxWidth="md">
        <div class="px-6 py-5 bg-white dark:bg-dark-card">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-green-100 dark:bg-green-500/10 text-green-600 dark:text-green-400">
                    <x-heroicon-s-check-circle class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight">
                        ¿Confirmar esta excusa?
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Revisa el resumen antes de confirmar — luego solo se puede cancelar, no editar.
                    </p>
                </div>
            </div>

            @if ($this->selectedExcuse)
                @php($excuseToConfirm = $this->selectedExcuse)
                <div class="rounded-2xl border border-slate-100 dark:border-dark-border p-4 space-y-4">
                    {{-- Estudiante (con foto — evita confirmar sobre el estudiante equivocado) --}}
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-xl overflow-hidden ring-2 ring-orvian-orange/10 bg-slate-100 dark:bg-dark-bg flex-shrink-0">
                            @if ($excuseToConfirm->student->photo_path)
                                <img
                                    src="{{ asset('storage/' . $excuseToConfirm->student->photo_path) }}"
                                    alt="{{ $excuseToConfirm->student->first_name }} {{ $excuseToConfirm->student->last_name }}"
                                    class="w-full h-full object-cover"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-lg font-black text-slate-300 dark:text-slate-600 uppercase">
                                    {{ Illuminate\Support\Str::substr($excuseToConfirm->student->first_name, 0, 1) }}{{ Illuminate\Support\Str::substr($excuseToConfirm->student->last_name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-white leading-none">
                                {{ $excuseToConfirm->student->first_name }} {{ $excuseToConfirm->student->last_name }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ $excuseToConfirm->student->full_section_name }}
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 dark:border-dark-border"></div>

                    {{-- Tipo y Fechas --}}
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Motivo</p>
                            <x-ui.badge :variant="$excuseToConfirm->type === \App\Models\Tenant\AttendanceExcuse::TYPE_MEDICAL ? 'error' : 'warning'" :dot="false" size="sm">
                                {{ $excuseToConfirm->type_label }}
                            </x-ui.badge>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Rango de Fechas</p>
                            <p class="text-slate-700 dark:text-slate-300">
                                {{ \Carbon\Carbon::parse($excuseToConfirm->date_start)->format('d/m/Y') }}
                                @if($excuseToConfirm->date_start !== $excuseToConfirm->date_end)
                                    al {{ \Carbon\Carbon::parse($excuseToConfirm->date_end)->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Documento (si aplica) --}}
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Documento Adjunto</p>
                        @if ($excuseToConfirm->attachment_path)
                            <a href="{{ Storage::url($excuseToConfirm->attachment_path) }}" target="_blank" class="text-sm text-orvian-orange underline">
                                Ver documento
                            </a>
                        @else
                            <p class="text-sm text-slate-400">Sin documento adjunto</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-white/5">
                <x-ui.button
                    variant="secondary"
                    size="sm"
                    wire:click="closeConfirm('')"
                >
                    Volver
                </x-ui.button>
                <x-ui.button
                    variant="success"
                    size="sm"
                    wire:click="confirm"
                    wire:loading.attr="disabled"
                    iconLeft="heroicon-s-check-circle"
                >
                    Sí, confirmar
                </x-ui.button>
            </div>
        </div>
    </x-modal>

    {{-- ========================================== --}}
    {{-- MODAL DE CANCELACIÓN (confirmed → cancelled) --}}
    {{-- ========================================== --}}
    <x-modal wire:model="showCancelModal" name="cancel-excuse" maxWidth="md">
        <div class="px-6 py-5 bg-white dark:bg-dark-card">
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400">
                        <x-heroicon-s-x-circle class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight">
                            Cancelar Excusa
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            La excusa ya confirmada quedará cancelada. Los registros de asistencia ya creados no se revierten.
                        </p>
                    </div>
                </div>

                {{-- Motivo de la Cancelación --}}
                <x-ui.forms.textarea
                    label="Motivo de la cancelación"
                    name="cancelNotes"
                    placeholder="Explica por qué se cancela esta excusa..."
                    wire:model="cancelNotes"
                    :error="$errors->first('cancelNotes')"
                    :rows="3"
                    required
                />
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-white/5">
                <x-ui.button
                    variant="secondary"
                    size="sm"
                    wire:click="closeCancel('')"
                >
                    Volver
                </x-ui.button>
                <x-ui.button
                    variant="error"
                    size="sm"
                    wire:click="cancel"
                    wire:loading.attr="disabled"
                >
                    Cancelar Excusa
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>