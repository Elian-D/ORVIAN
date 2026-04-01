<div class="p-6 space-y-8">
{{-- 1. Header: Perfil del Centro --}}
    <div class="flex flex-col md:flex-row justify-between items-center md:items-start gap-8">
        <div class="flex flex-col md:flex-row items-center md:items-start gap-6 w-full">
            
            {{-- Contenedor del Logo --}}
            <div class="relative flex-shrink-0">
                {{-- Pasamos 'newLogo' que es la propiedad pública que creamos en el componente Livewire --}}
                <x-ui.school-logo :school="$school" size="xl" uploadModel="newLogo" />
            </div>

            {{-- Bloque de Texto e Información --}}
            <div class="flex flex-col items-center md:items-start text-center md:text-left space-y-4 w-full">
                
                {{-- Etiquetas de Identificación (Plan y Estado) --}}
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                    {{-- Identificador de Plan --}}
                    <div class="flex items-center gap-2 bg-gray-100 dark:bg-dark-card pl-2 pr-1 py-1 rounded-lg border border-gray-200 dark:border-gray-800">
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Plan:</span>
                        <x-ui.badge 
                            :hex="$school->plan->text_color ?? '#64748b'" 
                            variant="slate" 
                            size="sm"
                            class="font-black uppercase tracking-tighter"
                        >
                            {{ $school->plan->name }}
                        </x-ui.badge>
                    </div>

                    {{-- Identificador de Estado --}}
                    <div class="flex items-center gap-2 bg-gray-100 dark:bg-dark-card pl-2 pr-1 py-1 rounded-lg border border-gray-200 dark:border-gray-800">
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Estado:</span>
                        <x-ui.badge 
                            variant="{{ $school->getStatusVariant() }}" 
                            size="sm" 
                            dot
                            class="font-bold"
                        >
                            {{ $school->getStatusLabel() }}
                        </x-ui.badge>
                    </div>

                    {{-- Código SIGERD --}}
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-dark-card border border-gray-200 dark:border-gray-800 shadow-inner">
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">SIGERD:</span>
                        <span class="text-xs font-mono text-gray-900 dark:text-white font-bold">{{ $school->sigerd_code ?? $school->id }}</span>
                    </div>
                </div>

                {{-- Nombre del Centro --}}
                <div class="space-y-1">
                    <h1 class="text-2xl md:text-4xl font-black text-gray-900 dark:text-white leading-tight tracking-tight">
                        {{ $school->name }}
                    </h1>
                    
                    {{-- Ubicación --}}
                    <div class="flex items-center justify-center md:justify-start gap-2 text-gray-500 dark:text-gray-400">
                        <x-heroicon-s-map-pin class="w-5 h-5 text-orvian-orange" />
                        <p class="text-sm md:text-base font-medium">
                            {{ $school->province?->name }}, {{ $school->municipality?->name }}, RD — <span class="text-gray-400 dark:text-gray-500 uppercase text-xs font-bold tracking-widest">Distrito {{ $school->educationalDistrict?->id }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Acciones Rápidas --}}
        <div class="flex flex-row md:flex-col lg:flex-row gap-3 w-full md:w-auto">
            <x-ui.button 
                variant="secondary" 
                type="outline"
                size="md"
                iconLeft="heroicon-o-pencil-square"
                class="flex-1 md:w-full lg:w-auto"
                wire:click="edit({{ $school->id }})" 
            >
                Editar
            </x-ui.button>
            
            <x-ui.button 
                variant="primary" 
                size="md"
                iconLeft="heroicon-s-arrow-down-tray"
                class="flex-1 md:w-full lg:w-auto"
                wire:click="exportReport({{ $school->id }})" 
            >
                Reporte
            </x-ui.button>
        </div>
    </div>

    {{-- 2. Grid de Métricas Rápidas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        {{-- Card 1: Uso de Cuota Estudiantil --}}
        <x-admin.stat-card 
            title="Estudiantes" 
            :value="$this->quotaStats['used'] . ' / ' . $this->quotaStats['limit']" 
            :used="$this->quotaStats['used']" 
            :limit="$this->quotaStats['limit']" 
            icon="heroicon-o-users" 
            color="text-orvian-blue" 
        />
        
        {{-- Card 2: Capacidad Docente / Staff --}}
        <x-admin.stat-card 
            title="Staff Académico" 
            :value="$this->staffCount" 
            icon="heroicon-o-user-group" 
            color="text-orvian-orange" 
        />

        {{-- Card 3: Tamaño de Operación (Secciones/Aulas) --}}
        <x-admin.stat-card 
            title="Secciones Activas" 
            :value="$this->sectionsCount" 
            icon="heroicon-o-rectangle-group" 
            color="text-indigo-500" 
        />

        {{-- Card 4: Ingreso Mensual (Valor del Plan) --}}
        <x-admin.stat-card 
            title="Ingreso (MRR)" 
            :value="'USD$ ' . number_format($school->plan->price ?? 0, 0)" 
            icon="heroicon-o-banknotes" 
            color="text-green-500" 
        />
    </div>

    {{-- 3. Navegación por Tabs --}}
    <div class="flex border-b border-slate-200 dark:border-gray-800 gap-8 overflow-x-auto">
        @php
            $tabs = [
                'general'   => 'Información General',
                'courses'   => 'Cursos y Grados',
            ];
        @endphp

        @foreach($tabs as $id => $label)
            <button 
                wire:click="$set('activeTab', '{{ $id }}')"
                class="pb-4 px-2 transition-all font-bold text-sm relative group
                {{ $activeTab === $id 
                    ? 'text-orvian-orange' 
                    : 'text-slate-500 dark:text-gray-500 hover:text-orvian-navy dark:hover:text-white' 
                }}">
                
                {{ $label }}

                {{-- Indicador de borde inferior animado --}}
                <div class="absolute bottom-0 left-0 w-full h-0.5 transition-all
                    {{ $activeTab === $id 
                        ? 'bg-orvian-orange' 
                        : 'bg-transparent group-hover:bg-slate-300 dark:group-hover:bg-gray-700' 
                    }}">
                </div>
            </button>
        @endforeach
    </div>
    

    {{-- 4. Contenido Principal: Datos y Gráficos --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        {{-- Bloque de Información Técnica --}}
        <div class="lg:col-span-2 space-y-6">
            @include('livewire.admin.schools.tabs._' . $activeTab)  
        </div>

        {{-- Sidebar: Cuotas y Acciones Críticas --}}
        <div class="space-y-6 sticky top-6 z-10">
            {{-- Gráfico de Capacidad --}}
            <div class="bg-white dark:bg-dark-card border border-slate-200 dark:border-gray-800 rounded-3xl p-8 text-center space-y-6 relative overflow-hidden shadow-sm dark:shadow-none">
                
                {{-- Badge de Alerta Superior --}}
                @if($this->staffQuotaStats['percentage'] >= 85)
                    <div class="absolute top-4 right-4">
                        <span class="flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $this->staffQuotaStats['percentage'] >= 100 ? 'bg-state-error/75' : 'bg-orvian-orange/75' }}"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 {{ $this->staffQuotaStats['percentage'] >= 100 ? 'bg-state-error' : 'bg-orvian-orange' }}"></span>
                        </span>
                    </div>
                @endif

                <h3 class="text-slate-400 dark:text-gray-500 font-bold uppercase text-[10px] tracking-widest">Capacidad de Gestión (Staff)</h3>
                
                <div class="relative w-40 h-40 mx-auto">
                    {{-- SVG con transformaciones adaptativas --}}
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                        {{-- Fondo (Círculo completo) - Ajustado para ser visible en ambos temas --}}
                        <circle cx="18" cy="18" r="16" 
                            class="text-slate-100 dark:text-gray-800" 
                            stroke-width="3" 
                            stroke="currentColor" 
                            fill="none" />
                        
                        {{-- Progreso Dinámico --}}
                        <circle cx="18" cy="18" r="16" 
                            class="{{ $this->staffQuotaStats['status_color'] }} transition-all duration-1000 ease-in-out" 
                            stroke-width="3" 
                            stroke-dasharray="{{ $this->staffQuotaStats['percentage'] }}, 100" 
                            stroke-linecap="round" 
                            stroke="currentColor" 
                            fill="none" 
                        />
                    </svg>
                    
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black tracking-tighter {{ $this->staffQuotaStats['percentage'] >= 100 ? 'text-state-error' : 'text-orvian-navy dark:text-white' }}">
                            {{ $this->staffQuotaStats['percentage'] }}%
                        </span>
                        <span class="text-[10px] text-slate-400 dark:text-gray-500 font-bold uppercase tracking-widest">Ocupación</span>
                    </div>
                </div>

                {{-- Texto de Feedback descriptivo --}}
                <div class="min-h-[40px] flex items-center justify-center px-2">
                    <p class="text-sm text-slate-500 dark:text-gray-400 leading-relaxed">
                        @if($this->staffQuotaStats['percentage'] >= 100)
                            <span class="text-state-error font-bold italic">Límite alcanzado.</span> <span class="dark:text-gray-300">Upgrade requerido.</span>
                        @elseif($this->staffQuotaStats['percentage'] >= 85)
                            <span class="text-orvian-orange font-bold">Crítico:</span> solo <span class="font-bold dark:text-white">{{ $this->staffQuotaStats['remaining'] }}</span> cupos restantes.
                        @else
                            Disponibles: <span class="text-orvian-navy dark:text-white font-extrabold">{{ $this->staffQuotaStats['remaining'] }}</span> <span class="text-xs">usuarios de staff</span>.
                        @endif
                    </p>
                </div>
            </div>

            {{-- Botones de Estado --}}
            <div class="space-y-3">
                {{-- 1. Activar / Desactivar (Sistema) --}}
                @php
                    $canToggleActive = !($school->is_active && !$school->is_suspended);
                    $activeTooltip = $school->is_active 
                        ? ($school->is_suspended ? 'Desactivar por completo' : 'Debe suspenderse antes de inactivar') 
                        : 'Reactivar institución';
                @endphp
                
                <x-ui.button 
                    wire:click="toggleActiveStatus"
                    wire:target="toggleActiveStatus"
                    variant="{{ $school->is_active ? 'info' : 'success' }}"
                    type="outline"
                    size="lg"
                    fullWidth
                    title="{{ $activeTooltip }}"
                    :disabled="!$canToggleActive"
                    iconLeft="{{ $school->is_active ? 'heroicon-s-power' : 'heroicon-s-play' }}"
                >
                    <span wire:loading.remove wire:target="toggleActiveStatus">
                        {{ $school->is_active ? 'Inactivar Institución' : 'Reactivar Institución' }}
                    </span>
                    <span wire:loading wire:target="toggleActiveStatus">Procesando...</span>
                </x-ui.button>

                {{-- 2. Suspender / Restaurar (Pagos) --}}
                <x-ui.button 
                    wire:click="toggleSuspension"
                    wire:target="toggleSuspension"
                    variant="{{ $school->is_suspended ? 'warning' : 'error' }}"
                    type="outline"
                    size="lg"
                    fullWidth
                    title="{{ !$school->is_active ? 'Reactive el centro para gestionar el servicio' : ($school->is_suspended ? 'Restaurar servicio' : 'Suspender servicio') }}"
                    :disabled="!$school->is_active"
                    iconLeft="{{ $school->is_suspended ? 'heroicon-s-check-circle' : 'heroicon-s-no-symbol' }}"
                >
                    <span wire:loading.remove wire:target="toggleSuspension">
                        {{ $school->is_suspended ? 'Restaurar Servicio' : 'Suspender Institución' }}
                    </span>
                    <span wire:loading wire:target="toggleSuspension">Actualizando...</span>
                </x-ui.button>
            </div>
        </div>
    </div>
</div>