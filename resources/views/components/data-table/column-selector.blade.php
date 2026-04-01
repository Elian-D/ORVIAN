{{--
    resources/views/components/data-table/column-selector.blade.php
    ----------------------------------------------------------------

    ANÁLISIS DEL PROBLEMA ANTERIOR:
    ─────────────────────────────────
    1. @checked($isVisible) es PHP estático — se evalúa una sola vez en el render
       inicial. Cuando Livewire hace morfing parcial del DOM tras resetColumns()
       o toggleColumn(), PHP no re-corre el template completo para esos atributos,
       entonces el checkbox mantiene su estado visual anterior aunque $wire.visibleColumns
       haya cambiado. → SOLUCIÓN: x-bind:checked que lee $wire.visibleColumns en runtime.

    2. $isCustomized también era PHP estático — no detectaba cambios reactivos.
       → SOLUCIÓN: computed Alpine que lee $wire.visibleColumns directamente.

    3. Device detection imposible en PHP — el servidor no conoce el ancho del cliente.
       → SOLUCIÓN: Alpine detecta el dispositivo en init() y llama
         $wire.resetColumns(isMobile) para que el servidor use la lista correcta.
         DataTable::resetColumns() acepta un bool $mobile.

    CONTRATO REQUERIDO EN DataTable:
      resetColumns(bool $mobile = false): void
        → $mobile ? defaultMobile() : defaultDesktop()

    PROPS:
      definition       — clase TableConfig (::class)
      visibleColumns   — array de columnas visibles (prop de Livewire, pasada para PHP)
      desktopDefaults  — defaultDesktop() serializado para Alpine
      mobileDefaults   — defaultMobile() serializado para Alpine
--}}

@props([
    'definition'      => null,
    'visibleColumns'  => [],
])

@php
    $allColumns      = $definition ? $definition::allColumns() : [];
    $desktopDefaults = $definition ? json_encode($definition::defaultDesktop()) : '[]';
    $mobileDefaults  = $definition ? json_encode($definition::defaultMobile())  : '[]';
@endphp

<div
    x-data="{
        open: false,
        isMobile: window.innerWidth < 768,

        {{-- Computed: columnas visibles actuales desde Livewire (siempre en sync) --}}
        get visible() {
            return $wire.visibleColumns ?? [];
        },

        {{-- Computed: columnas por defecto según dispositivo actual --}}
        get currentDefaults() {
            return this.isMobile
                ? {{ $mobileDefaults }}
                : {{ $desktopDefaults }};
        },

        {{-- Computed: hay personalización respecto al default del dispositivo --}}
        get isCustomized() {
            const vis  = this.visible;
            const defs = this.currentDefaults;
            return vis.length !== defs.length
                || defs.some(d => !vis.includes(d))
                || vis.some(v => !defs.includes(v));
        },

        {{-- ¿Está visible esta columna? (para x-bind:checked) --}}
        isVisible(key) {
            return this.visible.includes(key);
        },

        {{-- ¿Es la última columna visible? (guard de mínimo) --}}
        isLastOne(key) {
            return this.isVisible(key) && this.visible.length === 1;
        },

        init() {
            {{-- Detectar redimensión --}}
            const mq = window.matchMedia('(max-width: 767px)');
            mq.addEventListener('change', (e) => {
                const wasMobile = this.isMobile;
                this.isMobile = e.matches;
                if (!this.isMobile) this.open = false;

                {{-- Si cambió de dispositivo, resetear columnas al default correcto --}}
                if (wasMobile !== this.isMobile) {
                    $wire.resetColumns(this.isMobile);
                }
            });

            {{--
                Al montar: si las columnas actuales del servidor son defaultDesktop()
                pero estamos en mobile, corregir al default mobile.
                Esto cubre el caso de carga inicial en mobile.
            --}}
            if (this.isMobile) {
                const serverCols   = JSON.stringify([...(this.visible)].sort());
                const desktopCols  = JSON.stringify([...{{ $desktopDefaults }}].sort());
                const mobileCols   = JSON.stringify([...{{ $mobileDefaults }}].sort());

                {{-- Solo resetear si el servidor mandó desktop defaults en un móvil --}}
                if (serverCols === desktopCols && serverCols !== mobileCols) {
                    $wire.resetColumns(true);
                }
            }
        }
    }"
    class="relative flex-shrink-0"
