<div class="p-6 max-w-5xl mx-auto text-white">
    <!-- Header -->
    <x-ui.page-header 
        title="Configuración de Ventanas Horarias" 
        description="Ajuste permanente de los umbrales de tardanza por jornada escolar.">
    </x-ui.page-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Columna Izquierda: Lista de Tandas -->
        <div class="md:col-span-1 space-y-4">
            <h2 class="text-xs uppercase tracking-widest text-gray-400 font-bold mb-2">Tandas Activas</h2>
            @foreach($this->shifts as $shift)
                @php
                    $shiftType = strtoupper($shift->type);
                    $isSelected = $selectedShiftId == $shift->id;
                    
                    // Selección de icono según tipo de tanda
                    $iconName = match($shiftType) {
                        'MATUTINA'   => 'sun',
                        'VESPERTINA' => 'moon',
                        'NOCTURNA'   => 'moon',
                        'EXTENDIDA'  => 'clock',
                        default      => 'calendar'
                    };
                @endphp
                
                <div 
                    wire:click="$set('selectedShiftId', {{ $shift->id }})"
                    class="relative cursor-pointer p-5 rounded-orvian border transition-all duration-200 overflow-hidden
                    {{ $isSelected 
                        ? 'bg-orvian-card border-orvian-orange ring-2 ring-orvian-orange/50' 
                        : 'bg-orvian-card border-gray-100 dark:border-dark-border hover:border-gray-500' }}"
                >
                    <div class="flex justify-between items-center relative z-10">
                        <div>
                            <h3 class="font-bold text-lg">{{ $shift->type }}</h3>
                            <p class="text-gray-400 text-sm">Entrada Estándar: {{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }}</p>
                        </div>
                        
                        {{-- Icono Dinámico: Cambia a naranja si está seleccionado --}}
                        <div class="{{ $isSelected ? 'text-orvian-orange' : 'text-gray-500' }}">
                            <x-dynamic-component 
                                :component="'heroicon-o-' . $iconName" 
                                class="w-8 h-8" 
                            />
                        </div>
                    </div>

                    {{-- Efecto decorativo de icono grande al fondo (estilo image_4195e8.png) --}}
                    <div class="absolute -right-2 -bottom-2 opacity-[0.03] pointer-events-none">
                         <x-dynamic-component 
                            :component="'heroicon-s-' . $iconName" 
                            class="w-20 h-20" 
                        />
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Columna Derecha -->
        <div class="md:col-span-2">
            <div class="bg-orvian-card border border-gray-100 dark:border-dark-border p-8 rounded-orvian min-h-[250px]">
                @if($selectedShiftId)
                    <!-- grid-cols-1 en móvil, grid-cols-2 a partir de md -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        <!-- Nuevo campo Hora -->
                        <div class="group relative">
                            <label class="block text-xs uppercase font-bold text-gray-400 mb-3">NUEVA HORA DE ENTRADA</label>
                            <input type="time" wire:model.live="newStartTime" class="w-full bg-black border border-gray-100 dark:border-dark-border hover:border-gray-500 rounded-orvian p-6 text-orvian-orange font-bold text-2xl">
                        </div>

                        <!-- Campo Umbral -->
                        <div class="group">
                            <label class="block text-xs uppercase font-bold text-gray-400 mb-3">TOLERANCIA (MINUTOS)</label>
                            <input type="number" 
                                wire:model.live="newLateThresholdMinutes" 
                                class="w-full bg-black border border-gray-100 dark:border-dark-border hover:border-gray-500 rounded-orvian p-6 text-orvian-orange font-bold text-2xl" 
                                placeholder="Ej: 15">
                        </div>
                    </div>

                    <div class="mt-10">
                        <x-ui.button wire:click="applyAdjustment" variant="primary" fullWidth :hoverEffect="true">
                            Guardar Configuración Permanente
                        </x-ui.button>
                    </div>
                @else
                    <div class="flex items-center justify-center h-full text-gray-500 italic">
                        Seleccione una tanda para configurar
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sección Inferior: Impacto -->
    <div class="mt-8">
        <div x-show="$wire.impactDescription !== ''" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-4"
            x-transition:enter-end="opacity-01 transform translate-y-0"
            class="relative bg-gradient-to-r from-orvian-orange/10 to-transparent border-l-4 border-orvian-orange rounded-2xl p-6 overflow-hidden">
            
            <div class="flex items-center gap-4">
                <!-- Icono decorativo -->
                <div class="flex-shrink-0 w-12 h-12 bg-orvian-orange rounded-2xl flex items-center justify-center shadow-lg shadow-orvian-orange/20">
                    <x-heroicon-s-information-circle class="w-7 h-7 text-white" />
                </div>

                <!-- Contenido del texto -->
                <div>
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-orvian-orange mb-1">
                        VISTA PREVIA DE IMPACTO
                    </h4>
                    <p class="text-sm text-gray-200 leading-relaxed">
                        {!! $this->impactDescription !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>