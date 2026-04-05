<div class="p-4 md:p-6 flex flex-col gap-6"">
    {{-- Header --}}
    <x-ui.page-header
    title="Gestión de Carnets"
    description="Selecciona estudiantes para generar carnets de impresión"
    >
    <x-slot:actions>
        <x-ui.button 
            variant="secondary" 
            size="sm" 
            iconLeft="heroicon-s-arrow-left" 
            :href="route('app.academic.students.index')"
        >
            Volver al Listado
        </x-ui.button>
                {{-- Botón de Generación (Visible solo si hay selección) --}}
    @if($totalSelected > 0)
        <x-ui.button 
            variant="primary"
            size="lg"
            iconLeft="heroicon-s-printer"
            wire:click="generatePrintSheet"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="generatePrintSheet">
                Generar {{ $totalSelected }} Carnet{{ $totalSelected > 1 ? 's' : '' }}
            </span>
            <span wire:loading wire:target="generatePrintSheet">
                Generando...
            </span>
        </x-ui.button>
    @endif
    </x-slot:actions>
    
</x-ui.page-header>

    {{-- Filtros Estilo "Inline Bar" --}}
    <div class="bg-white dark:bg-dark-card rounded-2xl p-3 border border-slate-200 dark:border-gray-800 space-y-4">
        <div class="flex flex-col md:flex-row items-center gap-4">
            
            {{-- Búsqueda --}}
            <div class="w-full md:flex-1">
                <x-ui.forms.input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por nombre, cédula o código QR..."
                    icon-left="heroicon-o-magnifying-glass"
                />
            </div>

            {{-- Contenedor de Selectores y Botón Reset --}}
            <div class="flex flex-wrap md:flex-nowrap items-center gap-2 w-full md:w-auto">
                
                {{-- Filtro de Sección --}}
                <div class="w-full md:w-64">
                    <x-ui.forms.select 
                        wire:model.live="selectedSection"
                        placeholder="Todas las secciones">
                        @foreach($this->sections as $section)
                            <option value="{{ $section->id }}">{{ $section->full_label }}</option>
                        @endforeach
                    </x-ui.forms.select>
                </div>

                {{-- Filtro de Tanda --}}
                <div class="w-full md:w-40">
                    <x-ui.forms.select 
                        wire:model.live="selectedShift"
                        placeholder="Todas las tandas">
                        @foreach($this->shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->type }}</option>
                        @endforeach
                    </x-ui.forms.select>
                </div>


                {{-- Botón para Limpiar Filtros --}}
                <x-ui.button 
                    wire:click="resetFilters"
                    variant="primary"
                    type="ghost"
                    size="sm"
                    icon="heroicon-o-funnel"
                    aria-label="Limpiar filtros"
                    title="Limpiar filtros"
                />
            </div>
        </div>
    </div>

    {{-- Contenedor Principal (Grid de Cards) --}}
    <div class="space-y-6">
        {{-- Selector de "Seleccionar Todo" para Cards --}}
        <div class="flex items-center gap-2 px-2">
            <input 
                type="checkbox"
                wire:model.live="selectAll"
                id="selectAllCards"
                class="w-5 h-5 rounded border-slate-300 text-orvian-orange focus:ring-orvian-orange dark:bg-dark-bg dark:border-gray-700"
            >
            <label for="selectAllCards" class="text-sm font-medium text-slate-600 dark:text-gray-400 cursor-pointer">
                Seleccionar todos los estudiantes de esta página
            </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($students as $student)
                @php $isSelected = in_array($student->id, $selectedStudents); @endphp
                
                <div 
                    wire:key="student-card-{{ $student->id }}"
                    class="relative group bg-white dark:bg-dark-card rounded-[24px] border-2 transition-all duration-300 overflow-hidden
                    {{ $isSelected 
                        ? 'border-orvian-orange ring-4 ring-orvian-orange/10 shadow-xl' 
                        : 'border-slate-200 dark:border-gray-800 hover:border-slate-300 dark:hover:border-gray-700 shadow-sm' 
                    }}"
                >
                    {{-- Checkbox Flotante - Posicionado arriba a la derecha para no obstruir el avatar --}}
                    <div class="absolute top-3 right-3 z-10">
                        <input 
                            type="checkbox"
                            value="{{ $student->id }}"
                            wire:click="toggleStudent({{ $student->id }})"
                            @checked($isSelected)
                            class="w-5 h-5 rounded-full border-slate-300 text-orvian-orange focus:ring-orvian-orange cursor-pointer transition-transform group-hover:scale-110"
                        >
                    </div>

                    {{-- Header: Avatar + Info (Layout Horizontal) --}}
                    <div class="p-4 flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <x-ui.student-avatar :student="$student" size="md"/>
                        </div>

                        <div class="flex-1 min-w-0 pr-6"> {{-- Padding right para no chocar con el checkbox --}}
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-tight truncate" title="{{ $student->full_name }}">
                                {{ $student->full_name }}
                            </h3>
                            <p class="text-[11px] font-medium text-slate-500 dark:text-gray-500 mt-0.5 leading-snug">
                                {{ $student->section->full_label }}
                            </p>
                        </div>
                    </div>

                    {{-- Área de "Carnet" (Simulación de Impresión) --}}
                    <div class="px-4 pb-4">
                        <div class="bg-slate-50 dark:bg-dark-bg/50 rounded-[2rem] p-4 border border-slate-100 dark:border-gray-800 flex flex-col items-center shadow-inner">
                            
                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-gray-500 mb-4">
                                Credencial Digital
                            </p>

                            {{-- Contenedor del QR Real --}}
                            <div class="p-3 bg-white rounded-2xl shadow-sm border border-slate-100 mb-4">
                                <div class="qr-container">
                                    {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)
                                        ->color(30, 41, 59)
                                        ->margin(0)
                                        ->generate($student->qr_code) !!}
                                </div>
                            </div>

                            {{-- Código de Referencia --}}
                            <div class="flex flex-col items-center">
                                <span class="text-[8px] font-bold text-slate-500 tracking-tighter mb-0.5">Reference ID</span>
                                <code class="text-[10px] font-mono font-bold text-slate-600 dark:text-gray-400">
                                    {{ strtoupper(substr($student->qr_code, 0, 10)) }}
                                </code>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <x-ui.empty-state
                        icon="heroicon-o-users"
                        title="No se encontraron estudiantes"
                        description="No hay registros que coincidan con los filtros aplicados. Intenta buscar con otros términos o limpia los filtros actuales."
                        actionLabel="Limpiar todos los filtros"
                        actionClick="resetFilters"
                        variant="dashed"
                        class="bg-white dark:bg-dark-card"
                    />
                </div>
            @endforelse
        </div>
    </div>

