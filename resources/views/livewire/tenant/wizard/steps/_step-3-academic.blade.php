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

        {{-- Preview --}}
        <div x-show="$wire.selectedLevels.length > 0 && $wire.selectedSectionLabels.length > 0"
             style="display:none;"
             class="p-3 rounded-xl bg-slate-50 dark:bg-white/[0.02] border border-slate-200 dark:border-white/5">
            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Vista previa</p>
            <p class="text-[10px] text-slate-500">
                Aprox.
                <strong class="text-slate-700 dark:text-slate-200"
                        x-text="$wire.selectedLevels.length * 3 * $wire.selectedSectionLabels.length"></strong>
                secciones generales
                <span x-show="$wire.needsTitles && $wire.selectedTitles.length > 0" style="display:none;">
                    + <strong class="text-orvian-orange"
                              x-text="3 * $wire.selectedSectionLabels.length * $wire.selectedTitles.length"></strong>
                    técnicas
                </span>.
            </p>
        </div>
        @error('selectedSectionLabels') <p class="text-xs text-state-error mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Títulos Técnicos (condicional) ── --}}
    <div x-show="$wire.needsTitles" style="display:none;"
         class="space-y-4 p-5 rounded-2xl border border-slate-200 dark:border-white/6 bg-slate-50/30 dark:bg-white/[0.01]">
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

    {{-- ── Tandas ── --}}
    <div class="space-y-3">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            Tandas Disponibles <span class="text-state-error ml-0.5">*</span>
        </p>
        @php
            $shiftMeta = [
                'Matutina'         => ['icon' => '☀️',  'hours' => '7:30 AM – 12:30 PM'],
                'Vespertina'       => ['icon' => '🌤️',  'hours' => '1:30 PM – 6:00 PM'],
                'Jornada Extendida'=> ['icon' => '🌞',  'hours' => '8:00 AM – 4:00 PM'],
                'Nocturna'         => ['icon' => '🌙',  'hours' => '6:00 PM – 10:00 PM'],
            ];
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach($this->shifts as $key => $label)
                @php $meta = $shiftMeta[$label] ?? ['icon' => '🕐', 'hours' => '']; @endphp
                <label @class([
                    'relative flex flex-col items-center gap-2 p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 text-center select-none',
                    'border-orvian-orange bg-orvian-orange/5 ring-1 ring-orvian-orange/20' => in_array($key, $selectedShifts),
                    'border-slate-200 dark:border-white/6 hover:border-slate-300 dark:hover:border-white/15' => !in_array($key, $selectedShifts),
                ])>
                    <input type="checkbox" wire:model.live="selectedShifts" value="{{ $key }}" class="hidden" />

                    {{-- Check badge --}}
                    @if(in_array($key, $selectedShifts))
                        <span class="absolute top-2 right-2 w-5 h-5 bg-orvian-orange rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                    @endif

                    <span class="text-2xl leading-none">{{ $meta['icon'] }}</span>
                    <div>
                        <p @class([
                            'text-xs font-black leading-tight',
                            'text-orvian-orange' => in_array($key, $selectedShifts),
                            'text-slate-700 dark:text-slate-200' => !in_array($key, $selectedShifts),
                        ])>{{ $label }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $meta['hours'] }}</p>
                    </div>
                </label>
            @endforeach
        </div>
        @error('selectedShifts') <p class="text-xs text-state-error mt-1">{{ $message }}</p> @enderror
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