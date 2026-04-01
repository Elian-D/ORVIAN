<div>

    {{-- Grid de Estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        
        {{-- Card: Total Centros --}}
        <x-admin.stat-card 
            title="Total Centros"
            :value="$this->stats['total_schools']"
            icon="heroicon-s-academic-cap" 
            color="text-orvian-blue"
        />

        {{-- Card: Usuarios Totales --}}
        <x-admin.stat-card 
            title="Usuarios Totales"
            :value="$this->stats['total_users']"
            icon="heroicon-s-users" 
            color="text-orvian-orange"
        />

        {{-- Card: MRR Estimado --}}
        <x-admin.stat-card 
            title="MRR (Dominicana)"
            :value="$this->stats['mrr']"
            icon="heroicon-s-currency-dollar" 
            color="text-state-success"
        />
        

    </div>
    {{-- ══════════════════════════════════════════════════
         HEADER DE PÁGINA
    ═══════════════════════════════════════════════════ --}}
    <x-ui.page-header
        title="Centros Educativos"
        description="Administración global de instituciones, suscripciones y estado de acceso."
        :count="$schools->total()"
        countLabel="centros">

        <x-slot:actions>
            @can('schools.create')
                <x-ui.button
                    variant="primary"
                    size="sm"
                    iconLeft="heroicon-s-plus"
                    href="{{ route('admin.setup') }}"
                    >
                    Registrar Centro
                </x-ui.button>
            @endcan

        </x-slot:actions>
    </x-ui.page-header>

    {{-- ══════════════════════════════════════════════════
         DATATABLE
    ═══════════════════════════════════════════════════ --}}
    <x-data-table.base-table
        :items="$schools"
        :definition="\App\Tables\Admin\SchoolTableConfig::class"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="count(array_filter($filters)) > 0">

        <x-slot:filterSlot>
            <x-data-table.filter-container :activeCount="count(array_filter($filters))">

                {{-- Grupo 1: Ubicación --}}
                <x-data-table.filter-group 
                    title="Geografía Educativa" 
                    :activeCount="(!empty($filters['regional']) ? 1 : 0) + (!empty($filters['district']) ? 1 : 0)"
                >
                    <x-data-table.filter-select
                        label="Regional Educativa"
                        filterKey="regional"
                        :options="$regionals"
                        placeholder="Todas las regionales" />

                    @if(!empty($filters['regional']))
                        <x-data-table.filter-select
                            label="Distrito Educativo"
                            filterKey="district"
                            :options="$districts"
                            placeholder="Todos los distritos" />
                    @endif
                </x-data-table.filter-group>

                {{-- Grupo 2: Estado y Plan (Colapsado por defecto) --}}
                <x-data-table.filter-group 
                    title="Estado y Suscripción" 
                    :collapsed="true"
                    :activeCount="(!empty($filters['plan']) ? 1 : 0) + (!empty($filters['status']) ? 1 : 0) + ($filters['suspended'] ? 1 : 0)"
                >
                    <x-data-table.filter-select
                        label="Plan de Suscripción"
                        filterKey="plan"
                        :options="$plans->pluck('name', 'id')->toArray()"
                        placeholder="Todos los planes" />

                    <x-data-table.filter-select
                        label="Estado de Acceso"
                        filterKey="status"
                        :options="['1' => 'Activos', '0' => 'Inactivos']"
                        placeholder="Todos los estados" />

                    <x-data-table.filter-toggle
                        label="Solo Suspendidos"
                        filterKey="suspended"
                        description="Servicios pausados por pago" />
                </x-data-table.filter-group>

            </x-data-table.filter-container>
        </x-slot:filterSlot>
        {{-- Filas de la Tabla --}}
        @forelse($schools as $school)
            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors duration-150">
                
                {{-- Nombre y Logo (Identidad) --}}
                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <div class="flex items-center gap-3">
                        {{-- Componente de logo institucional (o fallback de iniciales) --}}
                        <x-ui.school-logo :school="$school" size="sm" />
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                                {{ $school->name }}
                            </p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">
                                {{ $school->sigerd_code }}
                            </p>
                        </div>
                    </div>
                </x-data-table.cell>

                {{-- Colocar después de la celda de "name" --}}
                <x-data-table.cell column="principal" :visible="$visibleColumns">
                    @if($school->principal)
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                {{ $school->principal->name }}
                            </span>
                            <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono leading-tight">
                                {{ $school->principal->email }}
                            </span>
                        </div>
                    @else
                        <span class="text-xs text-slate-400 italic">No asignado</span>
                    @endif
                </x-data-table.cell>

                {{-- Plan con Badge Dinámico --}}
                <x-data-table.cell column="plan" :visible="$visibleColumns">
                    <button 
                        wire:click="confirmPlanChange({{ $school->id }})"
                        class="group relative flex items-center outline-none"
                        title="Cambiar plan de suscripción"
                    >
                        <x-ui.badge 
                            :hex="$plans->firstWhere('id', $school->plan_id)?->text_color ?? '#64748b'" 
                            variant="slate" 
                            size="sm" 
                            class="transition-all duration-200 group-hover:pr-7 group-active:scale-95"
                        >
                            {{ $school->plan->name }}
                            
                            {{-- Icono sutil que aparece en hover --}}
                            <span class="absolute right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <x-heroicon-s-arrows-right-left class="w-3 h-3" />
                            </span>
                        </x-ui.badge>
                    </button>
                </x-data-table.cell>

                {{-- Salud: Conteo de Usuarios (Staff vs Estudiantes) --}}
                <x-data-table.cell column="users_count" :visible="$visibleColumns">
                    <div class="flex flex-col gap-1.5">
                        {{-- Badge de Staff/Otros --}}
                        <div class="flex items-center gap-1.5">
                            <x-ui.badge variant="slate" size="sm" class="font-medium">
                                <div class="flex items-center gap-1">
                                    <x-heroicon-s-users class="w-3 h-3 text-slate-500" />
                                    <span>{{ number_format($school->staff_count) }}</span>
                                </div>
                            </x-ui.badge>
                            <span class="text-[10px] text-slate-400 uppercase font-bold tracking-tight">Gestión</span>
                        </div>

                        {{-- Badge de Estudiantes --}}
                        <div class="flex items-center gap-1.5">
                            <x-ui.badge variant="info" size="sm" class="font-medium">
                                <div class="flex items-center gap-1">
                                    <x-heroicon-s-academic-cap class="w-3 h-3 text-blue-500" />
                                    <span>{{ number_format($school->students_count) }}</span>
                                </div>
                            </x-ui.badge>
                            <span class="text-[10px] text-slate-400 uppercase font-bold tracking-tight">Estud.</span>
                        </div>
                    </div>
                </x-data-table.cell>

                {{-- Salud: Última Actividad --}}
                <x-data-table.cell column="health" :visible="$visibleColumns">
                    <div class="flex flex-col">
                        <span class="text-xs font-medium {{ $school->last_activity ? 'text-slate-600 dark:text-slate-300' : 'text-slate-400 italic' }}">
                            {{ $school->last_activity ? \Carbon\Carbon::parse($school->last_activity)->diffForHumans() : 'Sin actividad' }}
                        </span>
                        <div class="flex items-center gap-1 mt-0.5">
                            <div @class([
                                'w-1.5 h-1.5 rounded-full',
                                'bg-green-500' => $school->last_activity && \Carbon\Carbon::parse($school->last_activity)->gt(now()->subDays(3)),
                                'bg-amber-500' => $school->last_activity && \Carbon\Carbon::parse($school->last_activity)->between(now()->subDays(7), now()->subDays(3)),
                                'bg-slate-300' => !$school->last_activity || \Carbon\Carbon::parse($school->last_activity)->lt(now()->subDays(7)),
                            ])></div>
                            <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">
                                {{ $school->last_activity && \Carbon\Carbon::parse($school->last_activity)->gt(now()->subDays(3)) ? 'Activo' : 'Latente' }}
                            </span>
                        </div>
                    </div>
                </x-data-table.cell>

                {{-- Estado de Acceso --}}
                <x-data-table.cell column="is_active" :visible="$visibleColumns">
                    <x-ui.badge 
                        variant="{{ $school->getStatusVariant() }}" 
                        size="sm" 
                        dot>
                        {{ $school->getStatusLabel() }}
                    </x-ui.badge>
                </x-data-table.cell>

                {{-- Creacion --}}
                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        {{ \Carbon\Carbon::parse($school->created_at)->format('d/m/Y') }}
                    </span>
                </x-data-table.cell>


                {{-- Acciones --}}
                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        
                        {{-- 1. Show --}}
                        <x-ui.button 
                            variant="info" 
                            type="ghost"
                            size="sm"
                            icon="heroicon-o-eye"
                            title="Ver Información"
                            href="{{ route('admin.schools.show', $school->id ) }}" />

                        {{-- 1. Editar --}}
                        <x-ui.button 
                            variant="secondary" 
                            type="ghost"
                            size="sm"
                            icon="heroicon-o-pencil-square"
                            title="Editar Información"
                            wire:click="edit({{ $school->id }})" />
                        
                        {{-- 2. Suspender / Restaurar (Pagos) --}}
                        {{-- BLOQUEO: Solo si is_active es true --}}
                        <span 
                            @if(!$school->is_active) 
                                title="Reactive el centro para gestionar el servicio" 
                                class="cursor-not-allowed"
                            @endif
                        >
                            <x-ui.button 
                                variant="{{ $school->is_suspended ? 'success' : 'warning' }}" 
                                type="ghost"
                                size="sm"
                                icon="{{ $school->is_suspended ? 'heroicon-o-play-circle' : 'heroicon-o-pause-circle' }}"
                                title="{{ $school->is_suspended ? 'Restaurar servicio' : 'Suspender por falta de pago' }}"
                                wire:click="toggleSuspension({{ $school->id }})"
                                :disabled="!$school->is_active"
                                class="{{ !$school->is_active ? 'opacity-30' : 'opacity-100' }}" />
                        </span>

                        {{-- 3. Activar / Desactivar (Sistema) --}}
                        <span 
                            @if($school->is_active && !$school->is_suspended) 
                                title="Debe suspenderse antes de inactivar" 
                                class="cursor-not-allowed"
                            @elseif(!$school->is_active)
                                title="Reactivar centro"
                            @endif
                        >
                            <x-ui.button 
                                variant="{{ $school->is_active ? 'error' : 'slate' }}" 
                                type="ghost"
                                size="sm"
                                icon="heroicon-o-power"
                                title="{{ $school->is_active ? 'Inactivar Centro' : 'Reactivar Centro' }}"
                                wire:click="toggleActiveStatus({{ $school->id }})"
                                :disabled="$school->is_active && !$school->is_suspended"
                                class="{{ ($school->is_active && !$school->is_suspended) ? 'opacity-30' : 'opacity-100' }}"
                            />
                        </span>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-12">
                    <x-ui.empty-state 
                        variant="simple" 
                        title="No hay centros registrados"
                        description="Intenta ajustar los filtros o registra una nueva institución educativa." />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>

    {{-- ══════════════════════════════════════════════════
         MODAL: CAMBIO DE PLAN
    ═══════════════════════════════════════════════════ --}}
    <x-modal name="change-plan-modal" maxWidth="md">
        <div class="px-6 py-5 bg-white dark:bg-dark-card">
            <h2 class="text-base font-bold text-slate-800 dark:text-white mb-4">Actualizar Plan de Suscripción</h2>
            
            <div class="space-y-4">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Selecciona el nuevo plan para el centro. Esto afectará los límites de usuarios y módulos disponibles.
                </p>

                <div class="grid gap-3">
                    @foreach($plans as $plan)
                        <label @class([
                            'relative flex items-center justify-between p-4 rounded-xl border-2 cursor-pointer transition-all',
                            'border-orvian-orange bg-orvian-orange/5' => $newPlanId == $plan->id,
                            'border-slate-100 dark:border-white/5 hover:border-slate-200' => $newPlanId != $plan->id,
                        ])>
                            <input type="radio" wire:model.live="newPlanId" value="{{ $plan->id }}" class="hidden">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $plan->hex_color }}"></div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700 dark:text-white">{{ $plan->name }}</p>
                                    <p class="text-xs text-slate-500">${{ number_format($plan->price, 2) }} / mes</p>
                                </div>
                            </div>
                            @if($newPlanId == $plan->id)
                                <x-heroicon-s-check-circle class="w-5 h-5 text-orvian-orange" />
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-slate-100 dark:border-dark-border">
                <x-ui.button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'change-plan-modal')">
                    Cancelar
                </x-ui.button>
                <x-ui.button variant="primary" size="sm" wire:click="updatePlan" wire:loading.attr="disabled">
                    Confirmar Cambio
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>