{{-- Action Bar Flotante (Cápsula de Selección) --}}
@if($totalSelected > 0)
    <div 
        x-data
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 w-[95%] md:w-auto"
    >
        <div class="bg-slate-900/90 dark:bg-dark-card/95 backdrop-blur-md border border-white/10 dark:border-gray-700/50 rounded-full px-4 py-2 shadow-2xl flex items-center justify-between md:justify-start gap-4 md:gap-6">
            
            {{-- Indicador de Cantidad --}}
            <div class="flex items-center gap-3 pr-4 border-r border-white/10">
                <div class="w-7 h-7 rounded-full bg-orvian-orange flex items-center justify-center shadow-lg shadow-orvian-orange/20">
                    <span class="text-xs font-black text-white">{{ $totalSelected }}</span>
                </div>
                <span class="hidden md:block text-sm font-bold text-white tracking-wide">Seleccionados</span>
            </div>

            {{-- Acciones Principales --}}
            <div class="flex items-center gap-2">
                {{-- Ver Plantilla (Link en blanco) --}}
                @php
                    $ids = implode(',', $selectedStudents);
                    $previewUrl = route('app.academic.students.print-qr-sheet', ['students' => $ids]);
                @endphp
                
                <x-ui.button 
                    variant="secondary" 
                    size="sm" 
                    type="ghost"
                    iconLeft="heroicon-s-document-text"
                    href="{{ $previewUrl }}"
                    target="_blank"
                    class="!text-slate-300 hover:!bg-white/10"
                >
                    <span class="hidden sm:inline">Ver Plantilla</span>
                </x-ui.button>
            </div>

            {{-- Botón Deseleccionar Todo (La X) --}}
            <div class="pl-2 border-l border-white/10">
                <button 
                    wire:click="clearSelection"
                    title="Deseleccionar todo"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:text-white hover:bg-white/10 transition-colors"
                >
                    <x-heroicon-s-x-mark class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
@endif
</div>