{{--
    resources/views/livewire/tenant/school-wizard.blade.php
    -------------------------------------------------------
    Vista compartida para SchoolWizard (5 pasos / Owner) y TenantSetupWizard (4 pasos / Usuario).

    REGLA FUNDAMENTAL DE ARQUITECTURA (aprendida del wizard anterior que funcionaba):
    ─────────────────────────────────────────────────────────────────────────────────
    ✅ TODO el HTML está SIEMPRE en el DOM — no se usa @if para bloques grandes.
    ✅ Alpine x-show controla la visibilidad (intro vs wizard vs pasos).
    ✅ Sin x-data en el div raíz — evita el problema de scope de función en @script.
    ✅ Alpine.data() para registrar componentes, no function declarations globales.
    ✅ style="display:none;" en secciones ocultas por defecto para evitar flash.

    ❌ NO usar @if/$showIntro para la intro/wizard — Livewire morfea el DOM al cambiar
       y Alpine no re-inicializa $wire correctamente en los nodos recién insertados.
    ❌ NO usar x-data="misFuncion()" donde misFuncion se declara en @script con
       `function` — esas declaraciones NO son globales en el scope de Livewire 3.
--}}

@php
    $stepNames = [
        1 => ['name' => 'Identidad',  'sub' => 'Datos del centro'],
        2 => ['name' => 'Ubicación',  'sub' => 'Geografía y dirección'],
        3 => ['name' => 'Académico',  'sub' => 'Niveles, tandas y año'],
        4 => ['name' => 'Plan',       'sub' => 'Suscripción y features'],
        5 => ['name' => 'Director',   'sub' => 'Cuenta del responsable'],
    ];

    $nextStepLabel = match($step ?? 1) {
        1 => 'Ubicación',
        2 => 'Configuración Académica',
        3 => 'Selección de Plan',
        4 => $totalSteps === 5 ? 'Datos del Director' : null,
        default => null,
    };
@endphp

{{-- ── Sin x-data en el raíz. x-show referencia $wire directamente. ── --}}
<div class="flex flex-col md:flex-row gap-8 w-full">

    {{-- ══════════════════════════════════════════════════
         SIDEBAR — siempre en el DOM, x-show oculta durante intro
         hidden md:flex: hidden en mobile, flex en md+
         style="display:none;" → Alpine lo muestra cuando showIntro=false
    ═══════════════════════════════════════════════════════ --}}
    @include('livewire.tenant.wizard._sidebar')
    
    {{-- ══════════════════════════════════════════════════
         CARD PRINCIPAL — siempre en el DOM
    ═══════════════════════════════════════════════════════ --}}
    <div class="flex-1 bg-white/80 dark:bg-slate-900/60 backdrop-blur-2xl border border-slate-200 dark:border-white/10 rounded-[2rem] shadow-2xl flex flex-col overflow-hidden min-h-[600px]">

        {{-- ────────────────────────────────────────────────
             PANTALLA DE INTRO
             Visible por defecto (showIntro = true en primer render).
             Sin style="display:none;" — es lo que se ve primero.
             Alpine lo oculta cuando showIntro cambia a false.
        ─────────────────────────────────────────────────── --}}
        @include('livewire.tenant.wizard._intro')

        {{-- ────────────────────────────────────────────────
             WIZARD — pasos 1 a N
             Oculto por defecto (style="display:none;"), Alpine lo muestra
             cuando showIntro cambia a false. SIN morph de Livewire.
             Alpine ya tiene $wire inicializado en todos los nodos hijos.
        ─────────────────────────────────────────────────── --}}
        <div x-show="!$wire.showIntro" style="display:none;" class="flex flex-col flex-1">
             @include('livewire.tenant.wizard._intro')
             
             {{-- Header del paso activo --}}
             @include('livewire.tenant.wizard._header')
             
             

            {{-- ─── Contenido de pasos ─────────────────────────────
                 Todos los pasos están SIEMPRE en el DOM.
                 x-show controla cuál es visible (igual que el wizard anterior).
                 Pasos 2-5: style="display:none;" para evitar flash inicial.
            ────────────────────────────────────────────────────── --}}
            <div class="px-8 py-7 flex-1 overflow-y-auto">

                {{-- ─────────────────────────────────────────
                     PASO 1 — Identidad Institucional
                     Sin style="display:none;" — es el paso inicial
                ───────────────────────────────────────────── --}}
                @include('livewire.tenant.wizard.steps._step-1-identity')

                {{-- ─────────────────────────────────────────
                     PASO 2 — Ubicación
                ───────────────────────────────────────────── --}}
                @include('livewire.tenant.wizard.steps._step-2-location')
                {{-- ─────────────────────────────────────────
                     PASO 3 — Configuración Académica
                ───────────────────────────────────────────── --}}

                @include('livewire.tenant.wizard.steps._step-3-academic')
                
                {{-- ─────────────────────────────────────────
                    PASO 4 — Selección de Plan (Dinámico)
                ───────────────────────────────────────────── --}}
                @include('livewire.tenant.wizard.steps._step-4-plan')
                
                {{-- ─────────────────────────────────────────
                     PASO 5 — Director (solo SchoolWizard / Owner)
                     En TenantSetupWizard $totalSteps=4 → $wire.step nunca llega a 5
                     ───────────────────────────────────────────── --}}
                @include('livewire.tenant.wizard.steps._step-5-director')



            </div>{{-- /contenido pasos --}}

            {{-- ── Footer de navegación ── --}}
            @include('livewire.tenant.wizard._footer-nav')

        </div>{{-- /wizard --}}

    </div>{{-- /card principal --}}

