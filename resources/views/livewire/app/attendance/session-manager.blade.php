<div class="p-6 space-y-6">
    {{-- Header --}}
    <x-ui.page-header 
        title="Gestión de Sesiones de Asistencia" 
        description="Control de apertura y cierre de asistencia por tanda institucional.">
        
        <x-slot name="actions">
            {{-- Contenedor del Reloj con Alpine.js --}}
            <div x-data="{ 
                    time: '', 
                    date: '',
                    updateClock() {
                        const now = new Date();
                        this.time = now.toLocaleTimeString('en-US', { 
                            hour12: true, 
                            hour: '2-digit', 
                            minute: '2-digit', 
                            second: '2-digit' 
                        });
                        this.date = now.toLocaleDateString('es-ES', { 
                            weekday: 'long', 
                            day: 'numeric', 
                            month: 'long', 
                            year: 'numeric' 
                        });
                    } 
                }" 
                x-init="updateClock(); setInterval(() => updateClock(), 1000)"
                class="flex flex-col items-end gap-1">
                
                {{-- Hora con protagonismo Naranja Orvian --}}
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black tracking-tighter text-orvian-orange tabular-nums" 
                        style="color: #f78904;">
                        <span x-text="time"></span>
                    </span>
                </div>

                {{-- Fecha detallada --}}
                <div class="px-3 py-1 bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg">
                    <span class="text-[11px] font-medium text-gray-600 dark:text-gray-400 capitalize" x-text="date"></span>
                </div>
            </div>
        </x-slot>
    </x-ui.page-header>

    {{-- Banner Informativo --}}
    @if($this->dailySessions->isEmpty())
        <div class="flex items-center p-5 mb-8 rounded-[2rem] bg-orange-50/50 dark:bg-orvian-orange/10 border border-orange-100 dark:border-orvian-orange/20 backdrop-blur-sm transition-all shadow-sm" role="alert">
            {{-- Icono con Pulso Suave --}}
            <div class="flex-shrink-0 relative">
                <div class="absolute inset-0 rounded-full bg-orvian-orange/20 animate-pulse"></div>
                <svg class="relative w-6 h-6 text-orvian-orange" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>

            <div class="ml-4">
                <h4 class="text-sm font-bold text-orange-900 dark:text-orvian-orange tracking-tight">
                    Asistencia pendiente
                </h4>
                <p class="text-xs text-orange-800/80 dark:text-gray-400 mt-0.5 font-medium">
                    No se ha abierto la asistencia del día. Inicia la sesión en la tanda correspondiente para comenzar el registro.
                </p>
            </div>

            {{-- Detalle decorativo a la derecha --}}
            <div class="ml-auto hidden sm:block">
                <span class="text-[10px] font-black uppercase tracking-widest text-orange-300 dark:text-orvian-orange/30">
                    Acción Requerida
                </span>
            </div>
        </div>
    @endif

{{-- Grid de Tandas Inteligente --}}
@php
    $shiftCount = count($this->shifts);
    $gridConfig = match($shiftCount) {
        1 => 'md:grid-cols-1 max-w-2xl mx-auto',
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        default => 'md:grid-cols-2',
    };
@endphp

<div 
    @if(!$selectedSession) wire:poll.10s @endif 
    class="grid grid-cols-1 {{ $gridConfig }} gap-8 transition-all duration-500"
