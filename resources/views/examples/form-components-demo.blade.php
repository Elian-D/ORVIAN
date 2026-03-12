{{--
    resources/views/examples/form-components-demo.blade.php
    -------------------------------------------------------
    Formulario de demostración de los componentes x-ui.forms.*
    Sin lógica real — solo para revisar el diseño en contexto.
    Acceder vía una ruta temporal: Route::view('/demo/form', 'examples.form-components-demo');
--}}

<x-app-layout title="Demo — Componentes de Formulario">

<div class="max-w-2xl mx-auto py-12 px-4 space-y-12">

    {{-- ── Encabezado ─────────────────────────────────────────────── --}}
    <div class="space-y-1">
        <p class="text-[11px] font-bold uppercase tracking-widest text-orvian-orange">
            Componentes UI
        </p>
        <h1 class="text-2xl font-bold text-orvian-navy dark:text-white">
            Formulario de Demostración
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Revisión visual de los componentes <code class="text-orvian-orange">x-ui.forms.*</code>
        </p>
    </div>

    {{-- Separador de sección --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">01 · Inputs de Texto</span>
        <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
    </div>

    {{-- ── Inputs ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">

        {{-- Estado normal --}}
        <x-ui.forms.input
            label="Nombre del Centro"
            name="name"
            placeholder="Ej. Liceo Juan Pablo Duarte"
            icon-left="heroicon-o-building-library"
            hint="Nombre oficial según el MINERD"
        />

        {{-- Con icono derecho --}}
        <x-ui.forms.input
            label="Código SIGERD"
            name="sigerd_code"
            placeholder="Ej. 08-0012-0034"
            icon-left="heroicon-o-hashtag"
            icon-right="heroicon-o-information-circle"
            hint="Código único del sistema SIGERD"
        />

        {{-- Estado error --}}
        <x-ui.forms.input
            label="Correo Electrónico"
            name="email"
            type="email"
            placeholder="director@escuela.edu.do"
            icon-left="heroicon-o-envelope"
            error="Este correo ya está registrado en el sistema"
        />

        {{-- Password con slot --}}
        <div x-data="{ show: false }" class="flex flex-col group">
            <label class="text-[11px] font-bold uppercase tracking-wider mb-2 text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                Contraseña <span class="text-state-error ml-0.5">*</span>
            </label>
            <div class="relative flex items-center">
                <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                    <x-heroicon-o-lock-closed class="w-5 h-5" />
                </span>
                <input
                    :type="show ? 'text' : 'password'"
                    name="password"
                    placeholder="Mínimo 8 caracteres"
                    class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent rounded-none pl-7 pr-7 py-3 text-sm text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors"
                />
                <button
                    type="button"
                    @click="show = !show"
                    class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 hover:text-orvian-orange transition-colors"
                >
                    <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                    <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" />
                </button>
            </div>
        </div>

        {{-- Disabled --}}
        <x-ui.forms.input
            label="Usuario generado"
            name="username"
            placeholder="@director_duarte"
            icon-left="heroicon-o-at-symbol"
            hint="Se genera automáticamente"
            :disabled="true"
        />

        {{-- Número --}}
        <x-ui.forms.input
            label="Teléfono"
            name="phone"
            type="tel"
            placeholder="(809) 000-0000"
            icon-left="heroicon-o-phone"
        />

    </div>

    {{-- Separador de sección --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">02 · Selects</span>
        <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
    </div>

    {{-- ── Selects ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">

        <x-ui.forms.select
            label="Regional Educativa"
            name="regional_education_id"
            icon-left="heroicon-o-map"
            required
        >
            <option value="08">Regional 08 Santiago</option>
            <option value="15">Regional 15 Mao</option>
        </x-ui.forms.select>

        <x-ui.forms.select
            label="Modalidad"
            name="modalidad"
            icon-left="heroicon-o-academic-cap"
            error="Debes seleccionar una modalidad"
        >
            <option value="academica">Liceo (Académica)</option>
            <option value="tecnico">Politécnico (Técnico-Profesional)</option>
        </x-ui.forms.select>

        <x-ui.forms.select
            label="Provincia"
            name="province_id"
            icon-left="heroicon-o-globe-americas"
            hint="Filtrará los municipios disponibles"
        >
            <option value="1">Santiago</option>
            <option value="2">Santo Domingo</option>
        </x-ui.forms.select>

        <x-ui.forms.select
            label="Plan"
            name="plan_id"
            icon-left="heroicon-o-credit-card"
            :disabled="true"
            hint="Seleccionado en el paso anterior"
        >
            <option value="pro">Pro — RD$ 2,500/mes</option>
        </x-ui.forms.select>

    </div>

    {{-- Separador --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">03 · Textarea</span>
        <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
    </div>

    {{-- ── Textarea ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">

        <x-ui.forms.textarea
            label="Referencias de Ubicación"
            name="address_reference"
            placeholder="Ej. Frente al parque central, al lado del banco..."
            :rows="3"
            hint="Opcional — ayuda a identificar el centro"
        />

        <x-ui.forms.textarea
            label="Observaciones"
            name="observations"
            placeholder="Comentarios adicionales..."
            :rows="3"
            :resize="true"
            error="El campo no puede superar 500 caracteres"
        />

    </div>

    {{-- Separador --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">04 · Checkboxes &amp; Radios</span>
        <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
    </div>

    {{-- ── Checkboxes ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

        <div class="flex flex-col gap-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Niveles educativos</p>
            <x-ui.forms.checkbox
                label="Primaria"
                name="levels[]"
                value="1"
                description="Primer ciclo educativo (1ro – 6to)"
            />
            <x-ui.forms.checkbox
                label="Secundaria Primer Ciclo"
                name="levels[]"
                value="2"
                :checked="true"
            />
            <x-ui.forms.checkbox
                label="Secundaria Segundo Ciclo"
                name="levels[]"
                value="3"
                :checked="true"
            />
            <x-ui.forms.checkbox
                label="Nivel no disponible (deshabilitado)"
                name="levels[]"
                value="4"
                :disabled="true"
            />
        </div>

        <div class="flex flex-col gap-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Régimen de Gestión</p>
            <x-ui.forms.radio
                label="Público"
                name="regimen_gestion"
                value="Público"
                description="Centro de gestión estatal"
                :checked="true"
            />
            <x-ui.forms.radio
                label="Privado"
                name="regimen_gestion"
                value="Privado"
                description="Centro de gestión privada"
            />
            <x-ui.forms.radio
                label="Semioficial"
                name="regimen_gestion"
                value="Semioficial"
                description="Gestión mixta público-privada"
            />
        </div>

    </div>

    {{-- Separador --}}
    <div class="flex items-center gap-4">
        <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">05 · Toggles</span>
        <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
    </div>

    {{-- ── Toggles ─────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-6">

        <x-ui.forms.toggle
            label="Notificaciones por correo"
            name="email_notifications"
            description="Recibe alertas de nuevas matrículas y actividad del sistema"
        />

        <div class="h-px bg-slate-100 dark:bg-dark-border"></div>

        <x-ui.forms.toggle
            label="Modo Avanzado"
            name="advanced_mode"
            description="Activa opciones adicionales de configuración académica"
            :checked="true"
        />

        <div class="h-px bg-slate-100 dark:bg-dark-border"></div>

        <x-ui.forms.toggle
            label="Sincronización automática con SIGERD"
            name="sigerd_sync"
            description="No disponible en tu plan actual"
            :disabled="true"
        />

    </div>

    {{-- Separador --}}
    <div class="h-px bg-slate-100 dark:bg-dark-border"></div>

    {{-- ── Footer del formulario ──────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4 pt-2">
        <x-ui.button variant="secondary" type="outline" icon-left="heroicon-s-arrow-left">
            Cancelar
        </x-ui.button>
        <x-ui.button variant="primary" :hover-effect="true" icon-right="heroicon-s-arrow-right">
            Guardar Cambios
        </x-ui.button>
    </div>

</div>

</x-app-layout>