>

    {{-- ── Trigger ── --}}
    <button
        @click="open = !open"
        class="flex items-center gap-2 px-3 py-2 rounded-xl border text-sm font-semibold
               transition-all duration-200 focus:outline-none"
        :class="isCustomized
            ? 'border-orvian-orange/40 bg-orvian-orange/8 dark:bg-orvian-orange/10 text-orvian-orange'
            : 'border-slate-200 dark:border-dark-border bg-white dark:bg-dark-card text-slate-600 dark:text-slate-300 hover:border-slate-300 dark:hover:border-white/20'"
    >
        <x-heroicon-o-view-columns class="w-4 h-4" />
        <span class="hidden sm:block">Columnas</span>

        {{-- Badge: número de columnas cuando hay personalización --}}
        <span
            x-show="isCustomized"
            x-text="visible.length"
            class="flex items-center justify-center w-5 h-5 rounded-full
                   bg-orvian-orange text-white text-[10px] font-black leading-none flex-shrink-0"
        ></span>

        <x-heroicon-s-chevron-down
            class="w-3.5 h-3.5 transition-transform duration-200 hidden sm:block"
            ::class="open && !isMobile ? 'rotate-180' : ''" />
    </button>

    {{-- ══════════════════════════════════════════════════
         DESKTOP DROPDOWN
    ═══════════════════════════════════════════════════ --}}
    <div
        x-show="open && !isMobile"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
        x-cloak
        class="absolute right-0 top-full mt-2 z-50 w-60 rounded-2xl border shadow-2xl
               bg-white dark:bg-dark-card border-slate-100 dark:border-dark-border"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3
                    border-b border-slate-100 dark:border-dark-border">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                Columnas visibles
            </p>
            <button
                @click="$wire.resetColumns(isMobile)"
                class="text-[11px] font-semibold transition-colors"
                :class="isCustomized
                    ? 'text-orvian-orange hover:text-orvian-orange/70 cursor-pointer'
                    : 'text-slate-300 dark:text-slate-600 cursor-default pointer-events-none'"
            >
                Restablecer
            </button>
        </div>

        {{-- Lista — estado 100% Alpine, no PHP --}}
        <div class="p-3 space-y-0.5 max-h-64 overflow-y-auto custom-scroll">
            @foreach($allColumns as $key => $colLabel)
                <label
                    class="flex items-center gap-3 px-2 py-2 rounded-lg transition-colors"
                    :class="isLastOne('{{ $key }}')
                        ? 'opacity-50 cursor-not-allowed'
                        : 'cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5 group'"
                >
                    <input
                        type="checkbox"
                        {{-- x-bind:checked → reactivo a cambios de $wire.visibleColumns --}}
                        :checked="isVisible('{{ $key }}')"
                        :disabled="isLastOne('{{ $key }}')"
                        @change="!isLastOne('{{ $key }}') && $wire.toggleColumn('{{ $key }}')"
                        class="w-4 h-4 rounded border-slate-300 dark:border-dark-border
                               text-orvian-orange focus:ring-orvian-orange focus:ring-offset-0
                               dark:bg-dark-bg transition-colors"
                        :class="isLastOne('{{ $key }}') ? 'cursor-not-allowed' : 'cursor-pointer'"
                    />
                    <span
                        class="text-sm text-slate-700 dark:text-slate-300 transition-colors"
                        :class="!isLastOne('{{ $key }}') ? 'group-hover:text-orvian-orange' : ''"
                    >
                        {{ $colLabel }}
                    </span>
                    <span
                        x-show="isLastOne('{{ $key }}')"
                        class="ml-auto text-[10px] text-slate-400 dark:text-slate-600"
                    >mín.</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         MOBILE OVERLAY
    ═══════════════════════════════════════════════════ --}}
    <div
        x-show="open && isMobile"
        x-cloak
        @click="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm md:hidden"
    ></div>

    {{-- ══════════════════════════════════════════════════
         MOBILE DRAWER
    ═══════════════════════════════════════════════════ --}}
    <div
        x-show="open && isMobile"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 z-50 rounded-t-3xl shadow-2xl
               bg-white dark:bg-dark-card border-t border-slate-100 dark:border-dark-border
               md:hidden"
    >
        <div class="flex flex-col items-center pt-3">
            <div class="w-10 h-1 rounded-full bg-slate-200 dark:bg-dark-border mb-3"></div>
            <div class="w-full flex items-center justify-between px-5 pb-3
                        border-b border-slate-100 dark:border-dark-border">
                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Columnas visibles</p>
                <div class="flex items-center gap-3">
                    <button
                        @click="$wire.resetColumns(true); open = false"
                        class="text-xs font-semibold transition-colors"
                        :class="isCustomized
                            ? 'text-orvian-orange'
                            : 'text-slate-300 dark:text-slate-600 pointer-events-none'"
                    >
                        Restablecer
                    </button>
                    <button @click="open = false" class="text-slate-400 dark:text-slate-500">
                        <x-heroicon-s-x-mark class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        <div class="px-4 py-3 space-y-0.5 max-h-[60vh] overflow-y-auto custom-scroll">
            @foreach($allColumns as $key => $colLabel)
                <label
                    class="flex items-center gap-3 px-2 py-3 rounded-xl transition-colors"
                    :class="isLastOne('{{ $key }}')
                        ? 'opacity-50 cursor-not-allowed'
                        : 'cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5 group'"
                >
                    <input
                        type="checkbox"
                        :checked="isVisible('{{ $key }}')"
                        :disabled="isLastOne('{{ $key }}')"
                        @change="!isLastOne('{{ $key }}') && $wire.toggleColumn('{{ $key }}')"
                        class="w-4 h-4 rounded border-slate-300 dark:border-dark-border
                               text-orvian-orange focus:ring-orvian-orange dark:bg-dark-bg"
                        :class="isLastOne('{{ $key }}') ? 'cursor-not-allowed' : 'cursor-pointer'"
                    />
                    <span
                        class="text-sm text-slate-700 dark:text-slate-300 transition-colors"
                        :class="!isLastOne('{{ $key }}') ? 'group-hover:text-orvian-orange' : ''"
                    >
                        {{ $colLabel }}
                    </span>
                </label>
            @endforeach
        </div>

        <div class="px-5 pb-6 pt-3 border-t border-slate-100 dark:border-dark-border">
            <button
                @click="open = false"
                class="w-full py-3 rounded-xl bg-orvian-orange text-white text-sm font-bold
                       hover:opacity-90 active:scale-[0.98] transition-all">
                Listo
            </button>
        </div>
    </div>

</div>