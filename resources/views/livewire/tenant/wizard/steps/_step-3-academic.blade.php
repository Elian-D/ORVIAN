{{-- PASO 3 — Configuración Académica --}}
<div x-show="$wire.step === 3" style="display:none;" class="space-y-8">

    {{-- ── Niveles Educativos ── --}}
    <div class="space-y-3">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            Niveles Educativos <span class="text-state-error ml-0.5">*</span>
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($this->levels as $level)
                <label @class([
                    'flex items-center gap-3 px-4 py-3 rounded-xl border-2 cursor-pointer transition-all',
                    'border-orvian-orange bg-orvian-orange/5' => in_array($level->id, $selectedLevels),
                    'border-slate-200 dark:border-white/6 hover:border-slate-300 dark:hover:border-white/15' => !in_array($level->id, $selectedLevels),
                ])>
                    <input type="checkbox" wire:model.live="selectedLevels" value="{{ $level->id }}"
                           class="w-4 h-4 rounded border-slate-300 text-orvian-orange focus:ring-orvian-orange focus:ring-offset-0" />
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $level->name }}</span>
                </label>
            @endforeach
        </div>
        @error('selectedLevels') <p class="text-xs text-state-error mt-1">{{ $message }}</p> @enderror

        @php $secundariaId = $this->levels->first(fn($l) => $l->slug === 'secundaria-segundo-ciclo')?->id; @endphp
        <div x-show="$wire.needsTitles && !$wire.selectedLevels.map(String).includes('{{ $secundariaId }}')"
            style="display:none;"
            class="flex items-start gap-3 p-4 rounded-xl bg-state-info/5 border border-state-info/20">
            <svg class="w-4 h-4 text-state-info flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-xs font-bold text-state-info">Esta modalidad requiere el nivel Secundaria Segundo Ciclo</p>
                <p class="text-[11px] text-state-info/70 mt-0.5 leading-relaxed">
                    Los títulos técnicos se imparten en el Segundo Ciclo (4to–6to).
                    Selecciona <strong class="text-state-info">Secundaria Segundo Ciclo</strong> o cambia la modalidad en el Paso 1.
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
                        x-text="$wire.selectedLevels.length * 6 * $wire.selectedSectionLabels.length"></strong>
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            {{-- Select de año escolar --}}
            <div class="flex flex-col group">
                <x-ui.forms.select
                    label="Año Escolar"
                    name="year_name"
                    wire:model="year_name"
                    icon-left="heroicon-o-academic-cap"
                    :error="$errors->first('year_name')"
                    placeholder=""
                    required
                >
                    @php $currentYear = now()->year; @endphp
                    @for($y = $currentYear; $y <= $currentYear + 5; $y++)
                        <option value="{{ $y }}-{{ $y + 1 }}">{{ $y }}-{{ $y + 1 }}</option>
                    @endfor
                </x-ui.forms.select>
            </div>

            {{-- Fecha inicio --}}
            <div class="flex flex-col group">
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
                           class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                                  rounded-none pl-7 pr-2 py-3 text-sm text-slate-800 dark:text-white
                                  focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors
                                  [color-scheme:light] dark:[color-scheme:dark]" />
                </div>
                @error('start_date') <p class="mt-1.5 text-xs text-state-error">{{ $message }}</p> @enderror
            </div>

            {{-- Fecha cierre --}}
            <div class="flex flex-col group">
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
                           class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                                  rounded-none pl-7 pr-2 py-3 text-sm text-slate-800 dark:text-white
                                  focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors
                                  [color-scheme:light] dark:[color-scheme:dark]" />
                </div>
                @error('end_date') <p class="mt-1.5 text-xs text-state-error">{{ $message }}</p> @enderror
            </div>

        </div>
    </div>

</div>