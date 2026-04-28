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
                        ID: {{ $student->rnc ?? 'N/A' }}
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
                    // Usamos (int) para forzar el número entero o floor()
                    'value' => (int) $student->created_at->diffInDays(now()) . ' d', 
                    'icon' => 'heroicon-o-clock', 
                    'color' => 'text-green-500'
                ],
                [
                    'title' => 'Asistencia (30d)', 
                    'value' => '96%', 
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
                                        iconLeft="heroicon-o-user-minus"
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
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight text-sm">Historial de Asistencia</h3>
                        <x-ui.button variant="secondary" size="xs">Descargar Reporte</x-ui.button>
                    </div>
                    <div class="h-48 flex items-end gap-2 px-4 mb-4">
                        {{-- Simulación de mini gráfico --}}
                        @foreach(range(1, 15) as $i)
                            <div class="flex-1 bg-orvian-orange/20 dark:bg-orvian-orange/10 rounded-t-sm hover:bg-orvian-orange transition-colors cursor-help" 
                                 style="height: {{ rand(40, 100) }}%" title="Día {{ $i }}: Presente"></div>
                        @endforeach
                    </div>
                    <p class="text-center text-xs text-slate-400">Asistencia diaria de los últimos 15 días lectivos</p>
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