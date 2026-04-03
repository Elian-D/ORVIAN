{{-- PASO 3 — Configuración Académica --}}
<div x-show="$wire.step === 3" style="display:none;" class="space-y-8">

    {{-- ── Niveles Educativos ── --}}
    <div class="space-y-6">
        <div class="space-y-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                Estructura Académica <span class="text-state-error ml-0.5">*</span>
            </p>

            @php
                // Agrupamos los niveles por ciclos (Asumiendo que vienen ordenados del Seeder)
                // Ciclo 1: 1ro, 2do, 3ro | Ciclo 2: 4to, 5to, 6to
                $primerCiclo = $this->levels->filter(fn($l) => in_array($l->slug, ['primaria-primer-ciclo', 'secundaria-primer-ciclo']));
                $segundoCiclo = $this->levels->filter(fn($l) => in_array($l->slug, ['primaria-segundo-ciclo', 'secundaria-segundo-ciclo']));
                
                $secundariaId = $this->levels->first(fn($l) => $l->slug === 'secundaria-segundo-ciclo')?->id;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- BLOQUE: PRIMER CICLO --}}
                <div class="space-y-3">
                    <h4 class="text-[10px] font-black text-orvian-navy/40 dark:text-white/20 uppercase tracking-[0.15em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-orvian-orange/40"></span>
                        Primer Ciclo (1ro – 3ro)
                    </h4>
                    
                    <div class="space-y-2">
                        @foreach($primerCiclo as $level)
                            <x-wizard.level-card :level="$level" :selectedLevels="$selectedLevels" />
                        @endforeach
                    </div>
                </div>

                {{-- BLOQUE: SEGUNDO CICLO --}}
                <div class="space-y-3">
                    <h4 class="text-[10px] font-black text-orvian-navy/40 dark:text-white/20 uppercase tracking-[0.15em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-orvian-orange/40"></span>
                        Segundo Ciclo (4to – 6to)
                    </h4>
                    
                    <div class="space-y-2">
                        @foreach($segundoCiclo as $level)
                            <x-wizard.level-card :level="$level" :selectedLevels="$selectedLevels" />
                        @endforeach
                    </div>
                </div>
            </div>

            @error('selectedLevels') <p class="text-xs text-state-error mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Aviso de Modalidad Técnica --}}
        <div x-show="$wire.needsTitles && !$wire.selectedLevels.map(String).includes('{{ $secundariaId }}')"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2"
            style="display:none;"
            class="flex items-start gap-4 p-4 rounded-2xl bg-state-info/5 border border-state-info/20">
            <div class="w-8 h-8 rounded-full bg-state-info/10 flex items-center justify-center flex-shrink-0">
                <x-heroicon-s-information-circle class="w-5 h-5 text-state-info" />
            </div>
            <div>
                <p class="text-sm font-bold text-state-info">Requisito de Modalidad Técnica</p>
                <p class="text-xs text-state-info/80 mt-1 leading-relaxed">
                    Para impartir Títulos Técnicos es obligatorio seleccionar el nivel 
                    <span class="font-bold text-state-info underline decoration-2 underline-offset-2">Secundaria Segundo Ciclo</span>, 
                    ya que estos cursos corresponden a 4to, 5to y 6to del Bachillerato Técnico.
                </p>
            </div>
        </div>
    </div>

    {{-- ── Títulos Técnicos (condicional) ── --}}
    <div x-show="$wire.needsTitles" style="display:none;"
         class="space-y-4 p-5 rounded-2xl transition-all
            {{-- Borde de líneas (Dashed) --}}
            border border-dashed border-slate-300 dark:border-white/10 
            {{-- Gradiente Diferenciado (Emerald/Slate) --}}
            bg-gradient-to-br from-slate-50/50 via-slate-50/30 to-emerald-500/5 
            dark:from-white/[0.02] dark:via-transparent dark:to-emerald-500/10 shadow-sm">
        <div class="flex items-center gap-2">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Títulos Técnicos</p>
            <x-ui.badge variant="warning" size="sm" :dot="false">Requerido</x-ui.badge>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
            <x-ui.forms.select label="Familia Técnica" name="temp_family_id"
                wire:model.live="temp_family_id" icon-left="heroicon-o-squares-2x2">
                @foreach($this->families as $family)
                    <option value="{{ $family->id }}">{{ $family->name }}</option>
                @endforeach
            </x-ui.forms.select>
            <div class="{{ !$temp_family_id ? 'opacity-50 pointer-events-none' : '' }}">
                <x-ui.forms.select label="Título" name="temp_title_id" wire:model="temp_title_id"
                    :disabled="!$temp_family_id"
                    :hint="!$temp_family_id ? 'Selecciona primero la Familia' : ''">
                    @foreach($titleOptions as $title)
                        <option value="{{ $title['id'] }}">{{ $title['name'] }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>
        </div>
        <x-ui.button variant="primary" type="outline" size="sm"
            iconLeft="heroicon-s-plus" wire:click="addTitle"
            x-bind:disabled="!$wire.temp_title_id">
            Agregar título
        </x-ui.button>
        <div x-show="$wire.selectedTitles.length > 0" style="display:none;" class="space-y-2">
            @foreach($this->displayTitles as $title)
                <div class="flex items-center justify-between px-4 py-2.5 rounded-xl bg-emerald-500/5 border border-emerald-500/20">
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate">{{ $title->name }}</p>
                        <p class="text-[10px] text-slate-500">
                            {{ optional($title->family)->name }}
                            @if($title->code) · <span class="font-mono">{{ $title->code }}</span> @endif
                        </p>
                    </div>
                    <x-ui.button variant="error" type="outline" size="sm"
                        icon="heroicon-s-x-mark" wire:click="removeTitle({{ $title->id }})" />
                </div>
            @endforeach
        </div>
        @error('selectedTitles') <p class="text-xs text-state-error mt-1">{{ $message }}</p> @enderror
    </div>

        {{-- 2. Selección de Tandas (Con Validación de Exclusividad) --}}
    @php
        // Definimos el FQN de la clase para evitar errores de "Class not found"
        $shiftModel = \App\Models\Tenant\Academic\SchoolShift::class;
    @endphp

    <div class="space-y-4" 
        x-data="{
            selectedShifts: $wire.entangle('selectedShifts'),
            {{-- Usamos las constantes del modelo con comillas porque son strings en la BD --}}
            get hasExtended() { return this.selectedShifts.includes('{{ $shiftModel::TYPE_EXTENDED }}') },
            get hasMorning() { return this.selectedShifts.includes('{{ $shiftModel::TYPE_MORNING }}') },
            get hasAfternoon() { return this.selectedShifts.includes('{{ $shiftModel::TYPE_AFTERNOON }}') },
            
            get showConflictWarning() { 
                return this.hasExtended && (this.hasMorning || this.hasAfternoon);
            }
        }">
        
        <div class="flex items-center justify-between">
            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                Tandas / Jornadas <span class="text-state-error ml-0.5">*</span>
            </label>
            
            {{-- Advertencia de Conflicto en tiempo real con Alpine --}}
            <div x-show="showConflictWarning" 
                x-transition 
                style="display:none;"
                class="flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400 font-medium">
                <x-heroicon-s-exclamation-triangle class="w-4 h-4" />
                <span>Conflicto: La Jornada Extendida es exclusiva.</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- En BaseSchoolWizard, shifts() devuelve un array [id => label] --}}
            @foreach($this->shifts as $id => $label)
                @php
                    $meta = match($label) {
                        $shiftModel::TYPE_MORNING   => ['icon' => '☀️', 'hours' => '7:30 AM – 12:30 PM', 'conflict' => 'hasExtended'],
                        $shiftModel::TYPE_AFTERNOON => ['icon' => '🌤️', 'hours' => '1:30 PM – 6:00 PM',  'conflict' => 'hasExtended'],
                        $shiftModel::TYPE_EXTENDED  => ['icon' => '🌞', 'hours' => '8:00 AM – 4:00 PM',  'conflict' => 'hasMorning || hasAfternoon'],
                        $shiftModel::TYPE_NIGHT     => ['icon' => '🌙', 'hours' => '6:00 PM – 10:00 PM', 'conflict' => 'false'],
                        default => ['icon' => '🕐', 'hours' => '', 'conflict' => 'false'],
                    };
                @endphp

                <label 
                    class="relative flex flex-col items-center gap-2 p-4 rounded-2xl border-2 transition-all cursor-pointer select-none"
                    :class="{
                        'border-orvian-orange bg-orvian-orange/5 ring-1 ring-orvian-orange/20': selectedShifts.includes('{{ $id }}'),
                        'border-slate-200 dark:border-white/6 opacity-40 cursor-not-allowed': {{ $meta['conflict'] }},
                        'border-slate-200 dark:border-white/6 hover:border-slate-300': !selectedShifts.includes('{{ $id }}') && !({{ $meta['conflict'] }})
                    }">
                    
                    <input 
                        type="checkbox" 
                        wire:model.live="selectedShifts"
                        value="{{ $id }}"
                        class="hidden"
                        :disabled="{{ $meta['conflict'] }}"
                    >
                    
                    <span class="text-2xl">{{ $meta['icon'] }}</span>
                    <div class="text-center">
                        <p class="text-xs font-black text-gray-900 dark:text-white leading-tight">{{ $label }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $meta['hours'] }}</p>
                    </div>

                    {{-- Icono de Checkmark --}}
                    <div x-show="selectedShifts.includes('{{ $id }}')" class="absolute top-2 right-2">
                        <x-heroicon-s-check-circle class="w-5 h-5 text-orvian-orange" />
                    </div>
                </label>
            @endforeach
        </div>

        @error('selectedShifts')
            <p class="text-xs text-state-error mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- ── Secciones (Paralelos) ── --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                Secciones (Paralelos) <span class="text-state-error ml-0.5">*</span>
            </p>
            <span class="text-[10px] font-bold text-orvian-orange"
                  x-text="$wire.selectedSectionLabels.length + ' seleccionada(s)'"></span>
        </div>
        <p class="text-[10px] text-slate-500 leading-relaxed">
            Cada letra representa un paralelo por grado. Ej: <strong>A, B, C</strong> → 1roA, 1roB, 1roC en cada grado.
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach($this->availableSectionLabels() as $letter)
                <label @class([
                    'flex items-center justify-center w-10 h-10 rounded-xl border-2 cursor-pointer transition-all text-sm font-black select-none',
                    'border-orvian-orange bg-orvian-orange/10 text-orvian-orange shadow-[0_0_8px_rgba(247,137,4,0.2)]' => in_array($letter, $selectedSectionLabels),
                    'border-slate-200 dark:border-white/6 text-slate-400 hover:border-slate-300 dark:hover:border-white/15' => !in_array($letter, $selectedSectionLabels),
                ])>
                    <input type="checkbox" wire:model.live="selectedSectionLabels" value="{{ $letter }}" class="hidden" />
                    {{ $letter }}
                </label>
            @endforeach
        </div>

        {{-- Preview de Secciones a Crear --}}
        @php $estimation = $this->estimatedSections; @endphp

        @if($estimation['total'] > 0)
            <div class="p-4 bg-gradient-to-br from-slate-50 to-orvian-blue/5 dark:from-white/[0.02] dark:to-orvian-orange/5 border border-dashed border-slate-300 dark:border-white/10 rounded-2xl transition-all">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-orvian-orange/10 rounded-lg">
                            <x-heroicon-s-calculator class="w-5 h-5 text-orvian-orange" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200">Resumen de Estructura</h4>
                            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Cálculo basado en selección actual</p>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <span class="text-2xl font-black text-orvian-orange leading-none">
                            {{ $estimation['total'] }}
                        </span>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Secciones</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                    {{-- Detalle Académico --}}
                    <div class="p-2.5 rounded-xl bg-white/50 dark:bg-black/20 border border-slate-200 dark:border-white/5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Cursos Generales</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-lg font-bold text-slate-700 dark:text-slate-300">{{ $estimation['academic'] }}</span>
                            <span class="text-[10px] text-slate-500">secciones</span>
                        </div>
                    </div>

                    {{-- Detalle Técnico (Solo se muestra si hay títulos o modalidad técnica) --}}
                    @if($this->needsTitles)
                        <div class="p-2.5 rounded-xl bg-orvian-orange/[0.03] border border-orvian-orange/20">
                            <p class="text-[10px] font-bold text-orvian-orange/80 uppercase mb-1">Cursos Técnicos</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-lg font-bold text-orvian-orange">{{ $estimation['technical'] }}</span>
                                <span class="text-[10px] text-orvian-orange/60">secciones</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Explicación de la fórmula (Dinámica) --}}
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-white/5">
                    <p class="text-[10px] text-slate-500 leading-relaxed italic">
                        * Nota: 
                        @if($this->needsTitles && count($selectedTitles) > 0)
                            Se calculan {{ count($selectedSectionLabels) }} paralelos por cada uno de los {{ count($selectedTitles) }} títulos técnicos en los grados de 2do Ciclo.
                        @else
                            Se calculan {{ count($selectedSectionLabels) }} paralelos uniformes para todos los grados seleccionados.
                        @endif
                    </p>
                </div>
            </div>
        @endif

        @error('selectedSectionLabels') <p class="text-xs text-state-error mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Año Escolar ── --}}
    <div class="space-y-3">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Período Académico</p>
        
        @php
            $currentMonth = now()->month;
            $currentYear = now()->year;
            // Si es agosto o posterior, el año escolar empieza este año. Si no, empezó el año pasado.
            $startYear = $currentMonth >= 8 ? $currentYear : $currentYear - 1;
            $endYear = $startYear + 1;
            
            // Calculamos el nombre del año escolar para mostrarlo
            $computedYearName = "{$startYear}-{$endYear}";
            
            // Establecemos los límites para los inputs de fecha (min y max)
            $minDate = "{$startYear}-08-01"; // El año escolar suele empezar en agosto
            $maxDate = "{$endYear}-07-31";   // Y suele terminar en julio del año siguiente
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- Input de Año Escolar Calculado (Disabled/Readonly) --}}
            <div class="flex flex-col group">
                <x-ui.forms.input
                    label="Año Escolar"
                    name="year_name"
                    value="{{ $computedYearName }}"
                    icon-left="heroicon-o-academic-cap"
                    placeholder=""
                    readonly
                    disabled
                />
                {{-- 
                    Nota: Dado que el input está 'disabled', su valor NO se enviará en el payload de Livewire.
                    Por lo tanto, en el componente BaseSchoolWizard.php debes asegurar que $this->year_name 
                    se asigne o se calcule de esta misma forma antes de guardar.
                --}}
                <p class="text-[10px] text-slate-400 mt-1">Calculado automáticamente por el sistema.</p>
            </div>

            {{-- Fecha de Inicio --}}
            <div class="flex flex-col group relative">
                <label class="text-[11px] font-bold uppercase tracking-wider mb-2
                              text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                    Fecha de Inicio <span class="text-state-error ml-0.5">*</span>
                </label>
                <div class="relative flex items-center">
                    <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none
                                 text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                        <x-heroicon-o-calendar-days class="w-5 h-5" />
                    </span>
                    <input type="date" wire:model="start_date"
                           min="{{ $minDate }}" max="{{ $maxDate }}"
                           class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                                  rounded-none pl-7 pr-2 py-3 text-sm text-slate-800 dark:text-white
                                  focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors
                                  [color-scheme:light] dark:[color-scheme:dark]" />
                </div>
                @error('start_date') 
                    <p class="absolute -bottom-5 left-0 text-[10px] text-state-error truncate w-full">{{ $message }}</p> 
                @enderror
            </div>

            {{-- Fecha de Cierre --}}
            <div class="flex flex-col group relative">
                <label class="text-[11px] font-bold uppercase tracking-wider mb-2
                              text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                    Fecha de Cierre <span class="text-state-error ml-0.5">*</span>
                </label>
                <div class="relative flex items-center">
                    <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none
                                 text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                        <x-heroicon-o-calendar-days class="w-5 h-5" />
                    </span>
                    <input type="date" wire:model="end_date"
                           min="{{ $minDate }}" max="{{ $maxDate }}"
                           class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                                  rounded-none pl-7 pr-2 py-3 text-sm text-slate-800 dark:text-white
                                  focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors
                                  [color-scheme:light] dark:[color-scheme:dark]" />
                </div>
                @error('end_date') 
                    <p class="absolute -bottom-5 left-0 text-[10px] text-state-error truncate w-full">{{ $message }}</p> 
                @enderror
            </div>
        </div>
    </div>

</div>