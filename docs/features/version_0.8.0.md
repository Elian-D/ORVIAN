# ORVIAN v0.8.0 — Estabilidad de Demo y Detalles Finos

**RAMA PADRE:** `feature/v0.8.0-demo-polish`

**Objetivo:** Preparar el sistema para la demo final del 12 de mayo eliminando dependencias externas en el kiosko de asistencia, puliendo la experiencia de usuario con audio feedback y pantalla completa, rediseñando el carnet QR para impresión profesional, e introduciendo mejoras visuales en el plan de precios y la navegación móvil. No se incorporan módulos nuevos — el foco es estabilidad, impresión y detalles que se notan en vivo.

> **Restricción temporal:** 2 días de desarrollo. Las 8 tareas están ordenadas por prioridad de riesgo: las de mayor impacto en la demo van primero. Ejecutar el seeder (Fase 8) al final, cuando el resto del sistema esté estable.

---

## Estado de la Base — v0.7.0 como Fundación

| Componente | Origen | Estado |
| :--- | :--- | :--- |
| Scanner visor (`scanner-visor.blade.php`) con face-api.js vía CDN | v0.4.0 | ⚠️ Reemplazar — CDN bloqueado por Fortinet |
| `AttendanceDashboard.php` con `loadWeeklyStats()` fijo a `today()` | v0.4.0 | ⚠️ Ajustar — debe seguir la fecha seleccionada |
| `ProfileModal.php` con preferencias `theme` y `login_version` | v0.4.1 | ✅ Base sólida — agregar `audio_feedback` |
| `qr-sheet.blade.php` con diseño simple | v0.4.0 | ⚠️ Rediseñar — carnet institucional b/n |
| `Feature.php` con `getIcon()` retornando `heroicon-*` | v0.4.0 | ⚠️ Actualizar — usar slugs de módulo |
| Navbar app (`navbar.blade.php`) y admin (`layout.blade.php`) | v0.3.0 | ✅ Agregar botón fullscreen |
| Menú móvil de la landing (`navigation.blade.php`) | v0.7.0 | ⚠️ Fix scroll en pantallas pequeñas |

---

## Tabla de Requerimientos

| ID | Fase | Área | Descripción | Prioridad | Estado |
| :-- | :-- | :-- | :-- | :-- | :-- |
| REQ-01 | 1 | Kiosko | Servir face-api.js y sus modelos localmente — eliminar dependencia de CDN | Crítica | Pendiente |
| REQ-02 | 2 | UX | Audio feedback de éxito/error en registro de asistencia | Alta | Pendiente |
| REQ-03 | 3 | UI | Botón de pantalla completa en navbar app y admin | Alta | Pendiente |
| REQ-04 | 4 | Dashboard | Gráfico de línea respeta fecha seleccionada del calendario | Media | Pendiente |
| REQ-05 | 5 | Print | Rediseño del carnet QR en blanco y negro estilo institucional | Alta | Pendiente |
| REQ-06 | 6 | UI | Iconos de módulo reales en cards de planes | Media | Pendiente |
| REQ-07 | 7 | Mobile | Fix scroll en menú móvil de la landing | Media | Pendiente |
| REQ-08 | 8 | Demo | Comando `orvian:seed-demo` — seeder maestro con 30 días de historial de asistencia | Crítica | Pendiente |

---

## Fase 1 — face-api.js Local (Estabilidad Kiosko)
**Rama:** `feature/v0.8.0-faceapi-local`

### Estrategia

face-api.js ya funciona y el código Alpine que lo usa está probado en producción. El único problema es que el `<script>` carga desde `cdn.jsdelivr.net` y los modelos desde `cdn.jsdelivr.net/npm/@vladmandic/face-api/model` — ambas URLs bloqueadas por Fortinet en la red de la escuela. La solución es servir el script y los modelos desde `public/`, sin tocar ninguna lógica del detector ni del dwell time.

**Lo que NO cambia:** absolutamente nada del código Alpine — ni inicialización, ni loop, ni `captureFace()`, ni dwell time, ni cleanup de streams.

**Lo que SÍ cambia:** la URL del `<script>` en la vista y la constante `MODEL_URL` en el init.

---

### 1.1 — Descargar face-api.js al proyecto

```bash
# Crear el directorio si no existe
mkdir -p public/vendor/face-api

# Descargar el script minificado (~650KB) — una sola vez, commitear al repo
curl -L -o public/vendor/face-api/face-api.min.js \
  "https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"

# Verificar que pesa entre 600-700KB
ls -lh public/vendor/face-api/face-api.min.js
```

---

### 1.2 — Descargar los modelos localmente

Los modelos de `@vladmandic/face-api` son los que usa el sistema. Hay que descargar solo el modelo `tiny_face_detector` que es el que usa el código (`~190KB` en total — 2 archivos).

```bash
# Crear directorio de modelos
mkdir -p public/vendor/face-api/models

# Descargar el manifest del modelo
curl -L -o public/vendor/face-api/models/tiny_face_detector_model-weights_manifest.json \
  "https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/tiny_face_detector_model-weights_manifest.json"

# Descargar los pesos del modelo (el archivo .bin referenciado en el manifest)
# Primero ver qué archivo .bin referencia el manifest:
cat public/vendor/face-api/models/tiny_face_detector_model-weights_manifest.json
```

El manifest referencia un archivo `.bin`. Descargarlo con el nombre exacto que aparece en el JSON:

```bash
# El nombre del .bin suele ser tiny_face_detector_model.bin o similar
# Reemplazar <nombre_del_bin> con lo que aparece en el manifest
curl -L -o "public/vendor/face-api/models/<nombre_del_bin>" \
  "https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/<nombre_del_bin>"
```

**Alternativa más rápida** — descargar todo el directorio de modelos de una vez usando npm:

```bash
# Instalar el paquete solo para extraer los modelos (no lo importamos en app.js)
npm install @vladmandic/face-api --save-dev

# Copiar los modelos al directorio público
cp node_modules/@vladmandic/face-api/model/tiny_face_detector* public/vendor/face-api/models/

# Verificar
ls public/vendor/face-api/models/
# tiny_face_detector_model-weights_manifest.json
# tiny_face_detector_model-shard1  (o similar)
```

