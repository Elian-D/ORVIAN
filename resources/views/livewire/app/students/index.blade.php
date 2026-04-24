<div>
    {{-- 1. TOOLBAR DEL MÓDULO --}}
    <x-app.module-toolbar>
        <x-slot:actions>
            @can('students.create')
                @php
                    $atLimit = $this->studentQuotaStats['atLimit'];
                @endphp

                <x-ui.button 
                    variant="primary" 
                    size="sm" 
                    iconLeft="heroicon-s-plus" 
                    {{-- Solo pasamos el href si NO hemos llegado al límite --}}
                    :href="!$atLimit ? route('app.academic.students.create') : null" 
                    :disabled="$atLimit"
                >
                    Nuevo Estudiante
                </x-ui.button>
            @endcan
            @can('students.import')
                <x-ui.button
                    variant="secondary"
                    type="ghost"
                    size="sm"
                    iconLeft="heroicon-s-arrow-up-tray"
                    href="{{ route('app.academic.students.import') }}"
                >
                    Importar
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

    {{-- SECCIÓN DE ESTADÍSTICAS Y CAPACIDAD --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-2">
        
        {{-- Card 1: Total Activos --}}
        <x-admin.stat-card 
            title="Estudiantes Activos"
            :value="$this->studentQuotaStats['used']"
            icon="heroicon-o-users"
            color="text-orvian-blue"
        />

        {{-- Card 2: Capacidad del Plan (Uso de Props limit y used) --}}
        <x-admin.stat-card 
            title="Capacidad del Plan"
            :value="$this->studentQuotaStats['percentage'] . '%'"
            icon="heroicon-o-chart-pie"
            color="text-orvian-orange"
            :limit="$this->studentQuotaStats['limit']"
            :used="$this->studentQuotaStats['used']"
        />

        {{-- Card 3: Cupos Disponibles --}}
        <x-admin.stat-card 
            title="Cupos Disponibles"
            :value="$this->studentQuotaStats['remaining']"
            icon="heroicon-o-user-plus"
            :color="$this->studentQuotaStats['atLimit'] ? 'text-red-500' : 'text-green-500'"
        />
    </div>
    

    <x-ui.page-header
        title="Estudiantes"
        description="Gestiona el expediente académico, datos personales y biometría de los alumnos."
        :count="$students->total()"
        countLabel="estudiantes"
    >
    </x-ui.page-header>
    
    {{-- 3. TABLA BASE --}}
    <x-data-table.base-table 
        :items="$students" 
        :definition="\App\Tables\App\StudentTableConfig::class"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="count(array_filter($filters)) > 0">

        <x-slot:filterSlot>
            {{-- 2. SISTEMA DE FILTROS --}}
            <x-data-table.filter-container :activeCount="count(array_filter($filters))">
                <x-data-table.filter-group title="Clasificación Académica" :activeCount="!empty($filters['school_section_id']) ? 1 : 0">
                    <x-data-table.filter-select 
                        label="Sección / Grado" 
                        filterKey="school_section_id" 
                        :options="$sections" 
                        placeholder="Todas las secciones" 
                    />
                </x-data-table.filter-group>

                <x-data-table.filter-group title="Atributos y Estado" :collapsed="true">
                    <x-data-table.filter-select 
                        label="Género" 
                        filterKey="gender" 
                        :options="['M' => 'Masculino', 'F' => 'Femenino']" 
                    />
                    
                    <x-data-table.filter-select
                        label="Estado de Acceso"
                        filterKey="status"
                        :options="['1' => 'Activos', '0' => 'Inactivos']"
                        placeholder="Todos los estados" 
                    />
                    <x-data-table.filter-toggle label="Con Fotografía" filterKey="has_photo" />
                    <x-data-table.filter-toggle label="Con Biometría" filterKey="has_face_encoding" />
                </x-data-table.filter-group>
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        @forelse($students as $student)
            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors">
                {{-- Nombre --}}
                <x-data-table.cell column="full_name" :visible="$visibleColumns">
                    <a href="{{ route('app.academic.students.show', $student) }}" class="text-sm font-bold text-orvian-orange hover:underline whitespace-nowrap">
                        {{ $student->full_name }}
                    </a>
                </x-data-table.cell>

                {{-- RNC/Cédula --}}
                <x-data-table.cell column="rnc" :visible="$visibleColumns">
                    <span class="text-xs font-mono text-slate-500">{{ $student->rnc ?? 'N/A' }}</span>
                </x-data-table.cell>

                {{-- Sección --}}
                <x-data-table.cell column="section" :visible="$visibleColumns">
                    <x-ui.badge 
                        variant="info" 
                        size="sm" 
                        {{-- 
                        max-w-[200px]: Limita el ancho (ajusta el valor a tu gusto)
                        truncate: Añade los "..." automáticamente
                        block: Asegura que el ancho máximo se respete
                        --}}
                        class="max-w-[180px] truncate block"
                        {{-- Opcional: añade un title para que al poner el mouse encima se vea el nombre completo --}}
                        title="{{ $student->full_section_name }}"
                    >
                        {{ $student->full_section_name }}
                    </x-ui.badge>
                </x-data-table.cell>

                {{-- Género --}}
                <x-data-table.cell column="gender" :visible="$visibleColumns">
                    <span class="text-sm">{{ $student->gender === 'M' ? 'M' : 'F' }}</span>
                </x-data-table.cell>

                {{-- Edad --}}
                <x-data-table.cell column="age" :visible="$visibleColumns">
                    <span class="text-sm text-slate-600">{{ $student->date_of_birth?->age ?? '--' }} años</span>
                </x-data-table.cell>

                {{-- Estado --}}
                <x-data-table.cell column="status" :visible="$visibleColumns">
                    <x-ui.badge :variant="$student->is_active ? 'success' : 'error'" size="sm" dot>
                        {{ $student->is_active ? 'Activo' : 'Inactivo' }}
                    </x-ui.badge>
                </x-data-table.cell>

                {{-- Biometría --}}
                <x-data-table.cell column="has_face_encoding" :visible="$visibleColumns">
                    @if($student->has_face_encoding)
                        <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                    @else
                        <x-heroicon-s-x-circle class="w-5 h-5 text-slate-300" />
                    @endif
                </x-data-table.cell>

                {{-- Acciones --}}
                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <x-ui.button variant="info" type="ghost" size="sm" icon="heroicon-o-qr-code" wire:click="showQr({{ $student->id }})" title="Ver QR" />
                        
                        {{-- Cambio: De wire:click a enlace de edición --}}
                        @can('students.edit',)
                            <x-ui.button 
                            variant="secondary" 
                            type="ghost" 
                            size="sm" 
                            icon="heroicon-o-pencil-square" 
                            href="{{ route('app.academic.students.edit', $student) }}" 
                            />
                            
                            
                            @if($student->is_active)
                                <x-ui.button 
                                    variant="error" 
                                    type="ghost" 
                                    size="sm" 
                                    icon="heroicon-o-user-minus" 
                                    wire:click="confirmWithdraw({{ $student->id }})" 
                                    title="Dar de baja" 
                                />
                            @else
                                @php $atLimit = $this->studentQuotaStats['atLimit']; @endphp
                                
                                <x-ui.button 
                                    variant="success" 
                                    type="ghost" 
                                    size="sm" 
                                    icon="heroicon-o-user-plus" 
                                    {{-- Si está al límite, no permitimos el click y cambiamos el estilo --}}
                                    :disabled="$atLimit"
                                    :class="$atLimit ? 'opacity-50 cursor-not-allowed' : ''"
                                    wire:click="{{ !$atLimit ? 'confirmReactivate(' . $student->id . ')' : '' }}" 
                                    title="{{ $atLimit ? 'Límite de plan alcanzado' : 'Reactivar' }}" 
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
                        title="No hay estudiantes registrados"
                        description="Intenta ajustar los filtros o registra un nuevo estudiante." />
                </td>
            </tr>
        @endforelse
    </div>
    </x-data-table.base-table>
    
    {{-- 4. MODAL QR --}}
    <x-modal name="qr-modal" :show="$showQrModal" x-on:close="$wire.closeModals()">
        @if($this->selectedStudent)
            <div class="p-8 text-center space-y-6">
                <h3 class="text-xl font-black text-slate-800 dark:text-white">{{ $this->selectedStudent->full_name }}</h3>
                
                <div class="inline-block p-6 bg-white rounded-[2rem] shadow-inner border border-slate-100">
                    {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)
                        ->color(30, 41, 59)
                        ->margin(1)
                        ->generate($this->selectedStudent->qr_code) !!}
                </div>

                <div class="space-y-3">
                    <p class="text-xs font-mono text-slate-400 tracking-widest uppercase">
                        ID: {{ $this->selectedStudent->qr_code }}
                    </p>
                </div>
            </div>
        @endif
    </x-modal>

    {{-- 5. MODAL DE BAJA --}}
    <x-modal name="withdraw-modal" :show="$showWithdrawModal" x-on:close="$wire.closeModals()" maxWidth="md">
        <form wire:submit.prevent="withdraw" class="p-6">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Retirar Estudiante</h2>
            
            @if($this->selectedStudent)
                <p class="text-sm text-slate-600 dark:text-slate-300 mb-6">
                    Estás a punto de dar de baja a <span class="font-bold">{{ $this->selectedStudent->full_name }}</span>.
                </p>
            @endif
            

            <div class="space-y-6">
                {{-- Componente Input Orvian --}}
                <x-ui.forms.input
                    label="Fecha de Retiro"
                    name="withdrawal_date"
                    type="date"
                    iconLeft="heroicon-o-calendar"
                    wire:model="withdrawal_date"
                    :error="$errors->first('withdrawal_date')"
                    required
                />

                {{-- Componente Textarea Orvian --}}
                <x-ui.forms.textarea
                    label="Motivo del Retiro"
                    name="withdrawal_reason"
                    placeholder="Indique la razón del retiro (ej. Mudanza, transferencia...)"
                    wire:model="withdrawal_reason"
                    :error="$errors->first('withdrawal_reason')"
                    hint="Este motivo quedará registrado en el historial del estudiante."
                />
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <x-ui.button 
                    type="button" 
                    variant="secondary" 
                    type="ghost" 
                    x-on:click="$dispatch('close-modal', 'withdraw-modal')"
                >
                    Cancelar
                </x-ui.button>
                <x-ui.button 
                    type="solid" 
                    variant="error" 
                    wire:click="withdraw" {{-- Llamada directa en lugar de submit --}}
                    wire:loading.attr="disabled"
                >
                    Confirmar Baja
                </x-ui.button>
            </div>
        </form>
    </x-modal>

    {{-- 6. MODAL DE REACTIVACIÓN --}}
    <x-modal name="reactivate-modal" :show="$showReactivateModal" x-on:close="$wire.closeModals()" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Re-inscribir Estudiante</h2>
                @if($this->selectedStudent)
                    <p class="text-sm text-slate-600 dark:text-slate-300 mb-2">
                        ¿Estás seguro que deseas re-inscribir y activar a <span class="font-bold">{{ $this->selectedStudent->full_name }}</span>?
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">
                        Esto limpiará su registro de retiro anterior. Se recomienda verificar que su grado y sección actualizados sean correctos después de esta acción.
                    </p>
                @endif
            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button wire:click="closeModals" variant="slate">
                    Cancelar
                </x-ui.button>

                <x-ui.button 
                    wire:click="reactivate" 
                    variant="success"
                    :disabled="$this->studentQuotaStats['atLimit']"
                >
                    Confirmar y Reactivar
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>