{{-- ══════════════════════════════════════════════════
         PANTALLA DE PROGRESO
         x-data="progressScreen" (sin paréntesis) — usa Alpine.data()
         Alpine.data() es el registro correcto; no depende de scope global.
    ═══════════════════════════════════════════════════════ --}}
@include('livewire.tenant.wizard._progress-screen')

</div>

@script
<script>
/**
 * Alpine.data() es la forma CORRECTA de registrar componentes Alpine en Livewire 3.
 * Con `function progressScreen() {}` la función queda en el scope del módulo de
 * script y NO es accesible globalmente. Alpine.data() la registra internamente.
 *
 * Uso en HTML: x-data="progressScreen" (sin paréntesis).
 *
 * El beforeunload está aquí mismo, no hace falta un componente raíz separado.
 */
Alpine.data('progressScreen', () => ({
    progress: 0,
    messageIndex: 0,
    durationSeconds: 30,
    timer: null,
    _unloadFn: null,
    messages: [
        'Inicializando entorno del centro educativo...',
        'Creando estructura académica base...',
        'Configurando niveles y grados...',
        'Asignando roles y permisos...',
        'Generando año escolar inicial...',
        'Vinculando plan de suscripción...',
        'Preparando accesos del director...',
        '¡Casi listo! Aplicando configuración final...',
    ],

    init() {
        // Definir el handler una sola vez para poder removerlo
        this._unloadFn = (e) => {
            e.preventDefault();
            e.returnValue = '⚠️ La configuración está en proceso. Si cierras esta ventana, perderás el progreso.';
            return e.returnValue;
        };

        this.$watch('$wire.isProcessing', val => {
            if (val) {
                window.addEventListener('beforeunload', this._unloadFn);
                this.startProgress();
            } else {
                window.removeEventListener('beforeunload', this._unloadFn);
            }
        });

        // Por si el componente monta con isProcessing ya en true (ej: recarga)
        if (this.$wire.isProcessing) {
            window.addEventListener('beforeunload', this._unloadFn);
            this.startProgress();
        }
    },

    get currentMessage() {
        return this.messages[this.messageIndex] ?? this.messages[this.messages.length - 1];
    },

    get timeLabel() {
        const elapsed = (this.progress / 100) * this.durationSeconds;
        const remaining = Math.max(0, Math.ceil(this.durationSeconds - elapsed));
        return remaining > 0 ? `Aprox. ${remaining}s restantes` : 'Redirigiendo...';
    },

    startProgress() {
        if (this.timer) return;
        const increment = 100 / this.durationSeconds;

        this.timer = setInterval(() => {
            this.progress = Math.min(this.progress + increment, 99);
            const msgStep = Math.floor(this.progress / (100 / this.messages.length));
            this.messageIndex = Math.min(msgStep, this.messages.length - 1);

            if (this.progress >= 99) {
                clearInterval(this.timer);
                this.timer = null;
                this.progress = 100;
                window.removeEventListener('beforeunload', this._unloadFn);
                setTimeout(() => {
                    window.location.href = '{{ route('app.dashboard') }}';
                }, 1200);
            }
        }, 1000);
    }
}));
</script>
@endscript