>        
    @foreach($this->shifts as $shift)
        @php 
            $session = $this->dailySessions->get($shift->id);
            $isOpen = $session && !$session->closed_at;
            
            // Configuración de Iconos y Colores según Tanda
            $shiftType = strtoupper($shift->type);
            
            $iconName = match($shiftType) {
                'MATUTINA'   => 'sun',
                'VESPERTINA' => 'circle-stack',
                'EXTENDIDA'  => 'clock',
                'NOCTURNA'   => 'moon',
                default      => 'calendar'
            };

            $iconColor = match($shiftType) {
                'MATUTINA'   => 'text-yellow-500',
                'VESPERTINA' => 'text-orange-500',
                'EXTENDIDA'  => 'text-sky-500',
                'NOCTURNA'   => 'text-indigo-400',
                default      => 'text-orvian-orange'
            };

            // Color para el anillo de progreso (opcional, para mayor coherencia visual)
            $progressColor = match($shiftType) {
                'MATUTINA'   => '#EAB308', // yellow-500
                'VESPERTINA' => '#F97316', // orange-500
                'EXTENDIDA'  => '#0EA5E9', // sky-500
                'NOCTURNA'   => '#818CF8', // indigo-400
                default      => '#FF7300'  // orvian-orange (ajusta a tu hex)
            };

            $percentage = $session ? ($session->total_registered / max($session->total_expected, 1)) * 100 : 0;
        @endphp

        <div class="relative group overflow-hidden bg-white dark:bg-dark-card border border-gray-100 dark:border-dark-border rounded-[2rem] transition-all duration-300 hover:shadow-2xl hover:shadow-orvian-orange/5 {{ $isOpen ? 'ring-2 ring-orvian-orange/50 border-orvian-orange/30' : '' }}">
            
            <div class="p-8">
                {{-- Header de la Card --}}
                <div class="flex justify-between items-start mb-8">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            @if($isOpen)
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orvian-orange opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-orvian-orange"></span>
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-orvian-orange">Sesión Activa</span>
                            @else
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                                    {{ $session ? 'Finalizado' : 'Pendiente de Inicio' }}
                                </span>
                            @endif
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight leading-none">
                            {{ $shift->type }}
                        </h3>
                    </div>
                    
                    {{-- Icono Dinámico con Color --}}
                    <div class="p-3 bg-gray-50 dark:bg-white/5 rounded-2xl {{ $iconColor }}">
                        <x-dynamic-component 
                            :component="'heroicon-s-' . $iconName" 
                            class="w-6 h-6 flex-shrink-0" 
                        />
                    </div>
                </div>

                @if($session)
                    {{-- Información de Progreso --}}
                    <div class="flex items-center gap-6 mb-8">
                        <div class="relative flex items-center justify-center">
                            <svg class="w-16 h-16 transform -rotate-90">
                                <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="5" fill="transparent" class="text-gray-100 dark:text-dark-border" />
                                <circle cx="32" cy="32" r="28" stroke="{{ $progressColor }}" stroke-width="5" fill="transparent" 
                                    stroke-dasharray="{{ 2 * pi() * 28 }}" 
                                    stroke-dashoffset="{{ (2 * pi() * 28) * (1 - $percentage / 100) }}" 
                                    class="transition-all duration-1000" />
                            </svg>
                            <span class="absolute text-xs font-bold dark:text-white">{{ round($percentage) }}%</span>
                        </div>

                        <div class="flex-1">
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-bold dark:text-white">{{ $session->total_registered }}</span>
                                <span class="text-gray-400 dark:text-gray-500 text-sm">/ {{ $session->total_expected }}</span>
                            </div>
                            <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estudiantes Presentes</p>
                        </div>
                    </div>

                    {{-- Acciones (Mantengo tu lógica original) --}}
                    <div class="grid grid-cols-1 gap-3">
                        @if($isOpen)
                            {{-- Botón Principal: Pase de Lista (Polimorfismo <a>) --}}
                            <x-ui.button 
                                href="{{ route('app.attendance.scanner') }}" 
                                variant="primary" 
                                size="md"
                                fullWidth
                                hoverEffect
                                iconLeft="heroicon-s-clipboard-document-list"
                            >
                                Realizar Pase de Lista
                            </x-ui.button>
                            
                            <div class="grid grid-cols-1"> {{-- Cambiado a 1 columna ya que eliminamos el botón redundante --}}
                                {{-- Botón Secundario: Cerrar Sesión (Polimorfismo <button>) --}}
                                <x-ui.button 
                                    wire:click="confirmCloseSession({{ $session->id }})" 
                                    variant="secondary" 
                                    type="outline"
                                    size="md"
                                    fullWidth
                                >
                                    Cerrar Sesión
                                </x-ui.button>
                            </div>
                        @else
                            {{-- Estado Cerrado: Mantenemos el estilo de badge/info --}}
                            <div class="py-4 bg-gray-50 dark:bg-white/5 rounded-2xl border border-dashed border-gray-200 dark:border-white/10 text-center">
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-widest">
                                    {{ $session->total_present }} Presentes • {{ $session->total_absent }} Ausentes
                                </span>
                            </div>

                            <x-ui.button 
                                href="{{ route('app.attendance.hub', ['shift' => $session->school_shift_id]) }}" 
                                variant="secondary"
                                type="outline"
                                size="md"
                                fullWidth
                                iconLeft="heroicon-s-eye"
                            >
                                Ver Resumen en Hub
                            </x-ui.button>
                        @endif
                    </div>
                @else
                    {{-- Estado Pendiente --}}
                    <div class="mt-4 flex flex-col items-center justify-center space-y-6">
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">Programada: {{ $shift->start_time->format('h:i A') }}</p>
                        <button wire:click="openSession({{ $shift->id }})" 
                                class="w-full py-4 bg-gray-900 dark:bg-white dark:text-black text-white text-sm font-black rounded-2xl hover:scale-[1.02] transition-all">
                            ABRIR TANDA <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>

    {{-- Modal de Confirmación de Cierre (Estilo Breeze) --}}
    <x-modal name="confirm-session-close" focusable>
        <div class="p-6">
            @if($selectedSession)
                <div class="flex items-center gap-4 mb-6">
                    <div class="p-3 bg-orange-100 dark:bg-orvian-orange/20 rounded-2xl text-orvian-orange">
                        <x-heroicon-s-lock-closed class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            Cerrar Sesión: {{ $selectedSession->shift->type }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                            Confirma el balance final de asistencia antes de finalizar la jornada.
                        </p>
                    </div>
                </div>

                {{-- Grid de Estadísticas --}}
                <div class="grid grid-cols-2 gap-3 mb-4"> {{-- Bajamos el margen inferior de 8 a 4 --}}
                    <div class="px-4 py-4 rounded-[1.5rem] bg-green-50 dark:bg-green-900/15 border border-green-100 dark:border-green-800/30 text-center">
                        <p class="text-2xl font-black text-green-700 dark:text-green-400">
                            {{ $selectedSession->total_present }}
                        </p>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-green-600/80 dark:text-green-500">
                            Presentes
                        </p>
                    </div>
                    <div class="px-4 py-4 rounded-[1.5rem] bg-amber-50 dark:bg-amber-900/15 border border-amber-100 dark:border-amber-800/30 text-center">
                        <p class="text-2xl font-black text-amber-700 dark:text-amber-400">
                            {{ $selectedSession->total_late }}
                        </p>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-amber-600/80 dark:text-amber-500">
                            Tardanzas
                        </p>
                    </div>
                    <div class="px-4 py-4 rounded-[1.5rem] bg-red-50 dark:bg-red-900/15 border border-red-100 dark:border-red-800/30 text-center">
                        <p class="text-2xl font-black text-red-700 dark:text-red-400">
                            {{ $selectedSession->total_absent }}
                        </p>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-red-600/80 dark:text-red-500">
                            Ausentes
                        </p>
                    </div>
                    <div class="px-4 py-4 rounded-[1.5rem] bg-blue-50 dark:bg-blue-900/15 border border-blue-100 dark:border-blue-800/30 text-center">
                        <p class="text-2xl font-black text-blue-700 dark:text-blue-400">
                            {{ $selectedSession->total_excused }}
                        </p>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-blue-600/80 dark:text-blue-500">
                            Excusados
                        </p>
                    </div>
                </div>

                {{-- CARD INFORMATIVO DE CIERRE AUTOMÁTICO --}}
                <div class="mb-8 p-4 rounded-[1.5rem] bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10 flex gap-4 items-start text-left">
                    <div class="mt-1 flex-shrink-0 text-blue-500 dark:text-blue-400">
                        <x-heroicon-s-information-circle class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-1">
                            Aviso de procesamiento
                        </p>
                        <p class="text-[11px] leading-relaxed text-gray-500 dark:text-gray-400 font-medium">
                            Al finalizar la sesión, el sistema detectará automáticamente a los estudiantes sin registros y les asignará el estado de <span class="text-red-600 dark:text-red-400 font-bold italic">Ausente</span> (o <span class="text-blue-600 dark:text-blue-400 font-bold italic">Excusado</span> si existe una licencia aprobada).
                        </p>
                    </div>
                </div>

                {{-- Acciones del Modal --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    <button x-on:click="$dispatch('close-modal', 'confirm-session-close')" 
                            class="flex-1 px-6 py-3 bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400 text-sm font-bold rounded-2xl hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="closeSession" 
                            wire:loading.attr="disabled"
                            class="flex-1 px-6 py-3 bg-orvian-orange text-white text-sm font-black rounded-2xl hover:bg-orvian-orange-hover transition-all shadow-lg shadow-orvian-orange/25">
                        <span wire:loading.remove>FINALIZAR SESIÓN</span>
                        <span wire:loading>PROCESANDO...</span>
                    </button>
                </div>
            @endif
        </div>
    </x-modal>
</div>