Los modelos ocupan ~190KB en total. Commitear `public/vendor/face-api/` al repositorio para que estén disponibles offline en la demo.

---

### 1.3 — Actualizar `scanner-visor.blade.php`

Dos cambios en el archivo:

**Cambio A — El `<script>` del CDN:**

```blade
{{-- ANTES (CDN externo — bloqueado por Fortinet): --}}
<script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

{{-- DESPUÉS (servido localmente): --}}
<script defer src="{{ asset('vendor/face-api/face-api.min.js') }}"></script>
```

**Cambio B — La `MODEL_URL` dentro de `initFacialScanner()`:**

```js
// ANTES (CDN externo — bloqueado por Fortinet):
const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';

// DESPUÉS (modelos locales):
const MODEL_URL = '/vendor/face-api/models';
```

Eso es todo. El resto del código Alpine — `TinyFaceDetectorOptions`, `detectAllFaces`, dwell time, canvas, `captureFace` — queda exactamente igual.

---

### 1.4 — Verificación

Después de aplicar los cambios:

1. Abre el kiosko en modo Facial
2. Abre DevTools → Network → filtrar por `face-api`
3. Debes ver que carga desde `localhost` o `orvian.test`, no desde `cdn.jsdelivr.net`
4. El bounding box amarillo debe aparecer al detectar un rostro

Si Fortinet también bloquea `orvian.test` en algún recurso estático, verificar que el servidor web (nginx/apache dentro de Sail) sirve correctamente los archivos de `public/vendor/`.


## Fase 2 — Feedback de Audio (Éxito / Error)
**Rama:** `feature/v0.8.0-audio-feedback`

### Estrategia

Sonidos nativos servidos desde `public/assets/sounds/`. La preferencia `audio_feedback` se almacena en el JSON `preferences` del usuario. Un Alpine component global (`audioFeedback`) escucha los eventos de Livewire y reproduce el archivo correspondiente. El QR no dispara sonido de error porque un código no reconocido es diferente a un rostro no reconocido — el QR siempre decodifica un valor válido.

---

### 2.1 — Archivos de audio

Coloca dos archivos en `public/assets/sounds/`:
- `success.wav` — tono corto y positivo (1 beep)
- `error.wav` — tono negativo (doble beep grave, "dum dum")

