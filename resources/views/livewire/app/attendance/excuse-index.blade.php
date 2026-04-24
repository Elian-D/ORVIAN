<div class="p-4 md:p-6 flex flex-col gap-4">
    
    {{-- Page Header --}}
    <x-ui.page-header
        title="Gestión de Excusas"
        description="Administra las justificaciones de ausencias y tardanzas de los estudiantes"
        :count="$excuses->total()"
        countLabel="excusas"
    >
        <x-slot:actions>
            @can('excuses.submit')
                <x-ui.button
                    variant="primary"
                    size="sm"
                    iconLeft="heroicon-s-plus"
                    {{-- Disparamos el evento directamente o vía Livewire --}}
                    x-on:click="$dispatch('open-modal', 'register-excuse')"
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
                        'pending'  => 'Pendientes',
                        'approved' => 'Aprobadas',
                        'rejected' => 'Rechazadas',
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
                                ID: {{ $excuse->student->rnc ?? 'N/A' }}
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
                            'full_absence'    => ['label' => 'Ausencia', 'color' => 'red'],
                            'late_arrival'    => ['label' => 'Tardanza', 'color' => 'amber'],
                            'early_departure' => ['label' => 'Salida Temp.', 'color' => 'blue'],
                        ];
                        $typeData = $types[$excuse->type] ?? ['label' => 'Desconocido', 'color' => 'slate'];
                    @endphp
                    <x-ui.badge variant="{{ $typeData['color'] }}" :dot="false" size="sm">
                        {{ $typeData['label'] }}
                    </x-ui.badge>
                </x-data-table.cell>

                {{-- Columna: Estado --}}
                <x-data-table.cell column="status" :visible="$visibleColumns">
                    @if($excuse->status === 'approved')
                        <x-ui.badge variant="success" size="sm">Aprobada</x-ui.badge>
                    @elseif($excuse->status === 'rejected')
                        <x-ui.badge variant="error" size="sm">Rechazada</x-ui.badge>
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
                            @can('excuses.approve')
                                <x-ui.button 
                                    variant="success" 
                                    type="ghost" 
                                    size="sm"
                                    icon="heroicon-o-check-circle"
                                    wire:click="openReview({{ $excuse->id }}, 'approve')" 
                                    title="Aprobar Excusa"
                                />
                            @endcan
                            @can('excuses.reject')
                                <x-ui.button 
                                    variant="error" 
                                    type="ghost" 
                                    size="sm"
                                    icon="heroicon-o-x-circle"
                                    wire:click="openReview({{ $excuse->id }}, 'reject')" 
                                    title="Rechazar Excusa"
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
    {{-- SLIDE-OVER PANEL: CREAR EXCUSA             --}}
    {{-- ========================================== --}}

    <x-ui.slide-over 
        name="register-excuse" 
        title="Registrar Excusa" 
        maxWidth="md"
    >
        <form wire:submit.prevent="submit" class="space-y-6">
            
            {{-- Selección de Estudiante --}}
            <x-ui.forms.select
                label="Estudiante"
                name="studentId"
                iconLeft="heroicon-o-user"
                wire:model="studentId"
                :error="$errors->first('studentId')"
                required
            >
                <option value="">Selecciona un estudiante...</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }}</option>
                @endforeach
            </x-ui.forms.select>

            {{-- Tipo de Justificación --}}
            <x-ui.forms.select
                label="Tipo de Justificación"
                name="type"
                iconLeft="heroicon-o-tag"
                wire:model="type"
                :error="$errors->first('type')"
                required
            >
                <option value="full_absence">Ausencia Completa</option>
                <option value="late_arrival">Tardanza</option>
                <option value="early_departure">Salida Temprana</option>
            </x-ui.forms.select>

            {{-- Rango de Fechas --}}
            <div class="grid grid-cols-2 gap-4">
                <x-ui.forms.input 
                    type="date"
                    label="Fecha de Inicio" 
                    name="dateStart"
                    wire:model="dateStart" 
                    :error="$errors->first('dateStart')"
                    required 
                />
                <x-ui.forms.input 
                    type="date"
                    label="Fecha de Fin" 
                    name="dateEnd"
                    wire:model="dateEnd" 
                    :error="$errors->first('dateEnd')"
                    required 
                />
            </div>

            {{-- Motivo Detallado --}}
            <x-ui.forms.textarea
                label="Motivo detallado"
                name="reason"
                placeholder="Explique brevemente la razón de la ausencia..."
                wire:model="reason"
                :error="$errors->first('reason')"
                :rows="4"
                required
            />

            {{-- Archivo Adjunto (Nuevo Componente FileInput) --}}
            <x-ui.forms.file-input
                label="Archivo Adjunto"
                name="attachment"
                wire:model="attachment"
                :error="$errors->first('attachment')"
                accept=".pdf,.jpg,.jpeg,.png"
                hint="Certificados médicos o notas de padres (Max: 5MB)."
            />

        </form>

        {{-- Slot de Footer --}}
        <x-slot:footer>
            <div class="flex gap-3">
                <x-ui.button 
                    variant="secondary" 
                    class="flex-1" 
                    x-on:click="show = false"
                >
                    Cancelar
                </x-ui.button>
                
                <x-ui.button 
                    variant="primary" 
                    class="flex-1" 
                    wire:click="submit"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="submit">Guardar Excusa</span>
                    <span wire:loading wire:target="submit">Procesando...</span>
                </x-ui.button>
            </div>
        </x-slot:footer>
    </x-ui.slide-over>
    
    {{-- ========================================== --}}
    {{-- MODAL DE REVISIÓN (APROBAR / RECHAZAR)     --}}
    {{-- ========================================== --}}
    <x-modal wire:model="showReview" name="review-excuse" maxWidth="md">
        <div class="px-6 py-5 bg-white dark:bg-dark-card">
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div @class([
                        'flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center',
                        'bg-green-100 dark:bg-green-500/10 text-green-600 dark:text-green-400' => $reviewAction === 'approve',
                        'bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400' => $reviewAction === 'reject',
                    ])>
                        @if($reviewAction === 'approve')
                            <x-heroicon-s-check-circle class="w-5 h-5" />
                        @else
                            <x-heroicon-s-x-circle class="w-5 h-5" />
                        @endif
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-white leading-tight">
                            {{ $reviewAction === 'approve' ? 'Aprobar Excusa' : 'Rechazar Excusa' }}
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $reviewAction === 'approve' ? 'Se justificará la asistencia de forma automática.' : 'La excusa será denegada. Es obligatorio dejar una nota.' }}
                        </p>
                    </div>
                </div>

                {{-- Notas de Resolución --}}
                <div class="mt-2 space-y-1">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Notas u Observaciones @if($reviewAction === 'reject') <span class="text-red-500">*</span> @endif
                    </label>
                    <textarea 
                        wire:model="reviewNotes" 
                        rows="3" 
                        class="w-full rounded-xl border-slate-200 dark:border-white/10 dark:bg-dark-card focus:ring-indigo-500 text-sm"
                        placeholder="Ej. Constancia médica verificada..."
                    ></textarea>
                    @error('reviewNotes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100 dark:border-white/5">
                <x-ui.button 
                    variant="secondary" 
                    size="sm"
                    wire:click="closeReview('')"
                >
                    Cancelar
                </x-ui.button>
                <x-ui.button 
                    variant="{{ $reviewAction === 'approve' ? 'success' : 'error' }}" 
                    size="sm"
                    wire:click="processReview" 
                    wire:loading.attr="disabled"
                >
                    Confirmar {{ $reviewAction === 'approve' ? 'Aprobación' : 'Rechazo' }}
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>