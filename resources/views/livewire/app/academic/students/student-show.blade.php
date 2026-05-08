<div class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8 space-y-6">
    
    {{-- Header Principal --}}
    <div class="relative bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border p-6 shadow-sm overflow-hidden">
        {{-- Decoración de fondo sutil --}}
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-orvian-orange/5 rounded-full blur-3xl"></div>
        
        <div class="relative flex flex-col md:flex-row items-center md:items-start gap-6">
            {{-- Avatar XL con el componente que proporcionaste --}}
            <x-ui.student-avatar :student="$student" size="xl" :showQr="true" />

            <div class="flex-1 text-center md:text-left space-y-2">
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $student->full_name }}
                    </h1>
                    <span @class([
                        "px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border",
                        "bg-green-100 text-green-700 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20" => $student->is_active,
                        "bg-red-100 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20" => !$student->is_active,
                    ])>
                        {{ $student->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                
                <div class="flex flex-wrap justify-center md:justify-start items-center gap-x-4 gap-y-1 text-slate-500 dark:text-slate-400 font-medium">
                    <span class="flex items-center gap-1.5">
                        <x-heroicon-s-hashtag class="w-4 h-4 text-orvian-orange" />
                        CED: {{ $student->rnc ?? 'N/A' }}
                    </span>
                    <span class="hidden md:block text-slate-300">|</span>
                    <span class="flex items-center gap-1.5">
                        <x-heroicon-s-academic-cap class="w-4 h-4 text-orvian-orange" />
                        {{ $student->section?->full_label ?? 'Sin Grado' }} 
                    </span>
                </div>
            </div>

            {{-- Acciones Rápidas --}}
            <div class="flex flex-col justify-center gap-2">
                @can('students.edit')
                    <x-ui.button variant="primary" size="md" iconLeft="heroicon-o-pencil-square"  href="{{ route('app.academic.students.edit', $student) }}" >
                        Editar Perfil
                    </x-ui.button>
                @endcan
                <x-ui.button variant="secondary" size="md" type="ghost" href="{{ route('app.academic.students.index') }}">
                    Volver
                </x-ui.button>
            </div>
        </div>
    </div>
    {{-- Grid de Stats Rápidas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @php
            // Obtenemos el resumen de la propiedad computada del componente
            $plantel = $this->plantelAttendanceSummary;
            $asistenciaValue = $plantel['rate'] !== null ? $plantel['rate'] . '%' : '---';
            
            $stats = [
                [
                    'title' => 'Edad Actual', 
                    'value' => ($student->age ?? '0') . ' años', 
                    'icon' => 'heroicon-o-user', 
                    'color' => 'text-blue-500'
                ],
                [
                    'title' => 'Inscrito desde', 
                    'value' => $student->created_at->format('d/m/Y'), 
                    'icon' => 'heroicon-o-calendar', 
                    'color' => 'text-orvian-orange'
                ],
                [
                    'title' => 'Días en Plantel', 
                    'value' => (int) $student->created_at->diffInDays(now()) . ' d', 
                    'icon' => 'heroicon-o-clock', 
                    'color' => 'text-green-500'
                ],
                [
                    // Hacemos el título dinámico para que coincida con el filtro seleccionado
                    'title' => "Asistencia ({$attendancePeriod}d)", 
                    'value' => $asistenciaValue, 
                    'icon' => 'heroicon-o-chart-bar', 
                    'color' => 'text-purple-500',
                ],
            ];
        @endphp

        @foreach($stats as $stat)
            <x-admin.stat-card 
                :title="$stat['title']" 
                :value="$stat['value']" 
                :icon="$stat['icon']" 
                :color="$stat['color']"
            />
        @endforeach
    </div>
    {{-- Sistema de Tabs Alpine.js --}}
    <div x-data="{ tab: @entangle('activeTab') }" class="space-y-6">
        {{-- Navegación de Tabs --}}
        <div class="flex items-center gap-1 p-1 bg-slate-200/50 dark:bg-dark-border/50 rounded-2xl w-full md:w-max overflow-x-auto">
            @foreach(['perfil' => 'Datos Generales', 'asistencia' => 'Asistencia', 'academico' => 'Académico', 'medico' => 'Ficha Médica'] as $key => $label)
                <button @click="tab = '{{ $key }}'" 
                    :class="tab === '{{ $key }}' ? 'bg-white dark:bg-slate-800 text-orvian-navy dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-5 py-2 text-xs font-bold rounded-xl transition-all whitespace-nowrap">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Contenido de Tabs --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {{-- Columna Izquierda: Información Principal --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- TAB: PERFIL --}}
                <div x-show="tab === 'perfil'" x-transition class="space-y-6">
                    
                    {{-- Información Personal (Lectura) --}}
                    <div class="bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border overflow-hidden">
                        <div class="p-6 border-b border-slate-100 dark:border-dark-border flex justify-between items-center">
                            <h3 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight text-sm">Información Personal</h3>
                        </div>
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
                            <x-admin.info-item label="Nombres" :value="$student->first_name" icon="heroicon-o-user" />
                            <x-admin.info-item label="Apellidos" :value="$student->last_name" icon="heroicon-o-identification" />
                            <x-admin.info-item label="Fecha de Nacimiento" :value="$student->date_of_birth?->format('d \d\e F, Y')" icon="heroicon-o-calendar-days" />
                            <x-admin.info-item label="Género" :value="$student->gender === 'M' ? 'Masculino' : 'Femenino'" icon="heroicon-o-user-group" />
                            <x-admin.info-item label="Tipo de Sangre" :value="$student->blood_type" icon="heroicon-o-beaker" />
                            <x-admin.info-item label="Dirección" :value="$student->address ?? 'No especificada'" icon="heroicon-o-map-pin" />
                        </div>
                    </div>
                    
                    {{-- Sección: Información del Tutor --}}
                    <div class="mt-8">
                        <h4 class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-4">
                            Tutor / Responsable
                        </h4>

                        {{-- Grid: 1 columna en móvil (default), 2 columnas en sm+ --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            {{-- Nombre del tutor --}}
                            <div class="p-4 bg-slate-50 dark:bg-dark-card rounded-xl">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                                    Nombre
                                </p>
                                <p class="text-sm font-semibold text-slate-700 dark:text-white">
                                    {{ $student->tutor_name ?? '—' }}
                                </p>
                            </div>

                            {{-- Teléfono del tutor --}}
                            <div class="p-4 bg-slate-50 dark:bg-dark-card rounded-xl">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        WhatsApp
                                    </p>
                                    @if($student->tutor_phone)
                                        <x-ui.badge variant="success" size="sm">Activo para alertas</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="warning" size="sm">Sin número</x-ui.badge>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-white font-mono">
                                        {{ $student->tutor_phone ?? 'No registrado' }}
                                    </p>

                                    @if($student->tutor_phone)
                                        {{-- Botón para enviar mensaje --}}
                                        <x-ui.button 
                                            href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $student->tutor_phone) }}" 
                                            target="_blank"
                                            variant="success" 
                                            size="sm" 
                                            iconLeft="heroicon-s-chat-bubble-left-right"
                                        >
                                            Escribir
                                        </x-ui.button>
                                    @endif
                                </div>

                                @if(!$student->tutor_phone)
                                    <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-2">
                                        ⚠ Sin número de tutor, las alertas automáticas de asistencia no se enviarán.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    @can('students.edit')
                        {{-- Gestión de Credenciales (Formulario) --}}
                        <div class="bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border overflow-hidden">
                            <div class="p-6 border-b border-slate-100 dark:border-dark-border">
                                <h3 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight text-sm">Seguridad y Acceso</h3>
                                <p class="text-xs text-slate-500 mt-1">Cambia el correo o restablece la contraseña del estudiante si es necesario.</p>
                            </div>
                            
                            <form wire:submit.prevent="updateCredentials" class="p-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                                    {{-- Input Email --}}
                                    <x-ui.forms.input
                                        label="Correo Electrónico de Acceso"
                                        name="email"
                                        type="email"
                                        wire:model="email"
                                        icon-left="heroicon-o-envelope"
                                        hint="Generado automáticamente. No es editable."
                                        readonly
                                    />

                                    {{-- Input Password con Toggle de Visibilidad manual --}}
                                    <div x-data="{ show: false }" class="flex flex-col group">
                                        <label class="text-[11px] font-bold uppercase tracking-wider mb-2 text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                                            Nueva Contraseña (Opcional)
                                        </label>
                                        <div class="relative flex items-center">
                                            <span class="absolute left-0 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                                                <x-heroicon-o-lock-closed class="w-5 h-5" />
                                            </span>
                                            <input 
                                                :type="show ? 'text' : 'password'" 
                                                wire:model="password"
                                                placeholder="Dejar en blanco para no cambiar"
                                                class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent pl-7 pr-8 py-3 text-sm focus:ring-0 focus:border-orvian-orange transition-colors"
                                            />
                                            <button type="button" @click="show = !show" class="absolute right-0 text-slate-400 hover:text-orvian-orange">
                                                <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                                                <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" />
                                            </button>
                                        </div>
                                        @error('password') <span class="text-xs text-state-error mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <x-ui.button 
                                        type="solid" 
                                        variant="primary" 
                                        size="md" 
                                        iconLeft="heroicon-o-user-plus"
                                        wire:click="updateCredentials" {{-- Llamada directa en lugar de submit --}}
                                        wire:loading.attr="disabled"
                                    >
                                        Actualizar Credenciales
                                    </x-ui.button>
                                </div>
                            </form>
                        </div>
                    @endcan
                </div>

                {{-- TAB: ASISTENCIA --}}
                <div x-show="tab === 'asistencia'" x-transition class="bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border p-6">

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-chart-bar class="w-4 h-4 text-slate-400" />
                            <h4 class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                                Asistencia Histórica
                            </h4>
                        </div>

                        {{-- Selector de período (Segmented Control) --}}
                        <div class="flex p-1 bg-slate-100 dark:bg-white/5 rounded-xl w-fit">
                            @foreach(['7' => '7D', '30' => '30D', '90' => '90D'] as $val => $label)
                                <button 
                                    wire:click="$set('attendancePeriod', '{{ $val }}')"
                                    class="px-4 py-1.5 text-[10px] font-bold rounded-lg transition-all duration-200 
                                        {{ $attendancePeriod === $val
                                            ? 'bg-white dark:bg-white/10 text-orvian-orange shadow-sm ring-1 ring-black/5'
                                            : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        {{-- Resumen Plantel --}}
                        @php $plantel = $this->plantelAttendanceSummary; @endphp
                        <div class="p-5 bg-slate-50/50 dark:bg-dark-card/50 border border-slate-100 dark:border-white/5 rounded-2xl">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 bg-blue-50 dark:bg-blue-500/10 rounded-lg">
                                        <x-heroicon-s-building-library class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Entrada al Plantel</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-lg font-black text-slate-900 dark:text-white">
                                        {{ $plantel['rate'] !== null ? $plantel['rate'] . '%' : '---' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Barra de progreso --}}
                            <div class="w-full h-2 bg-slate-200 dark:bg-white/10 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700 ease-out
                                    {{ ($plantel['rate'] ?? 0) >= 85 ? 'bg-green-500' : (($plantel['rate'] ?? 0) >= 70 ? 'bg-amber-400' : 'bg-red-500') }}"
                                    style="width: {{ $plantel['rate'] ?? 0 }}%">
                                </div>
                            </div>

                            {{-- Leyendas con Iconos --}}
                            <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-y-3 gap-x-6 mt-4">
                                <div class="flex items-center gap-1.5">
                                    <x-heroicon-s-check-circle class="w-3.5 h-3.5 text-green-500" />
                                    <span class="text-[10px] font-medium text-slate-500">{{ $plantel['present'] }} Presentes</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <x-heroicon-s-clock class="w-3.5 h-3.5 text-amber-500" />
                                    <span class="text-[10px] font-medium text-slate-500">{{ $plantel['late'] }} Tardanzas</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <x-heroicon-s-x-circle class="w-3.5 h-3.5 text-red-500" />
                                    <span class="text-[10px] font-medium text-slate-500">{{ $plantel['absent'] }} Ausencias</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <x-heroicon-s-document-text class="w-3.5 h-3.5 text-blue-500" />
                                    <span class="text-[10px] font-medium text-slate-500">{{ $plantel['excused'] }} Justificadas</span>
                                </div>
                            </div>
                        </div>

                        {{-- Resumen Aula --}}
                        @php $classroom = $this->classroomAttendanceSummary; @endphp
                        <div class="p-5 bg-slate-50/50 dark:bg-dark-card/50 border border-slate-100 dark:border-white/5 rounded-2xl">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 bg-purple-50 dark:bg-purple-500/10 rounded-lg">
                                        <x-heroicon-s-academic-cap class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Asistencia a Clases</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-lg font-black text-slate-900 dark:text-white">
                                        {{ $classroom['rate'] !== null ? $classroom['rate'] . '%' : '---' }}
                                    </span>
                                </div>
                            </div>

                            <div class="w-full h-2 bg-slate-200 dark:bg-white/10 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700 ease-out
                                    {{ ($classroom['rate'] ?? 0) >= 85 ? 'bg-purple-500' : (($classroom['rate'] ?? 0) >= 70 ? 'bg-amber-400' : 'bg-red-500') }}"
                                    style="width: {{ $classroom['rate'] ?? 0 }}%">
                                </div>
                            </div>

                            <div class="flex gap-6 mt-4">
                                <div class="flex items-center gap-1.5">
                                    <x-heroicon-s-check-circle class="w-3.5 h-3.5 text-purple-500" />
                                    <span class="text-[10px] font-medium text-slate-500">{{ $classroom['present'] }} Presentes</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <x-heroicon-s-x-circle class="w-3.5 h-3.5 text-red-500" />
                                    <span class="text-[10px] font-medium text-slate-500">{{ $classroom['absent'] }} Ausencias</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- TAB: MÉDICO --}}
                <div x-show="tab === 'medico'" x-transition class="bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border p-6">
                    {{-- Alertas Críticas --}}
                    <div class="p-6 bg-red-500/5 border border-red-500/20 rounded-2xl mb-8">
                        <h4 class="text-red-500 font-bold text-xs uppercase mb-4 flex items-center gap-2">
                            <x-heroicon-s-exclamation-triangle class="w-4 h-4" />
                            Alertas y Condiciones Críticas
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-admin.info-item label="Tipo de Sangre" :value="$student->blood_type" icon="heroicon-o-beaker" />
                            <x-admin.info-item label="Alergias" :value="$student->allergies" icon="heroicon-o-no-symbol" />
                        </div>
                    </div>

                    {{-- Notas adicionales --}}
                    <div class="px-2">
                        <x-admin.info-item 
                            label="Notas Médicas Adicionales" 
                            :value="$student->medical_conditions ?? 'No se registran condiciones especiales.'" 
                            icon="heroicon-o-clipboard-document-list" 
                        />
                    </div>
                </div>

            </div>

            {{-- Columna Derecha: Widgets de Acción --}}
            <div class="lg:col-span-4 space-y-6">
                
                {{-- Widget de QR / Credencial Digital --}}
                <div class="bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border p-4 text-center shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-6">Credencial Digital</p>
                    
                    {{-- Contenedor del QR --}}
                    <div class="inline-block p-4 bg-white rounded-[2rem] shadow-xl border border-slate-100 mb-6 group hover:scale-105 transition-transform duration-300">
                        {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)
                            ->color(30, 41, 59)
                            ->margin(1)
                            ->generate($student->qr_code) !!}
                    </div>
                    
                    <x-ui.button variant="primary" type="ghost" size="md" iconLeft="heroicon-o-pencil-square"  href="{{ route('app.academic.students.print-manager', ['search' => $student->first_name]) }}" >
                        Descargar QR
                    </x-ui.button>
                </div>

                {{-- Card: Biometría --}}
                <section class="relative overflow-hidden p-6 transition-all duration-300 rounded-3xl bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-white shadow-xl shadow-slate-200 dark:shadow-none border border-slate-200 dark:border-slate-800">
                    
                    <div class="relative z-10 flex items-center gap-4">
                        {{-- Contenedor del Icono --}}
                        <div @class([
                            'p-3 rounded-2xl transition-colors duration-300',
                            // Estado: Verificado (Verde)
                            'bg-green-500 text-white shadow-lg shadow-green-200 dark:shadow-none' => $student?->has_face_encoding,
                            // Estado: Pendiente (Gris neutro adaptable)
                            'bg-slate-200 dark:bg-white/10 text-slate-400 dark:text-white/40' => !$student?->has_face_encoding,
                        ])>
                            <x-heroicon-s-finger-print class="w-8 h-8" />
                        </div>

                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-tight leading-none">Estatus Biométrico</h4>
                            <p class="text-[11px] mt-1 font-medium text-slate-500 dark:text-white/60">
                                {{ $student?->has_face_encoding ? 'Identidad verificada' : 'Pendiente de captura facial' }}
                            </p>
                        </div>
                    </div>
                    
                    {{-- Decoración de fondo (Marca de agua) --}}
                    <div class="absolute -right-4 -bottom-4 opacity-[0.05] dark:opacity-10 text-slate-900 dark:text-white">
                        <x-heroicon-s-finger-print class="w-24 h-24" />
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>