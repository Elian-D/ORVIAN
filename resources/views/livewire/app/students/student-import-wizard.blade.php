<div class="w-full max-w-4xl mx-auto p-4 md:p-8">

    {{-- HEADER --}}
    <x-ui.page-header
        title="Importar Estudiantes"
        description="Sube un archivo Excel o CSV de SIGERD y mapea las columnas para importar múltiples estudiantes de forma masiva."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" type="ghost" href="{{ route('app.academic.students.index') }}">
                Cancelar
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- INDICADOR DE PASOS --}}
    <div class="flex items-center gap-0 mb-8">
        @foreach ([1 => 'Subir Archivo', 2 => 'Mapear Columnas', 3 => 'Progreso'] as $num => $label)
            <div class="flex items-center {{ !$loop->first ? 'flex-1' : '' }}">
                @if (!$loop->first)
                    <div class="flex-1 h-px {{ $step > $num - 1 ? 'bg-orvian-orange' : 'bg-slate-200 dark:bg-dark-border' }} transition-colors"></div>
                @endif
                <div class="flex flex-col items-center gap-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors
                        {{ $step > $num ? 'bg-orvian-orange text-white' : ($step === $num ? 'bg-orvian-orange text-white ring-4 ring-orvian-orange/20' : 'bg-slate-100 dark:bg-dark-card text-slate-400 dark:text-slate-500') }}">
                        @if ($step > $num)
                            <x-heroicon-s-check class="w-4 h-4" />
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    <span class="text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap
                        {{ $step === $num ? 'text-orvian-orange' : 'text-slate-400 dark:text-slate-500' }}">
                        {{ $label }}
                    </span>
                </div>
                @if (!$loop->last)
                    <div class="flex-1 h-px {{ $step > $num ? 'bg-orvian-orange' : 'bg-slate-200 dark:bg-dark-border' }} transition-colors"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ════════════════════════════════════════════════════
         PASO 1 — DROPZONE
    ════════════════════════════════════════════════════ --}}
    @if ($step === 1)
        <div class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-50 dark:border-dark-border">
                <div class="p-2 bg-orvian-orange/10 text-orvian-orange rounded-lg">
                    <x-heroicon-s-arrow-up-tray class="w-5 h-5" />
                </div>
                <h3 class="font-bold uppercase text-sm tracking-widest text-slate-700 dark:text-slate-300">Seleccionar Archivo</h3>
            </div>

            <div
                x-data="{
                    dragging: false,
                    handleDrop(e) {
                        this.dragging = false;
                        const file = e.dataTransfer.files[0];
                        if (!file) return;
                        @this.upload('file', file, () => {}, () => {}, (progress) => {});
                    }
                }"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="handleDrop($event)"
                class="relative border-2 border-dashed rounded-2xl p-10 text-center transition-colors cursor-pointer
                       hover:border-orvian-orange hover:bg-orvian-orange/5
                       dark:hover:bg-orvian-orange/5"
                :class="dragging ? 'border-orvian-orange bg-orvian-orange/5' : 'border-slate-200 dark:border-dark-border'"
                onclick="document.getElementById('file-input').click()"
            >
                <input
                    id="file-input"
                    type="file"
                    accept=".xlsx,.xls,.csv"
                    wire:model="file"
                    class="sr-only"
                    onclick="event.stopPropagation()"
                />

                <div class="flex flex-col items-center gap-3">
                    {{-- Cambiamos :class por x-bind:class para que lo maneje Alpine y no PHP --}}
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-dark-border flex items-center justify-center transition-colors"
                        x-bind:class="dragging ? 'bg-orvian-orange/20' : ''">
                        
                        {{-- Usamos x-bind:class o la abreviación ::class --}}
                        <x-heroicon-o-document-arrow-up 
                            class="w-7 h-7 text-slate-400 transition-colors" 
                            x-bind:class="dragging ? 'text-orvian-orange' : ''" 
                        />
                    </div>
                    
                    <div class="text-center"> {{-- Añadido text-center para mejor alineación --}}
                        <p class="font-semibold text-slate-700 dark:text-slate-200">
                            Arrastra tu archivo aquí
                        </p>
                        <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">
                            o haz clic para seleccionar — Excel (.xlsx, .xls) o CSV · Máx. 10 MB
                        </p>
                    </div>
                </div>

                {{-- Nombre del archivo seleccionado --}}
                @if ($file)
                    <div class="mt-4 flex items-center justify-center gap-2 text-sm text-orvian-orange font-medium">
                        <x-heroicon-s-document-check class="w-5 h-5" />
                        {{ $file->getClientOriginalName() }}
                    </div>
                @endif

                {{-- Loading while uploading --}}
                <div wire:loading wire:target="file" class="absolute inset-0 bg-white/70 dark:bg-dark-card/70 rounded-2xl flex items-center justify-center">
                    <div class="flex items-center gap-2 text-orvian-orange text-sm font-medium">
                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Cargando archivo...
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-500/10 rounded-xl border border-blue-100 dark:border-blue-500/20">
                <div class="flex gap-3">
                    <x-heroicon-s-information-circle class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" />
                    <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                        <p class="font-semibold">Sobre el archivo</p>
                        <ul class="list-disc list-inside text-blue-600 dark:text-blue-400 space-y-0.5">
                            <li>La primera fila debe ser la cabecera con los nombres de columnas.</li>
                            <li>En el siguiente paso podrás indicar qué columna corresponde a qué campo de ORVIAN.</li>
                            <li>Los estudiantes duplicados (mismo RNC o mismo nombre + fecha de nacimiento) serán omitidos automáticamente.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-ui.button
                    variant="primary"
                    wire:click="uploadFile"
                    wire:loading.attr="disabled"
                    wire:target="uploadFile,file"
                    :disabled="!$file"
                >
                    <span wire:loading.remove wire:target="uploadFile">Continuar →</span>
                    <span wire:loading wire:target="uploadFile">Analizando archivo...</span>
                </x-ui.button>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════
         PASO 2 — MAPEO DE COLUMNAS
    ════════════════════════════════════════════════════ --}}
    @if ($step === 2)
        <div class="space-y-6">

            {{-- Card: Mapeo --}}
            <div class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-50 dark:border-dark-border">
                    <div class="p-2 bg-orvian-orange/10 text-orvian-orange rounded-lg">
                        <x-heroicon-s-arrows-right-left class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-bold uppercase text-sm tracking-widest text-slate-700 dark:text-slate-300">Mapeo de Columnas</h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Indica qué columna de tu archivo corresponde a cada campo de ORVIAN.</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-100 dark:border-dark-border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-dark-border/40">
                                <th class="text-left px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider w-1/2">
                                    Columna en tu archivo
                                </th>
                                <th class="text-left px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider w-1/2">
                                    Campo en ORVIAN
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-dark-border">
                            @foreach ($headers as $header)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-dark-border/20 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 dark:bg-dark-border text-slate-600 dark:text-slate-300 font-mono text-xs">
                                                {{ $header }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <select
                                            wire:model="mapping.{{ $header }}"
                                            class="w-full text-sm border-0 border-b border-slate-200 dark:border-dark-border bg-transparent rounded-none py-1.5
                                                   text-slate-700 dark:text-slate-500 focus:ring-0 focus:border-orvian-orange transition-colors"
                                        >
                                            <option value="">— Ignorar esta columna —</option>
                                            @foreach (self::ORVIAN_FIELDS as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Leyenda de campos requeridos --}}
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-3">
                    Los campos <span class="font-semibold text-orvian-orange">Nombres</span> y <span class="font-semibold text-orvian-orange">Apellidos</span> son obligatorios para poder importar.
                </p>
            </div>

            {{-- Card: Sección por defecto --}}
            <div class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-50 dark:border-dark-border">
                    <div class="p-2 bg-blue-500/10 text-blue-500 rounded-lg">
                        <x-heroicon-s-academic-cap class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-bold uppercase text-sm tracking-widest text-slate-700 dark:text-slate-300">Sección por Defecto</h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Se aplicará a estudiantes cuya sección no pueda determinarse automáticamente.</p>
                    </div>
                </div>

                <x-ui.forms.select
                    label="Sección por defecto"
                    name="defaultSectionId"
                    wire:model="defaultSectionId"
                    icon-left="heroicon-o-academic-cap"
                    hint="Opcional — si tu archivo tiene una columna de sección mapeada, ORVIAN intentará usarla primero."
                >
                    @foreach ($this->sections as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>

            {{-- Acciones --}}
            <div class="flex justify-between">
                <x-ui.button variant="secondary" type="ghost" wire:click="$set('step', 1)">
                    ← Volver
                </x-ui.button>
                <x-ui.button variant="primary" wire:click="startImport" wire:loading.attr="disabled" wire:target="startImport">
                    <span wire:loading.remove wire:target="startImport">Iniciar Importación</span>
                    <span wire:loading wire:target="startImport">Iniciando...</span>
                </x-ui.button>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════
         PASO 3 — PROGRESO Y RESULTADOS
    ════════════════════════════════════════════════════ --}}
    @if ($step === 3)
        @php $record = $this->importRecord; @endphp

        {{-- Polling automático cada 2s mientras no finalice --}}
        @if ($record && !$record->isFinished())
            <div wire:poll.2000ms="$refresh"></div>
        @endif

        <div class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border">

            @if (!$record)
                <div class="flex items-center justify-center py-16 gap-3 text-slate-400">
                    <svg class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Iniciando importación...
                </div>

            @elseif ($record->status === 'processing' || $record->status === 'pending')
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-50 dark:border-dark-border">
                    <div class="p-2 bg-blue-500/10 text-blue-500 rounded-lg animate-pulse">
                        <x-heroicon-s-arrow-path class="w-5 h-5" />
                    </div>
                    <h3 class="font-bold uppercase text-sm tracking-widest text-slate-700 dark:text-slate-300">Importando...</h3>
                </div>

                <div class="space-y-6">
                    {{-- Barra de progreso --}}
                    <div>
                        <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mb-2">
                            <span>{{ $record->processed_rows }} de {{ $record->total_rows > 0 ? $record->total_rows : '?' }} filas procesadas</span>
                            <span class="font-bold text-orvian-orange">{{ $record->progress_percentage }}%</span>
                        </div>
                        <div class="h-3 bg-slate-100 dark:bg-dark-border rounded-full overflow-hidden">
                            <div
                                class="h-full bg-gradient-to-r from-orvian-orange to-amber-400 rounded-full transition-all duration-500"
                                style="width: {{ $record->progress_percentage }}%"
                            ></div>
                        </div>
                    </div>

                    {{-- Stats en tiempo real --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-4 bg-slate-50 dark:bg-dark-border/40 rounded-xl text-center">
                            <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">{{ $record->success_rows }}</p>
                            <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Exitosos</p>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-dark-border/40 rounded-xl text-center">
                            <p class="text-2xl font-bold text-red-500">{{ $record->failed_rows }}</p>
                            <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Errores</p>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-dark-border/40 rounded-xl text-center">
                            <p class="text-2xl font-bold text-slate-400">{{ max(0, $record->total_rows - $record->processed_rows) }}</p>
                            <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Pendientes</p>
                        </div>
                    </div>

                    <p class="text-xs text-center text-slate-400 dark:text-slate-500">
                        Esta página se actualiza automáticamente cada 2 segundos. No cierres esta ventana.
                    </p>
                </div>

            @elseif ($record->status === 'completed')
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-50 dark:border-dark-border">
                    <div class="p-2 bg-emerald-500/10 text-emerald-500 rounded-lg">
                        <x-heroicon-s-check-circle class="w-5 h-5" />
                    </div>
                    <h3 class="font-bold uppercase text-sm tracking-widest text-slate-700 dark:text-slate-300">Importación Completada</h3>
                </div>

                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-500/10 rounded-xl text-center border border-emerald-100 dark:border-emerald-500/20">
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $record->success_rows }}</p>
                        <p class="text-xs text-emerald-500 uppercase tracking-wider mt-1">Importados</p>
                    </div>
                    <div class="p-4 {{ $record->failed_rows > 0 ? 'bg-red-50 dark:bg-red-500/10 border border-red-100 dark:border-red-500/20' : 'bg-slate-50 dark:bg-dark-border/40' }} rounded-xl text-center">
                        <p class="text-2xl font-bold {{ $record->failed_rows > 0 ? 'text-red-500' : 'text-slate-400' }}">{{ $record->failed_rows }}</p>
                        <p class="text-xs {{ $record->failed_rows > 0 ? 'text-red-400' : 'text-slate-400' }} uppercase tracking-wider mt-1">Con Errores</p>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-dark-border/40 rounded-xl text-center">
                        <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">{{ $record->total_rows }}</p>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Total Filas</p>
                    </div>
                </div>

                {{-- Log de errores --}}
                @if ($record->failed_rows > 0 && !empty($record->errors))
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm font-semibold text-red-600 dark:text-red-400">
                                Registros con error ({{ $record->failed_rows }})
                            </p>
                            <x-ui.button
                                variant="secondary"
                                type="ghost"
                                size="sm"
                                iconLeft="heroicon-s-arrow-down-tray"
                                wire:click="downloadErrors"
                            >
                                Descargar CSV de errores
                            </x-ui.button>
                        </div>

                        <div class="max-h-52 overflow-y-auto rounded-xl border border-red-100 dark:border-red-500/20 divide-y divide-red-50 dark:divide-red-500/10">
                            @foreach (array_slice($record->errors ?? [], 0, 20) as $i => $err)
                                <div class="px-4 py-3 flex gap-3 items-start text-sm">
                                    <span class="text-xs font-mono text-red-400 mt-0.5">#{{ $i + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-red-600 dark:text-red-400 font-medium">{{ $err['error'] ?? 'Error desconocido' }}</p>
                                        @if (!empty($err['data']))
                                            <p class="text-slate-400 text-xs mt-0.5 truncate">
                                                {{ implode(' · ', array_map(fn($v) => (string)$v, array_slice($err['data'], 0, 4))) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @if (count($record->errors ?? []) > 20)
                                <div class="px-4 py-3 text-xs text-center text-slate-400">
                                    ... y {{ count($record->errors) - 20 }} errores más. Descarga el CSV para verlos todos.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="flex gap-3 justify-end">
                    <x-ui.button variant="secondary" type="ghost" wire:click="resetWizard">
                        Nueva importación
                    </x-ui.button>
                    <x-ui.button variant="primary" href="{{ route('app.academic.students.index') }}">
                        Ver Estudiantes
                    </x-ui.button>
                </div>

            @elseif ($record->status === 'failed')
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-50 dark:border-dark-border">
                    <div class="p-2 bg-red-500/10 text-red-500 rounded-lg">
                        <x-heroicon-s-x-circle class="w-5 h-5" />
                    </div>
                    <h3 class="font-bold uppercase text-sm tracking-widest text-slate-700 dark:text-slate-300">Error en la Importación</h3>
                </div>

                <div class="p-4 bg-red-50 dark:bg-red-500/10 rounded-xl border border-red-100 dark:border-red-500/20 mb-6">
                    <p class="text-sm text-red-600 dark:text-red-400 font-medium">
                        {{ $record->errors[0]['error'] ?? 'Ocurrió un error inesperado durante la importación.' }}
                    </p>
                </div>

                <div class="flex gap-3 justify-end">
                    <x-ui.button variant="secondary" type="ghost" wire:click="resetWizard">
                        Intentar de nuevo
                    </x-ui.button>
                </div>
            @endif

        </div>
    @endif

</div>