Puedes generarlos gratis en [freesound.org](https://freesound.org) o usar cualquier WAV de menos de 50KB.

---

### 2.2 — Preferencia en `ProfileModal.php`

```php
// app/Livewire/Shared/ProfileModal.php
// Agregar la propiedad pública junto a $loginVersion:

public bool $audioFeedback = true;

// En loadUserData(), agregar:
$this->audioFeedback = (bool) $user->preference('audio_feedback', true);

// En savePreferences(), agregar dentro del array $preferences:
$preferences['audio_feedback'] = $this->audioFeedback;
```

---

### 2.3 — Toggle en `profile-modal.blade.php`

Agregar dentro de la pestaña "Preferencias", junto al selector de versión de login:

```blade
{{-- Dentro de la sección de preferencias de profile-modal.blade.php --}}

{{-- Separador --}}
<div class="border-t border-slate-100 dark:border-white/5 pt-5 mt-5">

    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                Sonidos de feedback
            </p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                Reproduce un sonido al registrar asistencia
            </p>
        </div>

        {{-- Toggle reutilizando el mismo patrón visual del sistema --}}
        <button
            type="button"
            role="switch"
            :aria-checked="$wire.audioFeedback.toString()"
            @click="$wire.audioFeedback = !$wire.audioFeedback"
            :class="$wire.audioFeedback
                ? 'bg-orvian-orange shadow-sm shadow-orvian-orange/30'
                : 'bg-slate-200 dark:bg-slate-700'"
            class="relative inline-flex h-6 w-11 items-center rounded-full
                   transition-colors duration-200 focus:outline-none
                   focus:ring-2 focus:ring-orvian-orange focus:ring-offset-2
                   dark:focus:ring-offset-[#080e1a]"
        >
            <span
                :class="$wire.audioFeedback ? 'translate-x-6' : 'translate-x-1'"
                class="inline-block h-4 w-4 transform rounded-full bg-white
                       shadow-sm transition-transform duration-200"
            ></span>
        </button>
    </div>

    {{-- Botón de prueba --}}
    <button
        type="button"
        @click="
            const audio = new Audio('/assets/sounds/success.wav');
            audio.volume = 0.5;
            audio.play().catch(() => {});
        "
        class="mt-3 flex items-center gap-2 text-xs font-semibold
               text-slate-400 hover:text-orvian-orange transition-colors"
    >
        <x-heroicon-o-speaker-wave class="w-4 h-4" />
        Probar sonido
    </button>
</div>
```

---

### 2.4 — Componente Alpine global de audio

Agregar en `layouts/app-module.blade.php` (o en el layout base del módulo de asistencia), fuera de cualquier `wire:ignore`:

```blade
{{-- resources/views/layouts/app-module.blade.php --}}
{{-- Agregar en el <body>, después de x-ui.toasts --}}

<div
    x-data="audioFeedback()"
    x-init="init()"
    style="display:none;"
    aria-hidden="true"
></div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('audioFeedback', () => ({
        // Lee la preferencia del usuario desde la meta tag que inyectamos
        // en el layout (ver paso 2.5). Fallback: true.
        enabled: document.querySelector('meta[name="audio-feedback"]')?.content !== 'false',

        sounds: {
            success: new Audio('/assets/sounds/success.wav'),
            error:   new Audio('/assets/sounds/error.wav'),
        },

        init() {
            // Pre-cargar para que el primer disparo no tenga latencia
            Object.values(this.sounds).forEach(s => {
                s.volume = 0.5;
                s.load();
            });

            // Escuchar eventos emitidos por los componentes Livewire de asistencia
            Livewire.on('attendance-recorded-success', () => this.play('success'));
            Livewire.on('attendance-facial-error',     () => this.play('error'));
            // Nota: QR no dispara 'error' — un QR desconocido genera flash visual,
            //       no sonido de error, para no molestar al operador.
        },

        play(type) {
            if (!this.enabled) return;
            const sound = this.sounds[type];
            if (!sound) return;

            // Reiniciar si el sonido anterior aún está reproduciendo
            sound.currentTime = 0;
            sound.play().catch(() => {
                // Autoplay bloqueado por el navegador — silencioso, no crítico
            });
        },
    }));
});
</script>
@endpush
```

---

### 2.5 — Meta tag de preferencia en el layout

```blade
{{-- resources/views/layouts/app-module.blade.php — dentro del <head> --}}
{{-- Inyecta la preferencia del usuario para que Alpine la lea sin consulta adicional --}}

<meta name="audio-feedback" content="{{ auth()->user()?->preference('audio_feedback', true) ? 'true' : 'false' }}">
```

---

### 2.6 — Disparar los eventos desde Livewire

En el componente Livewire que procesa el resultado del reconocimiento facial (el que recibe `facialCaptureReady` y llama al microservicio), agregar los dispatches:

```php
// En el método que procesa la verificación facial exitosa:
$this->dispatch('attendance-recorded-success');

// En el método que maneja el caso "rostro no reconocido":
$this->dispatch('attendance-facial-error');

// Para QR exitoso también:
// En el método que procesa qrCodeScanned con resultado positivo:
$this->dispatch('attendance-recorded-success');
// QR fallido: NO disparar 'attendance-facial-error' — solo flash visual.
```

---

## Fase 3 — Botón de Pantalla Completa
**Rama:** `feature/v0.8.0-fullscreen`

### Estrategia

Alpine.js nativo con la Fullscreen API del navegador. Un solo componente inline en cada navbar. Al presionar F11 nativo el estado de Alpine no se sincroniza, por eso también escuchamos el evento `fullscreenchange` del documento.

---

### 3.1 — En `resources/views/components/app/navbar.blade.php` (Tenant)

Agregar el botón en el bloque `{{-- DERECHA --}}`, junto al botón de notificaciones:

```blade
{{-- Botón de pantalla completa — agregar antes del separador | --}}
<div
    x-data="{
        isFullscreen: false,
        toggle() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        },
        init() {
            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = !!document.fullscreenElement;
            });
        }
    }"
>
    <button
        @click="toggle()"
        :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'"
        class="relative w-8 h-8 flex items-center justify-center rounded-lg
               text-slate-400 dark:text-slate-500
               hover:text-slate-700 dark:hover:text-slate-200
               hover:bg-slate-100 dark:hover:bg-white/5 transition-colors"
    >
        <x-heroicon-o-arrows-pointing-out x-show="!isFullscreen" class="w-4 h-4" />
        <x-heroicon-o-arrows-pointing-in  x-show="isFullscreen"  class="w-4 h-4" x-cloak />
    </button>
</div>
```

---

### 3.2 — En `resources/views/components/navbar/layout.blade.php` (Admin)

Agregar el mismo bloque en la sección de iconos de la derecha del navbar admin, junto al botón de notificaciones:

```blade
{{-- Agregar antes del <button> de la campana en el navbar admin --}}
<div
    x-data="{
        isFullscreen: false,
        toggle() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        },
        init() {
            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = !!document.fullscreenElement;
            });
        }
    }"
>
    <button
        @click="toggle()"
        :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'"
        class="p-2.5 rounded-xl bg-gray-100 dark:bg-dark-bg
               text-gray-500 dark:text-gray-400
               hover:text-orvian-orange transition relative"
    >
        <x-heroicon-o-arrows-pointing-out x-show="!isFullscreen" class="w-5 h-5" />
        <x-heroicon-o-arrows-pointing-in  x-show="isFullscreen"  class="w-5 h-5" x-cloak />
    </button>
</div>
```

---

## Fase 4 — Gráfico de Línea Dinámico (Sigue Fecha del Calendario)
**Rama:** `feature/v0.8.0-chart-date`

### Estrategia

`loadWeeklyStats()` actualmente hardcodea `today()->subDays(6)`. Se cambia para que use `$this->selectedDate` como punto de referencia. El evento `weekly-stats-updated` ya existe y Alpine ya lo escucha — solo cambia el punto de origen de los datos.

---

### 4.1 — `AttendanceDashboard.php`

```php
// app/Livewire/App/Attendance/AttendanceDashboard.php

// REEMPLAZAR el método loadWeeklyStats() completo:

public function loadWeeklyStats(): void
{
    $schoolId   = Auth::user()->school_id;
    // Cambio clave: usar $this->selectedDate en lugar de today()
    $anchorDate = Carbon::parse($this->selectedDate);

    $records = PlantelAttendanceRecord::where('school_id', $schoolId)
        ->whereDate('date', '>=', $anchorDate->copy()->subDays(6)->toDateString())
        ->whereDate('date', '<=', $anchorDate->toDateString())
        ->selectRaw('date, status, count(*) as total')
        ->groupBy('date', 'status')
        ->get();

    $byDate = $records->groupBy(fn ($r) => Carbon::parse($r->date)->toDateString());

    $stats = collect(range(6, 0))->map(function ($daysAgo) use ($byDate, $anchorDate) {
        $date    = $anchorDate->copy()->subDays($daysAgo);
        $dayMap  = $byDate->get($date->toDateString(), collect())->pluck('total', 'status');

        $present = ((int) ($dayMap[PlantelAttendanceRecord::STATUS_PRESENT] ?? 0))
                 + ((int) ($dayMap[PlantelAttendanceRecord::STATUS_LATE]    ?? 0));
        $total   = (int) $dayMap->sum();
        $rate    = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;

        return ['date' => $date->format('d/M'), 'rate' => $rate];
    })->values()->toArray();

    $this->weeklyStats = $stats;
    $this->dispatch('weekly-stats-updated', stats: $this->weeklyStats);
}
```

```php
// También agregar loadWeeklyStats() en el watcher de fecha:

public function selectDate(string $date): void
{
    $this->selectedDate  = $date;
    $this->calendarMonth = Carbon::parse($date)->startOfMonth()->toDateString();
    $this->loadAll(); // loadAll() ya llama loadWeeklyStats(), no hay cambio adicional
}
```

> `loadAll()` ya incluye `loadWeeklyStats()`, así que con el cambio del método es suficiente. El gráfico se actualizará automáticamente al seleccionar cualquier fecha del calendario.

---

### 4.2 — `attendance-charts.js` — Ajuste del título del eje X

El JS no necesita cambios funcionales porque ya escucha `weekly-stats-updated` y actualiza las categorías del eje X con las fechas que vienen del backend. Solo se recomienda agregar un comentario de documentación:

```js
// resources/js/charts/attendance-charts.js
// En el listener de 'weekly-stats-updated', la línea existente ya es correcta:

Livewire.on('weekly-stats-updated', ({ stats }) => {
    this.chart.updateOptions({
        // Las fechas del eje X ahora reflejan los 7 días previos
        // a la fecha seleccionada en el calendario, no necesariamente hoy.
        xaxis: { categories: stats.map((d) => d.date) },
    });
    this.chart.updateSeries([{
        name: 'Asistencia',
        data: stats.map((d) => parseFloat(d.rate.toFixed(1))),
    }]);
});
```

---

## Fase 5 — Rediseño del Carnet QR (Estilo Institucional B/N)
**Rama:** `feature/v0.8.0-qr-sheet`

### Estrategia

El carnet pasa de un layout simple a un diseño vertical rectangular institucional en blanco y negro puro, apto para impresoras de oficina sin cartuchos de color. El chunk se mantiene en 3 por fila para aprovechar el papel carta.

---

### 5.1 — `resources/views/printables/qr-sheet.blade.php`

Reemplazar el archivo completo:

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* ── Configuración de página ── */
        @page {
            size: letter portrait;
            margin: 0.8cm;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #fff;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Grid de carnets ── */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        td {
            padding: 5px;
            vertical-align: top;
            width: 33.33%;
        }

        /* ── Carnet individual ── */
        .carnet {
            border: 1.5px solid #000;
            border-radius: 6px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            min-height: 7cm;
            page-break-inside: avoid;
        }

        /* ── Header: Logo del centro ── */
        .carnet-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px 6px 4px;
            gap: 4px;
        }
        .carnet-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        /* ── Sub-header negro con texto blanco ── */
        .carnet-subheader {
            background: #000;
            color: #fff;
            text-align: center;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 6px;
            line-height: 1.2;
        }

        /* ── Cuerpo: QR code ── */
        .carnet-qr {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 8px 10px 4px;
        }
        .qr-img {
            width: 100px;
            height: 100px;
            display: block;
        }

        /* ── Nombre del estudiante ── */
        .carnet-name {
            text-align: center;
            padding: 2px 6px;
        }
        .student-name {
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -0.01em;
            line-height: 1.1;
            display: block;
        }
        .student-section {
            font-size: 8px;
            color: #444;
            margin-top: 2px;
            display: block;
        }

        /* ── Separador ── */
        .carnet-divider {
            border: none;
            border-top: 1px solid #000;
            margin: 5px 10px;
        }

        /* ── Footer: Matrícula + Año Escolar ── */
        .carnet-footer {
            display: flex;
            align-items: stretch;
            margin: 0 8px 4px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .footer-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4px 2px;
            gap: 1px;
        }
        .footer-col + .footer-col {
            border-left: 1px solid #ddd;
        }
        .footer-icon {
            width: 10px;
            height: 10px;
            opacity: 0.6;
        }
        .footer-label {
            font-size: 5.5px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #666;
        }
        .footer-value {
            font-size: 8px;
            font-weight: 700;
        }

        /* ── Eslogan inferior ── */
        .carnet-slogan {
            text-align: center;
            font-size: 6px;
            color: #555;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 3px 4px 6px;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <table>
        @foreach($students->chunk(3) as $row)
            <tr>
                @foreach($row as $student)
                    <td>
                        <div class="carnet">

                            {{-- ── Header: Logo ── --}}
                            <div class="carnet-header">
                                @if($school->logo_path)
                                    <img
                                        src="{{ public_path('storage/' . $school->logo_path) }}"
                                        class="carnet-logo"
                                        alt="{{ $school->name }}"
                                    />
                                @else
                                    <img
                                        src="{{ public_path('img/logos/logo-full-light.svg') }}"
                                        class="carnet-logo"
                                        alt="ORVIAN"
                                    />
                                @endif
                            </div>

                            {{-- ── Sub-header negro ── --}}
                            <div class="carnet-subheader">
                                {{ $school->name }}
                            </div>

                            {{-- ── QR Code ── --}}
                            <div class="carnet-qr">
                                @php
                                    $qrCode = base64_encode(
                                        \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                                            ->size(100)
                                            ->margin(0)
                                            ->generate($student->qr_code)
                                    );
                                @endphp
                                <img src="data:image/svg+xml;base64,{{ $qrCode }}" class="qr-img" />
                            </div>

                            {{-- ── Nombre y Sección ── --}}
                            <div class="carnet-name">
                                <span class="student-name">{{ $student->full_name }}</span>
                                <span class="student-section">{{ $student->section->full_label }}</span>
                            </div>

                            {{-- ── Separador ── --}}
                            <hr class="carnet-divider" />

                            {{-- ── Footer: Matrícula | Año Escolar ── --}}
                            <div class="carnet-footer">
                                <div class="footer-col">
                                    {{-- Ícono matrícula (SVG inline mínimo) --}}
                                    <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                    <span class="footer-label">ID Estudiante</span>
                                    <span class="footer-value">{{ str_pad($student->id, 8, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <div class="footer-col">
                                    {{-- Ícono año escolar --}}
                                    <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" />
                                        <line x1="8"  y1="2" x2="8"  y2="6" />
                                        <line x1="3"  y1="10" x2="21" y2="10" />
                                    </svg>
                                    <span class="footer-label">Año Académico</span>
                                    <span class="footer-value">{{ $academicYear ?? '2024-2025' }}</span>
                                </div>
                            </div>

                            {{-- ── Eslogan ── --}}
                            <div class="carnet-slogan">
                                Gestión inteligente, presencia real
                            </div>

                        </div>
                    </td>
                @endforeach

                {{-- Celdas vacías para completar la fila si hay menos de 3 --}}
                @for($i = 0; $i < (3 - count($row)); $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>
```

> **Nota:** El controlador que renderiza esta vista debe pasar `$academicYear` con el año escolar activo del centro. Si no existe aún, el fallback `'2024-2025'` se muestra automáticamente.

---

## Fase 6 — Iconos de Módulo Reales en Planes
**Rama:** `feature/v0.8.0-plan-icons`

### Estrategia

`Feature::getIcon()` actualmente retorna strings `heroicon-*` que se pasan a `x-dynamic-component`. Se cambia para retornar el slug del módulo (`asistencia`, `academico`, etc.) y `plan-card.blade.php` usa `x-ui.module-icon` en su lugar.

---

### 6.1 — `app/Models/Tenant/Feature.php`

```php
// Reemplazar el método getIcon() completo:

/**
 * Retorna el slug del módulo para ser usado con x-ui.module-icon.
 * Los SVGs viven en public/assets/icons/modules/{slug}.svg
 */
public function getIcon(): string
{
    return match ($this->slug) {
        'attendance_qr',
        'attendance_facial'     => 'asistencia',
        'academic_grades',
        'academic_excel_import' => 'academico',
        'classroom_internal'    => 'classroom',
        'reports_advanced'      => 'reportes',
        default                 => 'administracion',
    };
}
```

---

### 6.2 — `resources/views/components/ui/plan-card.blade.php`

```blade
{{-- Reemplazar el bloque del feature dentro del @foreach --}}

@foreach($plan->features as $feature)
    <div class="flex items-center gap-4 group/item">
        {{-- Antes: x-dynamic-component con heroicon-* --}}
        {{-- Ahora: x-ui.module-icon con el slug del módulo --}}
        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-slate-50 dark:bg-white/5
                    flex items-center justify-center
                    transition-colors group-hover/item:bg-white group-hover/item:shadow-sm">
            <x-ui.module-icon
                :name="$feature->getIcon()"
                class="w-5 h-5 opacity-60 group-hover/item:opacity-100 transition-opacity"
            />
        </div>
        <div class="flex flex-col">
            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ $feature->name }}</span>
            <span class="text-[10px] text-slate-400 uppercase font-medium tracking-tight">{{ $feature->module }}</span>
        </div>
    </div>
@endforeach
```

---

## Fase 7 — Fix Scroll en Menú Móvil de la Landing
**Rama:** `feature/v0.8.0-mobile-nav`

### Estrategia

El menú móvil abre un `<div>` con `position: absolute` que en pantallas pequeñas (< 400px de alto, frecuente en Android mid-range) corta los botones de acción en la parte inferior. Se agrega `overflow-y-auto max-h-[85vh]` al contenedor interno del menú.

---

### 7.1 — `resources/views/layouts/navigation.blade.php`

```blade
{{-- Localizar el div del Mobile Menu y modificar el div interno --}}

{{-- ANTES: --}}
<div class="bg-white dark:bg-dark-card border border-slate-200 dark:border-white/10 rounded-[2rem] shadow-2xl p-6 space-y-4">

{{-- DESPUÉS: agregar overflow-y-auto y max-h-[85vh] --}}
<div class="bg-white dark:bg-dark-card border border-slate-200 dark:border-white/10 rounded-[2rem] shadow-2xl p-6 space-y-4
            overflow-y-auto max-h-[85vh] custom-scroll">
```

> `custom-scroll` es la clase de scrollbar estilizado que ya existe en el proyecto desde v0.3.0. No requiere CSS adicional.

---

## Fase 8 — Seeder Maestro de Demo (`orvian:seed-demo`)
**Rama:** `feature/v0.8.0-demo-seeder`

### Estrategia

Un comando de Artisan que genera datos verosímiles para la demo en ~15 segundos. Detecta la estructura académica existente (secciones, tandas) para no pisar configuraciones reales, crea estudiantes distribuidos por sección, y genera 30 días de historial de asistencia con distribución realista. El resultado: el dashboard tiene gráficos con datos, el calendario tiene colores de estado en cada día, y el kiosko tiene estudiantes con QR para escanear en vivo.

**Distribución de asistencia simulada:** 80% Presente · 10% Tardanza · 5% Ausente · 5% Excusado  
**Métodos de registro:** 60% QR · 40% Facial (varía por día para que el historial se vea natural)

---

### 8.1 — Comando `app/Console/Commands/SeedDemoSchoolData.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Tenant\AttendanceExcuse;
use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\School;
use App\Models\Tenant\Student;
use App\Models\Tenant\Teacher;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedDemoSchoolData extends Command
{
    protected $signature   = 'orvian:seed-demo
                                {--school_id= : ID de la escuela a poblar (requerido)}
                                {--students=75 : Cantidad de estudiantes a crear si no existen (25-150)}
                                {--days=30 : Días de historial de asistencia a generar (1-90)}
                                {--fresh : Eliminar registros de asistencia anteriores antes de generar}';

    protected $description = 'Genera datos de demostración realistas para una escuela: estudiantes, profesores y 30 días de historial de asistencia.';

    // ── Constantes de distribución ────────────────────────────────
    private const DIST_PRESENT = 80;
    private const DIST_LATE    = 10;
    private const DIST_ABSENT  =  5;
    private const DIST_EXCUSED =  5;

    public function handle(): int
    {
        $schoolId = (int) $this->option('school_id');

        if (!$schoolId) {
            $this->error('Debes indicar el ID de la escuela: --school_id=1');
            return self::FAILURE;
        }

        $school = School::find($schoolId);

        if (!$school) {
            $this->error("No se encontró la escuela con ID {$schoolId}.");
            return self::FAILURE;
        }

        $this->info("🏫  Escuela: {$school->name} (ID: {$schoolId})");
        $this->newLine();

        // Establecer el scope de tenant para todas las queries
        setPermissionsTeamId($schoolId);

        DB::transaction(function () use ($school, $schoolId) {

            // 1. Detectar estructura académica existente
            $sections = SchoolSection::where('school_id', $schoolId)
                ->where('is_active', true)
                ->with(['grade', 'shift'])
                ->get();

            $shifts = SchoolShift::where('school_id', $schoolId)->get();

            if ($sections->isEmpty()) {
                $this->warn('⚠️  No hay secciones activas. Completa primero el wizard de configuración.');
                return;
            }

            $this->line("  📚  Secciones detectadas: <comment>{$sections->count()}</comment>");
            $this->line("  🕐  Tandas detectadas: <comment>{$shifts->count()}</comment>");

            // 2. Crear o verificar estudiantes
            $this->seedStudents($school, $sections, $schoolId);

            // 3. Crear profesores si no existen
            $this->seedTeachers($school, $schoolId);

            // 4. Generar historial de asistencia
            $this->seedAttendance($school, $shifts, $schoolId);

            // 5. Crear excusas de muestra
            $this->seedExcuses($schoolId);
        });

        $this->newLine();
        $this->info('✅  Seeder completado. El sistema está listo para la demo.');
        $this->newLine();
        $this->line('  <fg=cyan>php artisan serve</> y luego accede a <fg=cyan>/app/attendance/dashboard</>');

        return self::SUCCESS;
    }

    // ── Paso 1: Estudiantes ───────────────────────────────────────

    private function seedStudents(School $school, $sections, int $schoolId): void
    {
        $existingCount = Student::where('school_id', $schoolId)->count();

        if ($existingCount > 0) {
            $this->line("  👥  Estudiantes existentes: <comment>{$existingCount}</comment> — se omite la creación.");
            return;
        }

        $targetCount = min(max((int) $this->option('students'), 25), 150);
        $this->line("  👥  Creando <comment>{$targetCount}</comment> estudiantes distribuidos en {$sections->count()} secciones...");

        // Distribución equitativa entre secciones
        $perSection = (int) ceil($targetCount / $sections->count());
        $created    = 0;

        $firstNames = ['Carlos', 'María', 'José', 'Ana', 'Luis', 'Laura', 'Juan', 'Sofía',
                       'Pedro', 'Carmen', 'Miguel', 'Valentina', 'Andrés', 'Isabella',
                       'Diego', 'Gabriela', 'Alejandro', 'Camila', 'Ricardo', 'Daniela',
                       'Fernando', 'Natalia', 'Eduardo', 'Paola', 'Jesús', 'Claudia',
                       'Ramón', 'Lucía', 'Francisco', 'Marta', 'Rafael', 'Sandra'];

        $lastNames  = ['García', 'Rodríguez', 'Martínez', 'López', 'González', 'Pérez',
                       'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Rivera', 'Gómez',
                       'Díaz', 'Cruz', 'Reyes', 'Morales', 'Ortiz', 'Jiménez',
                       'Medina', 'Santos', 'Herrera', 'Vargas', 'Castillo', 'Ramos',
                       'Núñez', 'Guerrero', 'Mendoza', 'Suárez', 'Molina', 'Silva'];

        foreach ($sections as $section) {
            $count = min($perSection, $targetCount - $created);
            if ($count <= 0) break;

            for ($i = 0; $i < $count; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName  = $lastNames[array_rand($lastNames)] . ' ' . $lastNames[array_rand($lastNames)];
                $rnc       = '4' . str_pad((string) rand(1000000, 9999999), 9, '0', STR_PAD_LEFT) . rand(1, 9);
                $dob       = Carbon::now()->subYears(rand(14, 18))->subDays(rand(0, 365));

                $student = Student::create([
                    'school_id'         => $schoolId,
                    'school_section_id' => $section->id,
                    'first_name'        => $firstName,
                    'last_name'         => $lastName,
                    'gender'            => rand(0, 1) ? 'M' : 'F',
                    'date_of_birth'     => $dob->toDateString(),
                    'rnc'               => $rnc,
                    'enrollment_date'   => Carbon::now()->startOfYear()->toDateString(),
                    'is_active'         => true,
                    'tutor_name'        => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
                    'tutor_phone'       => '+1829' . rand(1000000, 9999999),
                    // qr_code y face_encoding los genera el Observer existente
                ]);

                $created++;
            }
        }

        $this->line("  ✔️   <info>{$created} estudiantes creados correctamente.</info>");
    }

    // ── Paso 2: Profesores ────────────────────────────────────────

    private function seedTeachers(School $school, int $schoolId): void
    {
        $existingCount = Teacher::where('school_id', $schoolId)->count();

        if ($existingCount >= 3) {
            $this->line("  👩‍🏫  Profesores existentes: <comment>{$existingCount}</comment> — se omite la creación.");
            return;
        }

        $this->line('  👩‍🏫  Creando profesores de muestra...');

        $teachersData = [
            ['first_name' => 'María',  'last_name' => 'González', 'specialization' => 'Matemáticas'],
            ['first_name' => 'Carlos', 'last_name' => 'Reyes',    'specialization' => 'Lengua Española'],
            ['first_name' => 'Ana',    'last_name' => 'Castillo', 'specialization' => 'Ciencias Naturales'],
        ];

        foreach ($teachersData as $data) {
            Teacher::firstOrCreate(
                [
                    'school_id'  => $schoolId,
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                ],
                [
                    'gender'          => 'F',
                    'specialization'  => $data['specialization'],
                    'employment_type' => 'full_time',
                    'is_active'       => true,
                    'hire_date'       => Carbon::now()->subYears(2)->toDateString(),
                ]
            );
        }

        $this->line('  ✔️   <info>3 profesores creados correctamente.</info>');
    }

    // ── Paso 3: Historial de Asistencia ──────────────────────────

    private function seedAttendance(School $school, $shifts, int $schoolId): void
    {
        $days      = min(max((int) $this->option('days'), 1), 90);
        $students  = Student::where('school_id', $schoolId)->where('is_active', true)->get(['id']);
        $fresh     = $this->option('fresh');

        if ($students->isEmpty()) {
            $this->warn('  ⚠️  No hay estudiantes activos. Omitiendo generación de asistencia.');
            return;
        }

        if ($fresh) {
            $this->warn('  🗑️   Eliminando registros de asistencia anteriores (--fresh)...');
            PlantelAttendanceRecord::where('school_id', $schoolId)->delete();
            DailyAttendanceSession::where('school_id', $schoolId)->delete();
        }

        $this->line("  📅  Generando {$days} días de historial para <comment>{$students->count()}</comment> estudiantes...");

        $primaryShift = $shifts->first();

        // Obtener el usuario registrador (primer Director/Staff de la escuela)
        $registeredBy = User::where('school_id', $schoolId)->first()?->id ?? 1;

        $bar = $this->output->createProgressBar($days);
        $bar->start();

        for ($d = $days - 1; $d >= 0; $d--) {
            $date = Carbon::today()->subDays($d);

            // Saltar fines de semana — las escuelas dominicanas operan lunes-viernes
            if ($date->isWeekend()) {
                $bar->advance();
                continue;
            }

            // Crear sesión diaria del día
            $isToday = $date->isToday();
            $session = DailyAttendanceSession::firstOrCreate(
                [
                    'school_id'      => $schoolId,
                    'date'           => $date->toDateString(),
                    'school_shift_id' => $primaryShift?->id,
                ],
                [
                    'opened_at'       => $date->copy()->setTime(7, 30),
                    'closed_at'       => $isToday ? null : $date->copy()->setTime(13, 0),
                    'opened_by'       => $registeredBy,
                    'closed_by'       => $isToday ? null : $registeredBy,
                    'total_expected'  => $students->count(),
                ]
            );

            // Evitar duplicar registros si ya existen para este día
            $alreadyExists = PlantelAttendanceRecord::where('school_id', $schoolId)
                ->whereDate('date', $date)
                ->exists();

            if ($alreadyExists) {
                $bar->advance();
                continue;
            }

            $presentCount = 0;
            $lateCount    = 0;
            $absentCount  = 0;
            $excusedCount = 0;
            $records      = [];

            foreach ($students as $student) {
                $roll   = rand(1, 100);
                $status = match (true) {
                    $roll <= self::DIST_PRESENT                                    => PlantelAttendanceRecord::STATUS_PRESENT,
                    $roll <= self::DIST_PRESENT + self::DIST_LATE                  => PlantelAttendanceRecord::STATUS_LATE,
                    $roll <= self::DIST_PRESENT + self::DIST_LATE + self::DIST_ABSENT => PlantelAttendanceRecord::STATUS_ABSENT,
                    default                                                        => PlantelAttendanceRecord::STATUS_EXCUSED,
                };

                // Método de registro: varía por día para que no sea monótono
                $method = ($d % 3 === 0)
                    ? PlantelAttendanceRecord::METHOD_FACIAL
                    : PlantelAttendanceRecord::METHOD_QR;

                // Hora de entrada: entre 7:30 y 8:30 si es tardanza, 7:00-7:30 si es presente
                $hour   = ($status === PlantelAttendanceRecord::STATUS_LATE) ? rand(8, 9) : 7;
                $minute = rand(0, 59);
                $time   = $date->copy()->setTime($hour, $minute);

                $records[] = [
                    'school_id'                   => $schoolId,
                    'student_id'                  => $student->id,
                    'daily_attendance_session_id' => $session->id,
                    'school_shift_id'             => $primaryShift?->id,
                    'date'                        => $date->toDateString(),
                    'time'                        => $time,
                    'status'                      => $status,
                    'method'                      => $method,
                    'registered_by'               => $registeredBy,
                    'created_at'                  => $time,
                    'updated_at'                  => $time,
                ];

                match ($status) {
                    PlantelAttendanceRecord::STATUS_PRESENT => $presentCount++,
                    PlantelAttendanceRecord::STATUS_LATE    => $lateCount++,
                    PlantelAttendanceRecord::STATUS_ABSENT  => $absentCount++,
                    default                                 => $excusedCount++,
                };
            }

            // Insert masivo para rendimiento — evita N+1 de queries
            foreach (array_chunk($records, 200) as $chunk) {
                PlantelAttendanceRecord::insert($chunk);
            }

            // Actualizar estadísticas de la sesión
            $session->update([
                'total_registered' => $students->count(),
                'total_present'    => $presentCount,
                'total_late'       => $lateCount,
                'total_absent'     => $absentCount,
                'total_excused'    => $excusedCount,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("  ✔️   <info>Historial generado correctamente.</info>");
    }

    // ── Paso 4: Excusas de muestra ────────────────────────────────

    private function seedExcuses(int $schoolId): void
    {
        $existingExcuses = AttendanceExcuse::where('school_id', $schoolId)->count();

        if ($existingExcuses >= 3) {
            $this->line("  📋  Excusas existentes: <comment>{$existingExcuses}</comment> — se omite la creación.");
            return;
        }

        $students = Student::where('school_id', $schoolId)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        if ($students->isEmpty()) return;

        $submitter = User::where('school_id', $schoolId)->first()?->id ?? 1;

        $this->line('  📋  Creando excusas de muestra...');

        $excuseTypes = [
            AttendanceExcuse::TYPE_MEDICAL,
            AttendanceExcuse::TYPE_FULL_ABSENCE,
            AttendanceExcuse::TYPE_LICENSE,
        ];

        foreach ($students as $i => $student) {
            $dateStart = Carbon::today()->subDays(rand(3, 10));
            $dateEnd   = $dateStart->copy()->addDays(rand(1, 2));

            AttendanceExcuse::create([
                'school_id'    => $schoolId,
                'student_id'   => $student->id,
                'date_start'   => $dateStart->toDateString(),
                'date_end'     => $dateEnd->toDateString(),
                'type'         => $excuseTypes[$i % count($excuseTypes)],
                'reason'       => 'Generado para demo del sistema ORVIAN.',
                'status'       => AttendanceExcuse::STATUS_APPROVED,
                'submitted_by' => $submitter,
                'submitted_at' => $dateStart->copy()->subDay(),
                'reviewed_by'  => $submitter,
                'reviewed_at'  => $dateStart,
            ]);
        }

        $this->line('  ✔️   <info>3 excusas aprobadas creadas correctamente.</info>');
    }
}
```

---

### 8.2 — Registrar el comando en `app/Console/Kernel.php` (si usas Kernel)

En Laravel 12 con `routes/console.php` el registro es automático por namespace. Si tienes un `Kernel.php` explícito, agregar:

```php
// app/Console/Kernel.php
protected $commands = [
    \App\Console\Commands\SeedDemoSchoolData::class,
];
```

---

### 8.3 — Instrucciones de Ejecución

```bash
# Uso básico — crea 75 estudiantes y 30 días de historial para la escuela 1
php artisan orvian:seed-demo --school_id=1

# Personalizar cantidad de estudiantes y días
php artisan orvian:seed-demo --school_id=1 --students=100 --days=30

# Limpiar registros anteriores y regenerar desde cero
php artisan orvian:seed-demo --school_id=1 --fresh

# Para ver el ID de las escuelas disponibles antes de ejecutar
php artisan tinker
>>> App\Models\Tenant\School::select('id', 'name')->get()->toArray()
```

---

### 8.4 — Qué verás después de ejecutar el seeder

| Vista del sistema | Estado antes | Estado después |
| :--- | :--- | :--- |
| Dashboard → Calendario | Días vacíos, sin color | 30 días con íconos: ✅ verde (normal), ⚠️ naranja (abierta), 🔴 rojo (>20% ausentes) |
| Dashboard → Gráfico de línea | Sin datos / línea plana | Curva de asistencia con variaciones realistas |
| Dashboard → Donut chart | Vacío | Proporciones 80/10/5/5 visibles |
| Dashboard → Actividad reciente | Vacío | 15 registros con fotos, hora, estado y método |
| Kiosko QR → Scanner | Sin estudiantes | 75+ estudiantes con QR activo listos para escanear |
| Excusas | Lista vacía | 3 excusas aprobadas visibles |

---

### 8.5 — Precauciones para la Demo en Vivo

```bash
# ⚠️ NUNCA ejecutar con --fresh en una escuela con datos reales.
# El flag --fresh elimina TODOS los registros de asistencia del school_id indicado.

# ✅ Para demo en ambiente de desarrollo (seguro):
php artisan orvian:seed-demo --school_id=1 --students=75 --days=30

# ✅ Para resetear y generar datos limpios en staging:
php artisan orvian:seed-demo --school_id=1 --fresh --students=75 --days=30
```

---

## Archivos a Crear / Modificar

| Archivo | Acción | Fase |
| :--- | :--- | :--- |
| `package.json` + `vite.config.js` | Instalar `@mediapipe/tasks-vision` y `vite-plugin-static-copy` | 1 |
| `resources/js/app.js` | Importar y exponer `FaceDetector`, `FilesetResolver` | 1 |
| `public/models/blaze_face_short_range.tflite` | Descargar y commitear (800KB) | 1 |
| `resources/views/livewire/app/attendance/partials/scanner-visor.blade.php` | Reemplazar bloque `@push('scripts')` con implementación MediaPipe | 1 |
| `public/assets/sounds/success.wav` | Crear/copiar archivo de sonido éxito | 2 |
| `public/assets/sounds/error.wav` | Crear/copiar archivo de sonido error | 2 |
| `app/Livewire/Shared/ProfileModal.php` | Agregar propiedad `$audioFeedback` y persistencia | 2 |
| `resources/views/livewire/shared/profile-modal.blade.php` | Agregar toggle y botón de prueba | 2 |
| `resources/views/layouts/app-module.blade.php` | Agregar componente Alpine `audioFeedback` y meta tag | 2 |
| `resources/views/components/app/navbar.blade.php` | Agregar botón fullscreen | 3 |
| `resources/views/components/navbar/layout.blade.php` | Agregar botón fullscreen | 3 |
| `app/Livewire/App/Attendance/AttendanceDashboard.php` | Refactorizar `loadWeeklyStats()` para usar `$selectedDate` | 4 |
| `resources/views/printables/qr-sheet.blade.php` | Reemplazar completamente con diseño institucional b/n | 5 |
| `app/Models/Tenant/Feature.php` | Actualizar `getIcon()` para retornar slugs de módulo | 6 |
| `resources/views/components/ui/plan-card.blade.php` | Cambiar `x-dynamic-component` por `x-ui.module-icon` | 6 |
| `resources/views/layouts/navigation.blade.php` | Agregar `overflow-y-auto max-h-[85vh]` al menú móvil | 7 |
| `app/Console/Commands/SeedDemoSchoolData.php` | Crear comando completo de seeder de demo | 8 |

---

## Notas de Implementación

- **MediaPipe en desarrollo local (HTTP):** `requestFullscreen()` y `getUserMedia()` requieren HTTPS en producción, pero en `localhost` o `*.test` funcionan sin SSL. Para la demo en la escuela, servir desde HTTPS o usar el flag `--unsafely-treat-insecure-origin-as-secure` en Chrome si es necesario.
- **`faceDetector` no se destruye en `cleanup()`:** Reinicializar MediaPipe toma ~300ms. Reutilizar la instancia entre cambios de modo QR↔Facial es más eficiente. Solo se destruye si el componente Alpine se desmonta completamente.
- **Audio bloqueado por el navegador:** La Autoplay Policy de Chrome bloquea `audio.play()` si no hubo interacción previa del usuario. En el kiosko de asistencia siempre hay interacción (abrir el modo, escanear) antes de que suene el audio, así que en la práctica nunca se bloquea.
- **Carnet QR — `$academicYear`:** Agregar al controlador que llama a la vista: `$academicYear = $school->activeYear()?->label ?? date('Y') . '-' . (date('Y') + 1)`. Usar `activeYear()` del modelo `School` que ya existe.
- **`vite-plugin-static-copy` en producción:** El comando `npm run build` copia los WASM a `public/mediapipe/`. Verificar que el servidor web sirva los archivos `.wasm` con el MIME type correcto (`application/wasm`). En nginx: `types { application/wasm wasm; }`.
- **Sin dependencias nuevas de composer:** Esta versión solo agrega paquetes npm. El `composer.json` no cambia.
- **Seeder — `setPermissionsTeamId()`:** El helper de Spatie debe llamarse con el `school_id` antes de cualquier operación que involucre roles o permisos. El seeder ya lo hace al inicio del `DB::transaction()`.
- **Seeder — Insert masivo en chunks de 200:** `PlantelAttendanceRecord::insert()` no dispara Observers ni Events de Eloquent. Los QR codes de estudiantes ya existen (los generó el `StudentObserver` al crear). Si necesitas que los registros de asistencia disparen eventos, usa `create()` en su lugar, sabiendo que será 5-10x más lento.
- **Seeder — Fines de semana omitidos:** El comando detecta sábados y domingos con `$date->isWeekend()` y los salta. El gráfico de la Fase 4 solo muestra días con datos, así que los huecos son correctos.