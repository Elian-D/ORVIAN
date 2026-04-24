{{-- resources/views/livewire/app/teachers/teacher-show.blade.php --}}
<div class="max-w-7xl mx-auto p-4 md:p-6 lg:p-8 space-y-6">
    
    {{-- Header Principal --}}
    <div class="relative bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border p-6 shadow-sm overflow-hidden">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-orvian-orange/5 rounded-full blur-3xl"></div>
        
        <div class="relative flex flex-col md:flex-row items-center md:items-start gap-6">
            
            {{-- Componente Genérico de Avatar (Ajusta si se llama distinto) --}}
            {{--<x-ui.person-avatar :person="$teacher" size="xl" /> --}}
            <x-ui.avatar :person="$teacher" size="xl" />

            <div class="flex-1 text-center md:text-left space-y-3">
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $teacher->full_name }}
                    </h1>
                    
                    {{-- Badge Estado --}}
                    <span @class([
                        "px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border",
                        "bg-green-100 text-green-700 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20" => $teacher->is_active,
                        "bg-red-100 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20" => !$teacher->is_active,
                    ])>
                        {{ $teacher->is_active ? 'Activo' : 'Inactivo' }}
                    </span>

                    {{-- Badge Tipo Contrato (Match PHP 8) --}}
                    @php
                        $badgeConfig = match($teacher->employment_type) {
                            'Full-Time'  => ['variant' => 'info', 'label' => 'T. Completo'],
                            'Part-Time'  => ['variant' => 'warning', 'label' => 'T. Parcial'],
                            'Substitute' => ['variant' => 'secondary', 'label' => 'Suplente'],
                            default      => ['variant' => 'ghost', 'label' => $teacher->employment_type ?? 'N/A'],
                        };
                    @endphp
                    <x-ui.badge :variant="$badgeConfig['variant']" size="sm">
                        {{ $badgeConfig['label'] }}
                    </x-ui.badge>
                </div>
                
                <div class="flex flex-wrap justify-center md:justify-start items-center gap-x-4 gap-y-1 text-slate-500 dark:text-slate-400 font-medium">
                    @if($teacher->specialization)
                        <span class="flex items-center gap-1.5">
                            <x-heroicon-s-star class="w-4 h-4 text-orvian-orange" />
                            {{ $teacher->specialization }}
                        </span>
                        <span class="hidden md:block text-slate-300">|</span>
                    @endif
                    <span class="flex items-center gap-1.5">
                        <x-heroicon-s-phone class="w-4 h-4 text-slate-400" />
                        {{ $teacher->phone ?? 'Sin teléfono' }}
                    </span>
                </div>
            </div>

            {{-- Acciones Rápidas --}}
            <div class="flex flex-col justify-center gap-2">
                <x-ui.button variant="primary" size="md" iconLeft="heroicon-o-pencil-square" href="{{ route('app.academic.teachers.edit', $teacher) }}">
                    Editar Perfil
                </x-ui.button>
                <x-ui.button variant="secondary" size="md" iconLeft="heroicon-o-book-open" href="{{ route('app.academic.teachers.assignments', $teacher) }}"> {{-- Cambiar a route('teachers.assignments', $teacher) --}}
                    Gestionar Asig.
                </x-ui.button>
            </div>
        </div>
    </div>

    {{-- Grid de Stats Rápidas --}}
    <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-6">
        @php
            $stats = [
                [
                    'title' => 'Fecha Contratación', 
                    'value' => $teacher->hire_date?->format('d/m/Y') ?? 'N/A', 
                    'icon' => 'heroicon-o-calendar', 
                    'color' => 'text-orvian-orange'
                ],
                [
                    'title' => 'Antigüedad', 
                    'value' => $teacher->hire_date 
                        ? round($teacher->hire_date->diffInYears(now(), false), 1) . ' años' 
                        : 'N/A',
                    'icon' => 'heroicon-o-clock', 
                    'color' => 'text-green-500'
                ],
                [
                    'title' => 'Asignaciones Activas', 
                    'value' => $teacher->assignments->where('is_active', true)->count(), 
                    'icon' => 'heroicon-o-academic-cap', 
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
            @foreach(['perfil' => 'Datos Generales', 'asignaciones' => 'Asignaciones', 'historial' => 'Historial'] as $key => $label)
                <button @click="tab = '{{ $key }}'" 
                    :class="tab === '{{ $key }}' ? 'bg-white dark:bg-slate-800 text-orvian-navy dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-5 py-2 text-xs font-bold rounded-xl transition-all whitespace-nowrap">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Contenido de Tabs --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {{-- Columna Principal --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- TAB: PERFIL --}}
                <div x-show="tab === 'perfil'" x-cloak x-transition class="space-y-6">
                    
                    {{-- Información Personal --}}
                    <div class="bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border overflow-hidden">
                        <div class="p-6 border-b border-slate-100 dark:border-dark-border flex justify-between items-center">
                            <h3 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight text-sm">Información Personal</h3>
                        </div>
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
                            <x-admin.info-item label="Nombres" :value="$teacher->first_name" icon="heroicon-o-user" />
                            <x-admin.info-item label="Código" :value="$teacher->employee_code ?? 'N/A'" icon="heroicon-o-map-pin" />
                            <x-admin.info-item label="Apellidos" :value="$teacher->last_name" icon="heroicon-o-identification" />
                            <x-admin.info-item label="Cédula / RNC" :value="$teacher->rnc ?? 'No registrada'" icon="heroicon-o-hashtag" />
                            <x-admin.info-item label="Género" :value="$teacher->gender === 'M' ? 'Masculino' : 'Femenino'" icon="heroicon-o-user-group" />
                            <x-admin.info-item label="Fecha de Nacimiento" :value="$teacher->date_of_birth?->format('d \d\e F, Y') ?? 'No registrada'" icon="heroicon-o-calendar-days" />
                        </div>
                    </div>

                    {{-- Gestión de Credenciales (Solo si tiene usuario) --}}
                    @if($teacher->user_id)
                        <div class="bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border overflow-hidden">
                            <div class="p-6 border-b border-slate-100 dark:border-dark-border">
                                <h3 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight text-sm">Acceso al Sistema</h3>
                                <p class="text-xs text-slate-500 mt-1">Modifica el correo electrónico de acceso o restablece la contraseña del maestro.</p>
                            </div>
                            
                            <form wire:submit.prevent="updateCredentials" class="p-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                                    <x-ui.forms.input 
                                        label="Correo Electrónico de Acceso"
                                        name="email"
                                        type="email"
                                        wire:model="email"
                                        icon-left="heroicon-o-envelope"
                                        :error="$errors->first('email')"
                                    />

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
                                        @error('password') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <x-ui.button type="solid" variant="primary" size="md" iconLeft="heroicon-o-key" wire:click="updateCredentials" wire:loading.attr="disabled">
                                        Actualizar Credenciales
                                    </x-ui.button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center">
                            <div class="mx-auto w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-full flex items-center justify-center mb-3">
                                <x-heroicon-o-user-minus class="w-6 h-6 text-slate-400" />
                            </div>
                            <h3 class="font-bold text-slate-700 dark:text-slate-300">Sin acceso al sistema</h3>
                            <p class="text-sm text-slate-500 mt-1">Este maestro no tiene una cuenta de usuario vinculada. Puedes crearle una desde la opción de "Editar Perfil".</p>
                        </div>
                    @endif
                </div>

                {{-- TAB: ASIGNACIONES --}}
                <div x-show="tab === 'asignaciones'" x-cloak x-transition class="space-y-6">
                    <div class="bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border overflow-hidden p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight text-sm">Carga Académica Actual</h3>
                                <p class="text-xs text-slate-500 mt-1">Materias y secciones asignadas al docente.</p>
                            </div>
                            <x-ui.button variant="secondary" size="xs" :href="route('app.academic.teachers.assignments', $teacher)" iconLeft="heroicon-o-plus">Nueva Asignación</x-ui.button>
                        </div>

                        @if($teacher->assignments->isEmpty())
                            <div class="text-center py-10 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700">
                                <x-heroicon-o-book-open class="w-10 h-10 text-slate-300 mx-auto mb-3" />
                                <p class="text-sm text-slate-500">Este maestro aún no tiene materias asignadas.</p>
                            </div>
                        @else
                            <div class="space-y-6">
                                {{-- Agrupar por Sección --}}
                                @php
                                    $groupedAssignments = $teacher->assignments->groupBy('school_section_id');
                                @endphp

                                @foreach($groupedAssignments as $sectionId => $assignments)
                                    @php $section = $assignments->first()->section; @endphp
                                    
                                    <div class="border border-slate-100 dark:border-dark-border rounded-2xl overflow-hidden">
                                        <div class="bg-slate-50 dark:bg-slate-800/50 px-4 py-3 border-b border-slate-100 dark:border-dark-border flex items-center gap-2">
                                            <x-heroicon-s-academic-cap class="w-5 h-5 text-slate-400" />
                                            <h4 class="font-bold text-sm text-slate-700 dark:text-slate-300">{{ $section->full_label ?? 'Sección Desconocida' }}</h4>
                                        </div>
                                        <div class="p-4">
                                            <div class="flex flex-wrap gap-3">
                                                @foreach($assignments as $assignment)
                                                    <div class="flex items-center gap-2 bg-white dark:bg-dark-bg border border-slate-200 dark:border-slate-700 px-3 py-2 rounded-xl shadow-sm">
                                                        {{-- Se asume que $assignment->subject tiene una propiedad 'color' (ej. '#3b82f6') o usa uno por defecto --}}
                                                        <div class="w-3 h-3 rounded-full bg-orvian-orange"></div> 
                                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                            {{ $assignment->subject->name ?? 'Materia' }}
                                                        </span>
                                                        @if(!$assignment->is_active)
                                                            <span class="text-[10px] text-red-500 bg-red-50 px-1.5 py-0.5 rounded font-bold">Inactiva</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- TAB: HISTORIAL --}}
                <div x-show="tab === 'historial'" x-cloak x-transition class="bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border p-12 text-center">
                    <div class="mx-auto w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mb-4 transform rotate-3">
                        <x-heroicon-o-clock class="w-8 h-8 text-slate-400" />
                    </div>
                    <h3 class="font-bold text-slate-800 dark:text-slate-200 text-lg">Historial y Asistencias</h3>
                    <p class="text-sm text-slate-500 max-w-sm mx-auto mt-2">
                        El historial de asistencias registradas, llegadas tarde y reportes disciplinarios de este maestro estará disponible próximamente en la fase 2.
                    </p>
                </div>

            </div>

            {{-- Columna Derecha --}}
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white dark:bg-dark-card rounded-3xl border border-slate-200 dark:border-dark-border p-6 text-center shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-6">Credencial Digital</p>
                    
                    {{-- Contenedor del QR (Sin scale) --}}
                    <div class="inline-block p-4 bg-white rounded-[2rem] shadow-xl border border-slate-100 mb-6 transition-all duration-300">
                        {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)
                            ->color(30, 41, 59)
                            ->margin(1)
                            ->generate($teacher->qr_code) !!}
                    </div>

                    {{-- Botón de Descarga PNG --}}
                    <div class="flex justify-center">
                        <x-ui.button 
                            variant="secondary" 
                            type="outline"
                            size="sm"
                            iconLeft="heroicon-o-arrow-down-tray"
                            {{-- Generamos el PNG en base64 para descarga inmediata --}}
                            href="data:image/png;base64, {!! base64_encode(SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(300)->margin(1)->generate($teacher->qr_code)) !!}"
                            download="QR_{{ Str::slug($teacher->full_name) }}.png"
                        >
                            Descargar QR
                        </x-ui.button>
                    </div>
                </div>            
            </div>
        </div>
    </div>
</div>