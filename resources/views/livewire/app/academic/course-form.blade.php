{{-- resources/views/livewire/app/academic/course-form.blade.php --}}
<div>
    <div class="p-4 md:p-6 max-w-2xl mx-auto">

        <x-ui.page-header title="Nuevo Curso">
            <x-slot:actions>
                <x-ui.button href="{{ route('app.academic.courses.index') }}"
                    type="ghost" size="sm" iconLeft="heroicon-o-arrow-left">
                    Volver
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        {{-- ══ Barra de progreso ══
             - $this->visualStep: paso visual que NUNCA retrocede cuando se salta el paso 3
             - $this->totalVisualSteps: 3 cuando el grado no admite técnico, 4 cuando sí
             - $this->progressPercent: porcentaje calculado en el servidor para evitar saltos
        --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    Paso {{ $this->visualStep }} de {{ $this->totalVisualSteps }}
                </p>
                <p class="text-xs text-slate-400 dark:text-slate-600">
                    {{ $this->stepLabel }}
                </p>
            </div>
            {{-- Usar style width con el valor del servidor para evitar el flash de Alpine --}}
            <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-white/8 overflow-hidden">
                <div class="h-full rounded-full bg-orvian-orange transition-[width] duration-500 ease-out"
                     style="width: {{ $this->progressPercent }}%"></div>
            </div>

            {{-- Indicadores de paso (puntos) --}}
            <div class="flex items-center justify-between mt-3 px-0.5">
                @for ($i = 1; $i <= $this->totalVisualSteps; $i++)
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center
                                    transition-all duration-300
                                    {{ $this->visualStep >= $i
                                        ? 'border-orvian-orange bg-orvian-orange'
                                        : 'border-slate-200 dark:border-white/15 bg-white dark:bg-dark-card' }}">
                            @if($this->visualStep > $i)
                                <x-heroicon-s-check class="w-2.5 h-2.5 text-white" />
                            @elseif($this->visualStep === $i)
                                <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                            @endif
                        </div>
                        @if($i < $this->totalVisualSteps)
                            <div class="h-px flex-1 min-w-[2rem] transition-all duration-500
                                        {{ $this->visualStep > $i
                                            ? 'bg-orvian-orange'
                                            : 'bg-slate-200 dark:bg-white/10' }}"></div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        {{-- ══ Card del wizard ══ --}}
        <div class="bg-white dark:bg-dark-card rounded-2xl border
                    border-slate-200 dark:border-dark-border
                    overflow-hidden">

            {{-- ═══════════════════════════
                 Paso 1: Nivel educativo
            ═══════════════════════════ --}}
            @if($step === 1)
                <div class="p-6">
                    <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1">
                        ¿En qué nivel educativo?
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                        Selecciona el nivel al que pertenece el nuevo curso.
                    </p>

                    <div class="space-y-2">
                        @foreach($this->levels as $level)
                            <button
                                wire:click="$set('selectedLevelId', {{ $level->id }})"
                                class="w-full flex items-center justify-between p-4 rounded-xl
                                       border-2 transition-all text-left
                                       {{ $selectedLevelId === $level->id
                                           ? 'border-orvian-orange bg-orvian-orange/5 dark:bg-orvian-orange/8'
                                           : 'border-slate-200 dark:border-dark-border
                                              hover:border-slate-300 dark:hover:border-white/20
                                              bg-slate-50 dark:bg-dark-card' }}">
                                <div>
                                    <p class="text-sm font-bold
                                               {{ $selectedLevelId === $level->id
                                                   ? 'text-orvian-orange'
                                                   : 'text-slate-700 dark:text-white' }}">
                                        {{ $level->name }}
                                    </p>
                                    <p class="text-xs mt-0.5 text-slate-400 dark:text-slate-500">
                                        {{ $level->grades->count() }} grados disponibles
                                    </p>
                                </div>
                                @if($selectedLevelId === $level->id)
                                    <x-heroicon-s-check-circle
                                        class="w-5 h-5 text-orvian-orange flex-shrink-0" />
                                @endif
                            </button>
                        @endforeach
                    </div>

                    @error('selectedLevelId')
                        <p class="text-xs text-red-500 dark:text-red-400 mt-3">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- ═══════════════════════════
                 Paso 2: Grado
            ═══════════════════════════ --}}
            @if($step === 2)
                <div class="p-6">
                    <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1">
                        ¿Qué grado?
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                        Grados disponibles en
                        <strong class="text-slate-700 dark:text-slate-200">
                            {{ $this->levels->firstWhere('id', $selectedLevelId)?->name }}
                        </strong>.
                    </p>

                    <div class="grid grid-cols-2 gap-2">
                        @foreach($this->grades as $grade)
                            <button
                                wire:click="$set('selectedGradeId', {{ $grade->id }})"
                                class="flex flex-col p-4 rounded-xl border-2 transition-all text-left
                                       {{ $selectedGradeId === $grade->id
                                           ? 'border-orvian-orange bg-orvian-orange/5 dark:bg-orvian-orange/8'
                                           : 'border-slate-200 dark:border-dark-border
                                              hover:border-slate-300 dark:hover:border-white/20
                                              bg-slate-50 dark:bg-dark-card' }}">
                                <p class="text-sm font-bold
                                           {{ $selectedGradeId === $grade->id
                                               ? 'text-orvian-orange'
                                               : 'text-slate-700 dark:text-white' }}">
                                    {{ $grade->name }}
                                </p>
                                @if($grade->allows_technical)
                                    <span class="mt-2 inline-flex items-center gap-1
                                                 text-[9px] font-bold uppercase tracking-wider
                                                 text-orvian-orange/70">
                                        <x-heroicon-o-cog-6-tooth class="w-3 h-3" />
                                        Permite técnico
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    @error('selectedGradeId')
                        <p class="text-xs text-red-500 dark:text-red-400 mt-3">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- ═══════════════════════════
                Paso 3: Tipo + Título Técnico
                Solo aparece si gradeAllowsTechnical = true
            ═══════════════════════════ --}}
            @if($step === 3)
                <div class="p-6 space-y-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1">
                            ¿Académico o Técnico?
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            El grado
                            <strong class="text-slate-700 dark:text-slate-200">
                                {{ $this->selectedGrade?->name }}
                            </strong>
                            admite secciones técnicas.
                        </p>
                    </div>

                    {{-- Toggle Académico / Técnico --}}
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            wire:click="$set('sectionType', 'academic')"
                            class="flex flex-col items-center p-5 rounded-xl border-2 transition-all
                                {{ $sectionType === 'academic'
                                    ? 'border-orvian-orange bg-orvian-orange/5 dark:bg-orvian-orange/8'
                                    : 'border-slate-200 dark:border-dark-border
                                        hover:border-slate-300 dark:hover:border-white/20
                                        bg-slate-50 dark:bg-dark-card' }}">
                            <div class="w-10 h-10 rounded-xl mb-3 flex items-center justify-center
                                        {{ $sectionType === 'academic'
                                            ? 'bg-orvian-orange/15'
                                            : 'bg-slate-100 dark:bg-white/8' }}">
                                <x-heroicon-o-academic-cap
                                    class="w-5 h-5 {{ $sectionType === 'academic'
                                        ? 'text-orvian-orange'
                                        : 'text-slate-400 dark:text-slate-500' }}" />
                            </div>
                            <p class="text-sm font-bold
                                    {{ $sectionType === 'academic'
                                        ? 'text-orvian-orange'
                                        : 'text-slate-700 dark:text-white' }}">
                                Académico
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 text-center leading-snug">
                                Plan general de estudios
                            </p>
                        </button>

                        <button
                            wire:click="$set('sectionType', 'technical')"
                            class="flex flex-col items-center p-5 rounded-xl border-2 transition-all
                                {{ $sectionType === 'technical'
                                    ? 'border-orvian-orange bg-orvian-orange/5 dark:bg-orvian-orange/8'
                                    : 'border-slate-200 dark:border-dark-border
                                        hover:border-slate-300 dark:hover:border-white/20
                                        bg-slate-50 dark:bg-dark-card' }}">
                            <div class="w-10 h-10 rounded-xl mb-3 flex items-center justify-center
                                        {{ $sectionType === 'technical'
                                            ? 'bg-orvian-orange/15'
                                            : 'bg-slate-100 dark:bg-white/8' }}">
                                <x-heroicon-o-cog-6-tooth
                                    class="w-5 h-5 {{ $sectionType === 'technical'
                                        ? 'text-orvian-orange'
                                        : 'text-slate-400 dark:text-slate-500' }}" />
                            </div>
                            <p class="text-sm font-bold
                                    {{ $sectionType === 'technical'
                                        ? 'text-orvian-orange'
                                        : 'text-slate-700 dark:text-white' }}">
                                Técnico
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 text-center leading-snug">
                                Bachiller o título técnico
                            </p>
                        </button>
                    </div>

                    {{-- Selector de título técnico (solo cuando sectionType = 'technical') --}}
                    @if($sectionType === 'technical')

                        @if(! $this->schoolNeedsTechnical)
                            {{-- La modalidad de la escuela no habilita técnicos --}}
                            <div class="p-4 rounded-xl
                                        bg-amber-50 dark:bg-amber-950/30
                                        border border-amber-200 dark:border-amber-800/50">
                                <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">
                                    Modalidad no técnica
                                </p>
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                    La modalidad de tu centro
                                    <strong>({{ $this->school->modalidad }})</strong>
                                    no habilita títulos técnicos.
                                    Selecciona Académico para continuar.
                                </p>
                            </div>

                        @elseif($this->families->isEmpty())
                            {{-- Modalidad técnica pero sin familias en el catálogo --}}
                            <div class="p-4 rounded-xl
                                        bg-amber-50 dark:bg-amber-950/30
                                        border border-amber-200 dark:border-amber-800/50">
                                <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">
                                    Sin familias técnicas disponibles
                                </p>
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                    Ve a Configuración → Escuela para habilitar los títulos técnicos del centro.
                                </p>
                            </div>

                        @elseif($this->confirmedTitle)
                            {{-- Título ya confirmado — mostrar pill con opción de cambiar --}}
                            <div class="flex items-center justify-between px-4 py-3 rounded-xl
                                        bg-orvian-orange/5 dark:bg-orvian-orange/8
                                        border border-orvian-orange/20 dark:border-orvian-orange/15">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate">
                                        {{ $this->confirmedTitle->name }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                                        {{ $this->confirmedTitle->family?->name }}
                                        @if($this->confirmedTitle->code)
                                            ·
                                            <span class="font-mono">{{ $this->confirmedTitle->code }}</span>
                                        @endif
                                    </p>
                                </div>
                                <button
                                    wire:click="clearTitle"
                                    type="button"
                                    class="ml-3 flex-shrink-0 p-1.5 rounded-lg
                                        text-slate-400 dark:text-slate-600
                                        hover:text-red-500 dark:hover:text-red-400
                                        hover:bg-red-50 dark:hover:bg-red-950/30
                                        transition-colors">
                                    <x-heroicon-s-x-mark class="w-4 h-4" />
                                </button>
                            </div>

                        @else
                            {{-- Selector de familia + título técnico --}}
                            <div class="space-y-4 p-5 rounded-2xl
                                        border border-dashed border-slate-300 dark:border-white/10
                                        bg-gradient-to-br from-slate-50/50 via-slate-50/30 to-orvian-orange/5
                                        dark:from-white/[0.02] dark:via-transparent dark:to-orvian-orange/8
                                        shadow-sm">

                                <div class="flex items-center gap-2">
                                    <p class="text-[11px] font-bold uppercase tracking-wider
                                            text-slate-400 dark:text-slate-500">
                                        Título Técnico
                                    </p>
                                    <x-ui.badge variant="warning" size="sm">Requerido</x-ui.badge>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">

                                    {{-- Select de Familia — wire:model.live para disparar updatedTempFamilyId --}}
                                    <x-ui.forms.select
                                        label="Familia Técnica"
                                        name="tempFamilyId"
                                        wire:model.live="tempFamilyId"
                                        iconLeft="heroicon-o-squares-2x2">
                                        @foreach($this->families as $family)
                                            <option value="{{ $family->id }}">{{ $family->name }}</option>
                                        @endforeach
                                    </x-ui.forms.select>

                                    {{-- Select de Título — wire:model.live SIEMPRE
                                        El disabled se controla SOLO con CSS/Alpine para no romper
                                        la sincronización de Livewire. El atributo HTML `disabled`
                                        impide que el <select> envíe su valor en algunos browsers. --}}
                                    <div class="transition-opacity duration-200
                                                {{ ! $tempFamilyId ? 'opacity-40 pointer-events-none select-none' : '' }}">
                                        <x-ui.forms.select
                                            label="Título"
                                            name="tempTitleId"
                                            wire:model.live="tempTitleId"
                                            :hint="! $tempFamilyId ? 'Selecciona primero la familia' : ''">
                                            @foreach($this->titlesForFamily as $title)
                                                <option value="{{ $title->id }}">{{ $title->name }}</option>
                                            @endforeach
                                        </x-ui.forms.select>
                                    </div>

                                </div>

                                {{-- Botón de confirmar — visible solo cuando hay título seleccionado --}}
                                <div class="flex items-center gap-3">
                                    <x-ui.button
                                        wire:click="confirmTitle"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmTitle"
                                        variant="primary"
                                        type="outline"
                                        size="sm"
                                        iconLeft="heroicon-s-check"
                                        :disabled="! $tempTitleId">
                                        <span wire:loading.remove wire:target="confirmTitle">
                                            Confirmar título
                                        </span>
                                        <span wire:loading wire:target="confirmTitle">
                                            Confirmando...
                                        </span>
                                    </x-ui.button>

                                    @if($tempTitleId && $this->titlesForFamily->isNotEmpty())
                                        <p class="text-xs text-slate-400 dark:text-slate-600">
                                            Revisa que sea el título correcto antes de confirmar.
                                        </p>
                                    @endif
                                </div>

                                @error('tempTitleId')
                                    <p class="text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                                @enderror

                            </div>
                        @endif

                    @endif

                    @error('sectionType')
                        <p class="text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    @error('selectedTitleId')
                        <p class="text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                    @enderror

                </div>
            @endif

            {{-- ═══════════════════════════
                 Paso 4: Paralelo + Tanda
            ═══════════════════════════ --}}
            @if($step === 4)
                <div class="p-6 space-y-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1">
                            Configurar paralelo y tanda
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Define la letra del paralelo y el horario de la nueva sección.
                        </p>
                    </div>

                    {{-- Resumen de selección --}}
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg
                                     bg-slate-100 dark:bg-dark-border
                                     text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ $this->selectedGrade?->name }}
                        </span>
                        @if($sectionType === 'technical' && $this->confirmedTitle)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg
                                         bg-orvian-orange/10 dark:bg-orvian-orange/12
                                         text-xs font-semibold text-orvian-orange">
                                <x-heroicon-o-cog-6-tooth class="w-3 h-3" />
                                {{ $this->confirmedTitle->name }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg
                                         bg-slate-100 dark:bg-dark-border
                                         text-xs font-semibold text-slate-600 dark:text-slate-300">
                                <x-heroicon-o-academic-cap class="w-3 h-3" />
                                Académico
                            </span>
                        @endif
                    </div>

                    <x-ui.forms.input
                        label="Paralelo"
                        name="label"
                        wire:model="label"
                        placeholder="Ej: A"
                        hint="Una letra identifica cada paralelo del grado. Ej: A, B, C."
                        :error="$errors->first('label')" />

                    <x-ui.forms.select
                        label="Tanda"
                        name="shiftId"
                        wire:model="shiftId"
                        :error="$errors->first('shiftId')">
                        @foreach($this->shifts as $shift)
                            <option value="{{ $shift->id }}">
                                {{ $shift->type }}
                                @if($shift->start_time && $shift->end_time)
                                    — {{ $shift->start_time->format('h:i A') }}
                                    a {{ $shift->end_time->format('h:i A') }}
                                @endif
                            </option>
                        @endforeach
                    </x-ui.forms.select>

                    {{-- Paralelos ya existentes (informativo) --}}
                    @if($this->existingSections->isNotEmpty())
                        <div class="rounded-xl p-4
                                    bg-slate-50 dark:bg-dark-bg
                                    border border-slate-200 dark:border-dark-border">
                            <p class="text-[10px] font-black uppercase tracking-widest mb-2.5
                                       text-slate-400 dark:text-slate-600">
                                Paralelos ya configurados para este grado
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($this->existingSections as $existing)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg
                                                 bg-white dark:bg-dark-border
                                                 border border-slate-200 dark:border-dark-border
                                                 text-xs font-bold text-slate-700 dark:text-slate-200">
                                        {{ $existing->label }}
                                        @if($existing->shift)
                                            <span class="text-[9px] font-normal
                                                         text-slate-400 dark:text-slate-500">
                                                {{ $existing->shift->type }}
                                            </span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ══ Footer de navegación ══ --}}
            <div class="flex items-center justify-between w-full p-6 border-t border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card rounded-b-2xl">
                {{-- Botón Atrás (Izquierda) --}}
                <x-ui.button
                    wire:click="prevStep"
                    variant="ghost" 
                    size="sm"
                    iconLeft="heroicon-o-arrow-left"
                    :disabled="$step === 1">
                    Atrás
                </x-ui.button>

                {{-- Botones de Acción (Derecha) --}}
                <div>
                    @if($step < 4)
                        <x-ui.button
                            wire:click="nextStep"
                            variant="primary" 
                            size="sm"
                            iconRight="heroicon-o-arrow-right"
                            :disabled="
                                ($step === 1 && ! $selectedLevelId)
                                || ($step === 2 && ! $selectedGradeId)
                                || ($step === 3 && $sectionType === 'technical' && ! $selectedTitleId)
                            ">
                            Continuar
                        </x-ui.button>
                    @else
                        <x-ui.button
                            wire:click="create"
                            variant="primary" 
                            size="sm"
                            wire:loading.attr="disabled"
                            wire:target="create">
                            <span wire:loading.remove wire:target="create">Crear Sección</span>
                            <span wire:loading wire:target="create">Creando...</span>
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>