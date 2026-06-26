# ORVIAN v0.9.0 — Pivote Arquitectónico: Fat Client Scanner

**RAMA PADRE:** `feature/v0.9.0-platform-maturity`

**Objetivo:** Esta versión introduce el cambio arquitectónico más significativo en la historia de la plataforma de asistencia biométrica. Se abandona el modelo _Slim Client_ (procesamiento de visión artificial en el navegador web) y se adopta un modelo **Fat Client** mediante una aplicación de escritorio nativa en Python (`orvian-desktop-scanner`) que actúa como Tótem/Kiosko de portería. Paralelamente, la versión consolida múltiples mejoras de calidad: ventanas horarias configurables por tanda (REQ-03), Selector Universal de Cursos (REQ-04), rediseño del pase de lista con gestos táctiles (REQ-05), y refinamientos de UI Kit (REQ-09, REQ-10, REQ-11).

> **Por qué se abandona el Slim Client:** Las iteraciones v0.8.0 y anteriores demostraron que el procesamiento de visión artificial en el navegador es inviable en el entorno escolar dominicano. Los tres problemas son estructurales y no tienen solución en el stack web: (1) los archivos WASM de MediaPipe (~18MB) son bloqueados o corrompidos por firewalls Fortinet en redes escolares; (2) el rendimiento de inferencia en CPU via WASM es inconsistente entre dispositivos de bajo costo; (3) la gestión de memoria de `getUserMedia` + WASM en sesiones largas genera fugas detectadas en portería. La solución correcta es ejecutar el stack de visión nativo (OpenCV + MediaPipe Python) donde fue diseñado para correr: directamente en el hardware del dispositivo kiosko.

> **Nota sobre REQ-06 (App Móvil) y REQ-08 (Dominio de Tutores):** Ambos son de planificación/análisis en esta versión. No generan código. Sus secciones describen las decisiones de arquitectura que guiarán versiones futuras.

---

## Estado de la Base — v0.8.0 como Fundación

| Componente | Origen | Estado |
| :--- | :--- | :--- |
| `AttendanceScanner.php` — Livewire del kiosko web | v0.4.0 | 🗑️ **ELIMINAR** — reemplazado por `orvian-desktop-scanner` |
| `scanner-visor.blade.php` con face-api.js / MediaPipe Web | v0.8.0 | 🗑️ **ELIMINAR** — la cámara pasa al cliente de escritorio |
| `scanner-stats.blade.php` — stats del kiosko web | v0.8.0 | 🗑️ **ELIMINAR** — vista obsoleta con el nuevo modelo |
| `attendance-scanner.blade.php` — layout del kiosko web | v0.4.0 | 🗑️ **ELIMINAR** — ya no existe kiosko en el navegador |
| Feedback de audio web (`success.wav`, `error.wav`) | v0.8.0 | 🗑️ **ELIMINAR** — el audio es responsabilidad exclusiva del desktop |
| `FaceEncodingManager.php` — servicio de encodings | v0.6.0 | ✅ Se conserva intacto |
| `FacialApiClient.php` — cliente del microservicio Python | v0.6.0 | ✅ Se conserva intacto |
| `orvian-facial-recognition` — microservicio Python | v0.6.0 | ✅ Se conserva intacto — Laravel sigue siendo el orquestador |
| `ClassroomAttendanceLive.php` — pase de lista en aula | v0.4.0 | ⚠️ Rediseñar vista con gestos táctiles (REQ-05) |
| `SchoolShift` con `start_time` y `end_time` | v0.4.0 | ⚠️ Extender con ventanas de registro configurables (REQ-03) |
| `PlantelAttendanceService::determineStatus()` | v0.4.0 | ⚠️ Integrar con nueva configuración de ventanas horarias (REQ-03) |
| Selects de secciones en múltiples vistas | v0.3.0+ | ⚠️ Reemplazar con Selector Universal de Cursos (REQ-04) |
| `x-ui.button` con `wire:loading.class` global | v0.3.0 | ⚠️ Corregir — dispara en cualquier acción Livewire (REQ-11) |
| `x-ui.toasts` con `toastManager` Alpine | v0.3.0 | ⚠️ Modernizar — stack, swipe-to-dismiss y refinamiento (REQ-10) |
| Páginas de error de Laravel (genéricas) | v0.1.0 | ⚠️ Crear vistas personalizadas 403, 404 y 500 (REQ-09) |
| Navbar de módulos en mobile | v0.8.0 | ✅ Funcional, ajustes visuales menores (REQ-07) |

---

## Tabla de Requerimientos

| ID | Fase | Área | Descripción | Prioridad | Estado |
| :-- | :-- | :-- | :-- | :-- | :-- |
| REQ-01 | 1 | Asistencia Biométrica | API Gateway para el Kiosko: rutas `/api/v1/kiosk/` protegidas por Sanctum | Alta | Completado |
| REQ-02 | 2 | Asistencia Biométrica | Arquitectura de `orvian-kiosk-electron`: app de escritorio con Electron + MediaPipe Tasks-Vision for Web (WASM local), sin lógica de QR | Alta | Pendiente |
| REQ-03 | 3 | Configuración | Ventanas horarias configurables por tanda (entrada, tardanza, cierre) | Alta | Pendiente |
| REQ-04 | 4 | UI / Componentes | Selector Universal de Cursos — componente Livewire reutilizable | Alta | Pendiente |
| REQ-05 | 5 | Asistencia Aula | Rediseño completo del pase de lista con gestos de deslizamiento | Alta | Pendiente |
| REQ-06 | 6 | Mobile | Planificación de app móvil Flutter para tutores (sin código) | Media | Planificación |
| REQ-07 | 7 | UI | Ajustes visuales al navbar de módulos en mobile | Media | Pendiente |
| REQ-08 | 8 | Arquitectura | Evaluación del dominio de tutores y padres (sin código) | Media | Análisis |
| REQ-09 | 9 | UI | Páginas de error personalizadas (403, 404, 500) | Baja | Pendiente |
| REQ-10 | 10 | UI Kit | Toasts acumulativos, swipe-to-dismiss y refinamiento visual | Media | Pendiente |
| REQ-11 | 11 | UI Kit | Corrección de `wire:loading` global en `x-ui.button` | Alta | Pendiente |

---

**Rama:** `feature/v0.9.0-purge`

## 🗑️ THE PURGE — Archivos Eliminados del Proyecto Laravel

Los siguientes archivos se eliminan del repositorio `orvian` como parte del pivote arquitectónico. Su funcionalidad es reemplazada en su totalidad por el nuevo modelo Fat Client descrito en REQ-01 y REQ-02.

### Componentes Livewire del Kiosko Web

```
app/Livewire/App/Attendance/AttendanceScanner.php
```

El componente Livewire que gestionaba el estado del kiosko web (sesión activa, modo QR/Facial, captura de foto, dispatch de eventos) deja de existir. El cliente de escritorio se comunicará directamente con el API Gateway de Laravel (REQ-01) mediante tokens Sanctum, sin intermediación de Livewire.

### Vistas del Kiosko Web

```
resources/views/livewire/app/attendance/attendance-scanner.blade.php
resources/views/livewire/app/attendance/partials/scanner-stats.blade.php
resources/views/livewire/app/attendance/partials/scanner-visor.blade.php
```

Estas vistas contenían toda la interfaz del kiosko web: el visor de cámara con detección facial (face-api.js / MediaPipe WASM), el panel de estadísticas de sesión y el layout principal del scanner. La interfaz del kiosko ahora es una ventana nativa de Python (CustomTkinter), no una página web.

### Feedback de Audio del Frontend Web

```
public/assets/sounds/success.wav
public/assets/sounds/error.wav
```

**Lógica asociada a eliminar:**

- En `app/Livewire/Shared/ProfileModal.php`: la propiedad `$audioFeedback` y su persistencia en `preferences['audio_feedback']` vía `savePreferences()`.
- En `resources/views/livewire/shared/profile-modal.blade.php`: la sección de preferencia de audio feedback en la pestaña _Preferencias_.
- En `resources/views/layouts/app-module.blade.php`: el componente Alpine global `audioFeedback` que escuchaba los eventos `attendance-recorded-success` y `attendance-facial-error`, y la meta tag `<meta name="audio-feedback">`.

El audio de éxito y error es ahora responsabilidad exclusiva de la aplicación de escritorio `orvian-desktop-scanner`, que reproduce los archivos de audio localmente mediante la librería `pygame.mixer` o similar. Los archivos de sonido se distribuyen con el ejecutable empaquetado.

### Vendor de face-api.js

```
public/vendor/face-api/
```

La totalidad del directorio `face-api/` (librería + modelos, ~840KB) se elimina del repositorio. No hay reemplazo en el frontend web.

### Rutas y configuraciones

Eliminar ruta `/attendance/scanner` y de `config/modules.php` eliminar la ruta del navbar de scanner.

```
routes/app/attendance.php
config/modules.php
resources/views/livewire/app/attendance/session-manager.blade.php
resources/views/livewire/app/attendance/attendance-dashboard.blade.php
resources/views/livewire/app/attendance/manual-attendance.blade.php
```


---

## Fase 1 — API Gateway para el Kiosko (REQ-01)

**Rama:** `feature/v0.9.0-kiosk-api`

### Contexto y Decisión de Diseño

El cliente de escritorio `orvian-desktop-scanner` necesita comunicarse con el backend Laravel para: verificar si hay una sesión de asistencia activa, registrar un QR decodificado y enviar una imagen facial para reconocimiento. Estas operaciones se exponen como una API REST dedicada bajo el prefijo `/api/v1/kiosk/`, separada de las rutas web de Livewire y de la futura API de tutores (`/api/v1/parent/`).

La autenticación entre el kiosko físico y Laravel se gestiona mediante **Laravel Sanctum API Tokens** (no SPA cookies). Cada dispositivo kiosko tiene un token único vinculado a la escuela, generado desde el panel de configuración del administrador.

### 1.1 — Token de Kiosko

#### Modelo conceptual

```
School (school_id)
  └── KioskToken (personal_access_token)
        ├── name: "Portería Principal"
        ├── abilities: ["kiosk"]
        └── tokenable_type / tokenable_id → School
```

El token se emite sobre el modelo `School` (tokenable de tipo School), no sobre un `User`. Esto permite que el dispositivo esté vinculado a la institución sin representar a ningún usuario en particular.

#### Instalación de Sanctum

```bash
  sail composer require laravel/sanctum
```
#### Migración

```php
// El modelo School debe implementar HasApiTokens de Sanctum
// app/Models/Tenant/School.php

use Laravel\Sanctum\HasApiTokens;

class School extends Model
{
    use HasApiTokens;
    // ...
}
```

#### Generación del Token desde la Configuración de Escuela

```php
// app/Livewire/App/Settings/SchoolSettings.php

public function generateKioskToken(): void
{
    $this->authorize('settings.manage');

    $school = Auth::user()->school;

    // Revocar token anterior si existe
    $school->tokens()->where('name', 'kiosk')->delete();

    $token = $school->createToken('kiosk', ['kiosk'])->plainTextToken;

    // El token se muestra una sola vez en la UI para copiarlo al dispositivo
    $this->dispatch('kiosk-token-generated', token: $token);
}
```

La vista de configuración mostrará el token en un campo de solo lectura con botón "Copiar" y una advertencia de que no se puede recuperar después de cerrar el modal.

### 1.2 — Rutas del API Gateway

```php
// routes/api.php

use App\Http\Controllers\Api\Kiosk\KioskStatusController;
use App\Http\Controllers\Api\Kiosk\KioskQrRecordController;
use App\Http\Controllers\Api\Kiosk\KioskFacialRecordController;

Route::prefix('v1/kiosk')
    ->middleware(['auth:sanctum', 'ability:kiosk'])
    ->group(function () {

        // GET /api/v1/kiosk/status
        Route::get('status', KioskStatusController::class);

        // POST /api/v1/kiosk/record/qr
        Route::post('record/qr', KioskQrRecordController::class);

        // POST /api/v1/kiosk/record/facial
        Route::post('record/facial', KioskFacialRecordController::class);
    });
```

El middleware `ability:kiosk` garantiza que solo tokens con el claim `kiosk` puedan acceder a estas rutas, bloqueando tokens de otros contextos (ej. tutores con ability `parent`).

### 1.3 — Endpoint: `GET /api/v1/kiosk/status`

**Controller:** `app/Http/Controllers/Api/Kiosk/KioskStatusController.php`

**Propósito:** El cliente de escritorio consulta este endpoint al iniciar y en polling periódico para saber si hay una `DailyAttendanceSession` abierta para hoy en la escuela vinculada al token.

```php
namespace App\Http\Controllers\Api\Kiosk;

use App\Models\Tenant\Attendance\DailyAttendanceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KioskStatusController
{
    public function __invoke(Request $request): JsonResponse
    {
        $school = $request->user(); // El tokenable es el modelo School

        $session = DailyAttendanceSession::query()
            ->where('school_id', $school->id)
            ->whereDate('date', today())
            ->where('status', 'open')
            ->first();

        return response()->json([
            'session_active' => (bool) $session,
            'session_id'     => $session?->id,
            'session_date'   => $session?->date?->toDateString(),
            'school_name'    => $school->name,
            'server_time'    => now()->toIso8601String(),
        ]);
    }
}
```

**Respuesta cuando hay sesión activa:**

```json
{
  "session_active": true,
  "session_id": 42,
  "session_date": "2026-03-15",
  "school_name": "Colegio San Judas Tadeo",
  "server_time": "2026-03-15T07:45:00-04:00"
}
```

**Respuesta sin sesión activa:**

```json
{
  "session_active": false,
  "session_id": null,
  "session_date": null,
  "school_name": "Colegio San Judas Tadeo",
  "server_time": "2026-03-15T06:00:00-04:00"
}
```

El cliente de escritorio usa `session_active: false` para mostrar la pantalla de "Sin sesión activa" en la UI del kiosko e inhabilitar el procesamiento de cámara.

### 1.4 — Endpoint: `POST /api/v1/kiosk/record/qr`

**Controller:** `app/Http/Controllers/Api/Kiosk/KioskQrRecordController.php`

**Propósito:** Recibe el payload del código QR ya decodificado por `pyzbar` en el cliente de escritorio. Laravel valida el código, identifica al estudiante y registra la asistencia usando la lógica existente de `PlantelAttendanceService`.

**Payload esperado:**

```json
{
  "session_id": 42,
  "qr_code": "ORV-2024-00153"
}
```

```php
namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Requests\Kiosk\RecordQrRequest;
use App\Services\Attendance\PlantelAttendanceService;
use Illuminate\Http\JsonResponse;

class KioskQrRecordController
{
    public function __invoke(
        RecordQrRequest $request,
        PlantelAttendanceService $service
    ): JsonResponse {
        $school = $request->user();

        $result = $service->recordByQr(
            schoolId:  $school->id,
            sessionId: $request->validated('session_id'),
            qrCode:    $request->validated('qr_code'),
        );

        if ($result->failed()) {
            return response()->json([
                'success' => false,
                'error'   => $result->errorCode(),   // 'NOT_FOUND' | 'ALREADY_RECORDED' | 'SESSION_CLOSED'
                'message' => $result->errorMessage(),
            ], 422);
        }

        return response()->json([
            'success'    => true,
            'student'    => [
                'id'         => $result->student->id,
                'full_name'  => $result->student->full_name,
                'photo_url'  => $result->student->photo_url,
            ],
            'status'     => $result->attendanceStatus,  // 'present' | 'late'
            'recorded_at'=> $result->recordedAt->toIso8601String(),
        ]);
    }
}
```

**Form Request:** `app/Http/Requests/Kiosk/RecordQrRequest.php`

```php
public function rules(): array
{
    return [
        'session_id' => ['required', 'integer', 'exists:daily_attendance_sessions,id'],
        'qr_code'    => ['required', 'string', 'max:100'],
    ];
}
```

**Respuesta exitosa:**

```json
{
  "success": true,
  "student": {
    "id": 153,
    "full_name": "Ana María Rodríguez Pérez",
    "photo_url": "https://orvian.app/storage/students/photos/153.jpg"
  },
  "status": "present",
  "recorded_at": "2026-03-15T07:48:32-04:00"
}
```

### 1.5 — Endpoint: `POST /api/v1/kiosk/record/facial`

**Controller:** `app/Http/Controllers/Api/Kiosk/KioskFacialRecordController.php`

**Propósito:** Recibe la imagen capturada por el cliente de escritorio como `multipart/form-data`. Laravel la reenvía al microservicio `orvian-facial-recognition` existente mediante `FacialApiClient`, exactamente como lo hacía el flujo Livewire anterior.

**Payload esperado:** `multipart/form-data`

```
session_id: 42
photo: <binary JPEG>
```

```php
namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Requests\Kiosk\RecordFacialRequest;
use App\Services\Attendance\PlantelAttendanceService;
use Illuminate\Http\JsonResponse;

class KioskFacialRecordController
{
    public function __invoke(
        RecordFacialRequest $request,
        PlantelAttendanceService $service
    ): JsonResponse {
        $school = $request->user();

        $result = $service->recordByFacial(
            schoolId:  $school->id,
            sessionId: $request->validated('session_id'),
            photo:     $request->file('photo'),
        );

        if ($result->failed()) {
            return response()->json([
                'success' => false,
                'error'   => $result->errorCode(),   // 'NO_MATCH' | 'MULTIPLE_FACES' | 'LOW_CONFIDENCE' | 'SESSION_CLOSED'
                'message' => $result->errorMessage(),
            ], 422);
        }

        return response()->json([
            'success'    => true,
            'student'    => [
                'id'         => $result->student->id,
                'full_name'  => $result->student->full_name,
                'photo_url'  => $result->student->photo_url,
            ],
            'status'     => $result->attendanceStatus,
            'confidence' => $result->confidence,
            'recorded_at'=> $result->recordedAt->toIso8601String(),
        ]);
    }
}
```

**Form Request:** `app/Http/Requests/Kiosk/RecordFacialRequest.php`

```php
public function rules(): array
{
    return [
        'session_id' => ['required', 'integer', 'exists:daily_attendance_sessions,id'],
        'photo'      => ['required', 'file', 'mimes:jpeg,jpg,png', 'max:5120'],  // 5MB máx.
    ];
}
```

**Respuesta exitosa:**

```json
{
  "success": true,
  "student": {
    "id": 87,
    "full_name": "Luis Antonio Martínez García",
    "photo_url": "https://orvian.app/storage/students/photos/87.jpg"
  },
  "status": "late",
  "confidence": 0.94,
  "recorded_at": "2026-03-15T08:17:05-04:00"
}
```

### 1.6 — Códigos de Error Estándar del API Kiosko

| `error` | HTTP | Descripción |
| :--- | :--- | :--- |
| `NOT_FOUND` | 422 | El código QR no corresponde a ningún estudiante de la escuela |
| `ALREADY_RECORDED` | 422 | El estudiante ya fue registrado en esta sesión |
| `SESSION_CLOSED` | 422 | La sesión especificada no está abierta |
| `NO_MATCH` | 422 | El microservicio facial no encontró coincidencia |
| `MULTIPLE_FACES` | 422 | La imagen contiene más de un rostro |
| `LOW_CONFIDENCE` | 422 | La coincidencia facial está por debajo del umbral de confianza |
| `INVALID_TOKEN` | 401 | Token inválido, revocado o sin la ability `kiosk` |

---

## Fase 2 (revisada) — Arquitectura del Cliente de Escritorio Electron (REQ-02)

**Repositorio:** `orvian-kiosk-electron` (independiente de `orvian`, reemplaza al repositorio Python que no llegó a completarse)

**Rama inicial:** `main`

### 2.1 — Visión General

Electron empaqueta dos procesos dentro de un mismo ejecutable: un **proceso principal** (Node.js, sin interfaz, con acceso a sistema de archivos y hardware) y un **proceso de renderizado** (la ventana visible, que es Chromium real ejecutando HTML/CSS/JS locales, no remotos). La cámara, MediaPipe y la UI corren en el renderer; el token, la configuración persistente y el futuro acceso a lectores de hardware (huella) viven en el proceso principal.

```
┌──────────────────────────────────────────────────────────────┐
│                  orvian-kiosk-electron                        │
│                                                                │
│  ┌────────────────────────┐      ┌─────────────────────────┐ │
│  │   Proceso Principal     │      │   Proceso de Renderizado │ │
│  │   (Node.js)              │      │   (Chromium local)       │ │
│  │                          │      │                          │ │
│  │  • config-store.js       │◀────▶│  • index.html             │ │
│  │    (token, server_url)   │ IPC  │  • camera.js (getUserMedia│ │
│  │  • preload.js (bridge)   │      │    + MediaPipe Tasks-     │ │
│  │  • (futuro) huella.js    │      │    Vision, WASM local)    │ │
│  │    via node-hid/serialport│      │  • api-client.js (fetch  │ │
│  │                          │      │    + Bearer token)        │ │
│  └────────────────────────┘      └─────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
                             │
                    HTTPS + Bearer Token
                             │
                    ┌────────▼───────┐
                    │  Laravel API   │
                    │  /api/v1/kiosk/│   (sin cambios — Fase 1)
                    └────────────────┘
```

### 2.2 — Stack Tecnológico

| Componente | Librería | Justificación |
| :--- | :--- | :--- |
| Runtime de escritorio | `electron` | Empaqueta Chromium + Node.js en un único ejecutable; assets locales, sin dependencia de CDN |
| Captura de cámara | `getUserMedia` (Web API nativa) | Misma API que ya usabas en el navegador; no requiere librería adicional |
| Detección facial | `@mediapipe/tasks-vision` | La misma librería que falló por CDN en el navegador — aquí los `.wasm` y `.tflite` se sirven desde disco local, dentro del propio paquete |
| Estilos | `tailwindcss` (build standalone) | Reutiliza el mismo lenguaje de utilidades que ya usas en Laravel, en un pipeline de build independiente |
| HTTP Client | `fetch` nativo de Chromium | No requiere librería adicional para llamar a `/api/v1/kiosk/` |
| Persistencia de configuración | `electron-store` | Equivalente directo al `config.json` planeado para Python; guarda token, URL del servidor y preferencias en disco |
| Empaquetado | `electron-builder` | Genera instalador `.exe` (NSIS) standalone para Windows; equivalente a `PyInstaller` |
| Auto-update | `electron-updater` | Librería estándar del ecosistema Electron; sustituye al `launcher.py` personalizado planeado para Python |
| (Futuro) Lector de huella | `node-hid` o `serialport` (proceso principal) | Acceso a dispositivos USB/Serial desde Node.js, expuesto al renderer vía IPC |

### 2.3 — Estructura del Repositorio

```
orvian-kiosk-electron/
├── package.json
├── electron-builder.yml          # Configuración de empaquetado (.exe)
│
├── main/
│   ├── main.js                   # Punto de entrada del proceso principal
│   ├── config-store.js           # Wrapper sobre electron-store (token, server_url)
│   ├── preload.js                # Bridge seguro entre main y renderer (contextBridge)
│   └── hardware/
│       └── fingerprint.js        # Placeholder — futuro lector de huella vía node-hid
│
├── renderer/
│   ├── index.html                # Ventana única del kiosko
│   ├── styles.css                # Salida del build de Tailwind
│   ├── camera.js                 # Captura de video + bucle de detección MediaPipe
│   ├── api-client.js             # Wrapper de fetch() con Bearer token
│   ├── ui-states.js              # Manejo de los 4 estados visuales del kiosko
│   └── setup-screen.js           # Pantalla de configuración inicial (pegar token)
│
├── vendor/
│   └── mediapipe/
│       ├── wasm/                 # Runtime WASM de MediaPipe, copiado localmente
│       └── models/
│           └── blaze_face_short_range.tflite
│
├── assets/
│   ├── sounds/
│   │   ├── success.wav
│   │   └── error.wav
│   └── icons/
│       └── orvian.ico
│
└── tailwind.config.js
```

### 2.4 — Detección Facial en el Renderer (MediaPipe Tasks-Vision)

```javascript
// renderer/camera.js

import { FaceDetector, FilesetResolver } from "@mediapipe/tasks-vision";

const DWELL_REQUIRED_MS = 1200;
const MIN_DETECTION_CONFIDENCE = 0.6;

let faceDetector = null;
let dwellStart = null;

export async function initFaceDetector() {
    // Resolver apunta a la carpeta local empaquetada, NUNCA a un CDN
    const vision = await FilesetResolver.forVisionTasks("./vendor/mediapipe/wasm");

    faceDetector = await FaceDetector.createFromOptions(vision, {
        baseOptions: {
            modelAssetPath: "./vendor/mediapipe/models/blaze_face_short_range.tflite",
        },
        runningMode: "VIDEO",
        minDetectionConfidence: MIN_DETECTION_CONFIDENCE,
    });
}

export function detectLoop(videoEl, canvasEl, onCaptureReady) {
    const ctx = canvasEl.getContext("2d");

    function loop() {
        const result = faceDetector.detectForVideo(videoEl, performance.now());
        ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);

        if (result.detections.length === 1) {
            const box = result.detections[0].boundingBox;
            drawFaceBox(ctx, box);

            const now = Date.now();
            if (!dwellStart) dwellStart = now;
            const progress = Math.min((now - dwellStart) / DWELL_REQUIRED_MS, 1);

            if (progress >= 1) {
                dwellStart = null;
                onCaptureReady(videoEl); // dispara la captura del frame
            }
        } else {
            dwellStart = null;
        }

        requestAnimationFrame(loop);
    }

    requestAnimationFrame(loop);
}

function drawFaceBox(ctx, box) {
    ctx.strokeStyle = "#10b981";
    ctx.lineWidth = 3;
    ctx.strokeRect(box.originX, box.originY, box.width, box.height);
}
```

Nota: la lógica de *dwell time* (mantener el rostro estable antes de capturar) es prácticamente un calco del bucle que ya tenías en `scanner-visor.blade.php` con `face-api.js`. No es código nuevo conceptualmente, solo una librería distinta corriendo en un contexto sin restricciones de red.

### 2.5 — Cliente HTTP (`api-client.js`)

```javascript
// renderer/api-client.js

export class ApiClient {
    constructor(serverUrl, token) {
        this.base = `${serverUrl.replace(/\/$/, '')}/api/v1/kiosk`;
        this.headers = { 'Authorization': `Bearer ${token}` };
    }

    async getStatus() {
        const resp = await fetch(`${this.base}/status`, { headers: this.headers });
        return resp.json();
    }

    async recordFacial(sessionId, blob) {
        const form = new FormData();
        form.append('session_id', sessionId);
        form.append('photo', blob, 'capture.jpg');

        const resp = await fetch(`${this.base}/record/facial`, {
            method: 'POST',
            headers: this.headers, // FormData define su propio Content-Type automáticamente
            body: form,
        });
        return resp.json();
    }
}
```

### 2.6 — Configuración Inicial sin Login (Token de Kiosko)

No existe pantalla de usuario/contraseña. En el primer arranque, `setup-screen.js` muestra un formulario simple con dos campos: URL del servidor y Token de Kiosko (generado desde `SchoolSettings` en Laravel, exactamente como ya documentaste en la sección 1.1 de la Fase 1). Al guardar, el proceso principal persiste estos valores con `electron-store`:

```javascript
// main/config-store.js

const Store = require('electron-store');
const store = new Store({
    defaults: {
        server_url: '',
        kiosk_token: '',
        display_fullscreen: true,
        audio_enabled: true,
        status_poll_interval_seconds: 60,
    }
});

module.exports = store;
```

Estos valores se exponen al renderer mediante el `preload.js` con `contextBridge`, nunca exponiendo Node.js directamente a la ventana (buena práctica de seguridad de Electron):

```javascript
// main/preload.js

const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('orvianConfig', {
    get: (key) => ipcRenderer.invoke('config:get', key),
    set: (key, value) => ipcRenderer.invoke('config:set', key, value),
});
```

### 2.7 — UX/UI del Kiosko

Los **cuatro estados visuales** (Esperando escaneo, Procesando, Resultado, Sin sesión activa) se mantienen idénticos a los planeados originalmente para CustomTkinter — solo cambia que ahora se implementan como vistas HTML con Tailwind en vez de widgets de Tkinter. El polling de `GET /status` (60s sin sesión, 30s con sesión activa como heartbeat) tampoco cambia.

### 2.8 — Preparación para Lector de Huella Futuro

La razón original para usar el Patrón Strategy en Python era permitir agregar módulos de escaneo sin tocar el núcleo. En Electron, el equivalente es mantener el acceso a hardware en el **proceso principal** (donde Node.js sí puede hablar con dispositivos USB/Serial vía `node-hid` o `serialport`) y exponerlo al renderer únicamente a través de IPC — el renderer nunca toca hardware directamente, solo recibe eventos:

```javascript
// main/hardware/fingerprint.js (placeholder para cuando se integre el lector)

const { ipcMain } = require('electron');
// const HID = require('node-hid');

ipcMain.handle('fingerprint:scan', async () => {
    // Lógica del SDK del lector específico, ejecutada en el proceso principal
    // Devuelve el resultado al renderer vía Promise resuelta del invoke()
});
```

Esto preserva la misma idea de extensibilidad que tenía `ScannerStrategy` en Python, adaptada al modelo de procesos de Electron en vez de a clases abstractas de Python.

### 2.9 — Auto-Update

`electron-updater` es la herramienta estándar del ecosistema para este propósito — sustituye al `launcher.py` personalizado que se había planeado para Python. Se configura apuntando a un feed de actualizaciones (puede ser un endpoint propio en Laravel o GitHub Releases) y gestiona la descarga e instalación de nuevas versiones de forma silenciosa en segundo plano, sin necesitar un script de arranque separado.

### 2.10 — Empaquetado

```yaml
# electron-builder.yml

appId: com.orvian.kiosk
productName: ORVIAN Kiosko
win:
  target: nsis
  icon: assets/icons/orvian.ico
extraResources:
  - from: vendor/mediapipe
    to: vendor/mediapipe
```

El bloque `extraResources` es la pieza clave: garantiza que los archivos `.wasm` y `.tflite` de MediaPipe viajen físicamente dentro del instalador `.exe`, accesibles por ruta local en cualquier máquina donde se instale, sin pedir nada a un CDN en tiempo de ejecución.

---

## Fase 3 — Ventanas Horarias Configurables por Tanda

**Rama:** `feature/v0.9.0-shift-windows`

### Contexto

La lógica actual en `PlantelAttendanceService::determineStatus()` usa `SchoolShift::start_time` + un margen fijo de 15 minutos para determinar si un estudiante llegó tarde. Esto no contempla:
- Ventanas de registro pre-apertura (entrada temprana).
- Cierre automático de registro (nadie puede entrar después de X hora).
- Rangos de tardanza configurables por tanda (no siempre son 15 minutos).

### Migración de Base de Datos

```php
// database/migrations/xxxx_add_attendance_windows_to_school_shifts.php

Schema::table('school_shifts', function (Blueprint $table) {
    // Minutos antes del start_time que se permite registrar entrada
    $table->unsignedSmallInteger('early_entry_minutes')->default(30);

    // Minutos después del start_time que se considera "Tardanza"
    // (anteriormente hardcodeado como 15 en el Service)
    $table->unsignedSmallInteger('late_threshold_minutes')->default(15);

    // Hora límite de registro. Después de esta hora no se acepta entrada.
    // Si es null, no hay cierre automático.
    $table->time('registration_closes_at')->nullable();
});
```

### Actualización de `PlantelAttendanceService::determineStatus()`

```php
protected function determineStatus(string $time, int $shiftId): string
{
    $shift = SchoolShift::find($shiftId);

    if (!$shift || !$shift->start_time) {
        return PlantelAttendanceRecord::STATUS_PRESENT;
    }

    $arrivalTime   = Carbon::parse($time);
    $shiftStart    = Carbon::parse($shift->start_time);
    $lateThreshold = $shiftStart->copy()->addMinutes($shift->late_threshold_minutes ?? 15);

    // ── Ventana de cierre ────────────────────────────────────────
    if ($shift->registration_closes_at) {
        $closesAt = Carbon::parse($shift->registration_closes_at);
        if ($arrivalTime->gt($closesAt)) {
            throw new \Exception(
                "El registro de entrada para la {$shift->type} cerró a las " .
                $closesAt->format('h:i A') . '.'
            );
        }
    }

    return $arrivalTime->lte($lateThreshold)
        ? PlantelAttendanceRecord::STATUS_PRESENT
        : PlantelAttendanceRecord::STATUS_LATE;
}
```

### Livewire — `ShiftWindowManager`

Nuevo componente en `app/Livewire/App/Attendance/ShiftWindowManager.php` que implementa la interfaz del Shift Flexibility Manager: lista de tandas a la izquierda, configuración de ventana a la derecha con alcance Solo por Hoy / Rango de Fechas / Permanente, y panel de impacto en lenguaje natural.

```php
namespace App\Livewire\App\Attendance;

use App\Models\Tenant\Academic\SchoolShift;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ShiftWindowManager extends Component
{
    use AuthorizesRequests;

    public ?int    $selectedShiftId         = null;
    public string  $scope                   = 'today';  // 'today' | 'range' | 'permanent'
    public string  $newLateThresholdMinutes = '';
    public string  $dateRangeStart          = '';
    public string  $dateRangeEnd            = '';

    #[Computed]
    public function shifts(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolShift::where('school_id', Auth::user()->school_id)->get();
    }

    #[Computed]
    public function selectedShift(): ?SchoolShift
    {
        return $this->selectedShiftId ? SchoolShift::find($this->selectedShiftId) : null;
    }

    #[Computed]
    public function impactDescription(): string
    {
        if (!$this->selectedShift || empty($this->newLateThresholdMinutes)) return '';

        $shiftName = $this->selectedShift->type;
        $newTime   = Carbon::parse($this->selectedShift->start_time)
            ->addMinutes((int) $this->newLateThresholdMinutes)
            ->format('h:i A');
        $oldTime   = Carbon::parse($this->selectedShift->start_time)
            ->addMinutes($this->selectedShift->late_threshold_minutes ?? 15)
            ->format('h:i A');

        return "Los estudiantes de la Tanda {$shiftName} serán marcados como \"Tarde\" " .
               "después de las {$newTime} en lugar de las {$oldTime}.";
    }

    public function applyAdjustment(): void
    {
        $this->authorize('shifts.configure');

        $this->validate([
            'selectedShiftId'         => 'required|exists:school_shifts,id',
            'newLateThresholdMinutes' => 'required|integer|min:0|max:120',
            'scope'                   => 'required|in:today,range,permanent',
            'dateRangeStart'          => 'required_if:scope,range|date',
            'dateRangeEnd'            => 'required_if:scope,range|date|after_or_equal:dateRangeStart',
        ]);

        $shift = SchoolShift::findOrFail($this->selectedShiftId);
        $shift->update(['late_threshold_minutes' => (int) $this->newLateThresholdMinutes]);

        $this->dispatch('notify',
            type: 'success',
            message: "Ventana horaria actualizada para la tanda {$shift->type}."
        );
    }

    public function render()
    {
        return view('livewire.app.attendance.shift-window-manager')
            ->layout('layouts.app-module', config('modules.asistencia'));
    }
}
```

**Ruta nueva:**

```php
// routes/app/attendance.php
Route::get('/attendance/shift-windows', ShiftWindowManager::class)
    ->name('app.attendance.shift-windows')
    ->middleware('can:shifts.configure');
```

---

## Fase 4 — Selector Universal de Cursos

**Rama:** `feature/v0.9.0-course-selector`

### Contexto

Con 40+ secciones activas en un centro polivalente (Académico + Técnico con múltiples familias y títulos), los `<select>` nativos actuales son inutilizables en mobile y lentos en desktop. El componente propuesto es un modal de búsqueda tipo Spotlight con filtros en tiempo real, navegación por teclado y soporte completo para nombres largos de títulos técnicos.

### Componente Livewire — `CourseSelectorModal`

```php
// app/Livewire/Shared/CourseSelectorModal.php

namespace App\Livewire\Shared;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CourseSelectorModal extends Component
{
    public bool   $open        = false;
    public string $search      = '';
    public string $filterLevel = '';   // '' | 'primaria' | 'secundaria'
    public string $filterShift = '';   // '' | shift_id
    public string $context     = 'default';

    #[Computed]
    public function sections(): \Illuminate\Support\Collection
    {
        return SchoolSection::withFullRelations()
            ->where('school_id', Auth::user()->school_id)
            ->where('is_active', true)
            ->get()
            ->filter(function ($section) {
                if ($this->search) {
                    $haystack = mb_strtolower($section->full_label);
                    $needle   = mb_strtolower($this->search);
                    if (!str_contains($haystack, $needle)) return false;
                }
                if ($this->filterShift) {
                    if ($section->school_shift_id !== (int) $this->filterShift) return false;
                }
                if ($this->filterLevel) {
                    $levelName = mb_strtolower($section->grade->level->name ?? '');
                    if (!str_contains($levelName, $this->filterLevel)) return false;
                }
                return true;
            })
            ->sortBy(fn ($s) => $s->grade->level->name . $s->grade->name . $s->label)
            ->values();
    }

    #[Computed]
    public function shifts(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolShift::where('school_id', Auth::user()->school_id)->get();
    }

    #[On('open-course-selector')]
    public function openModal(string $context = 'default'): void
    {
        $this->context = $context;
        $this->reset(['search', 'filterLevel', 'filterShift']);
        $this->open = true;
    }

    public function select(int $sectionId): void
    {
        $this->dispatch('section-selected', sectionId: $sectionId, context: $this->context);
        $this->open = false;
    }

    public function render()
    {
        return view('livewire.shared.course-selector-modal');
    }
}
```

### Vistas a Migrar

| Vista | Contexto actual | Prioridad |
| :--- | :--- | :--- |
| `manual-attendance.blade.php` | Filtro de sección en pase de lista | Alta |
| `classroom-attendance-live.blade.php` | Selector de clase/sección | Alta |
| `attendance-reports.blade.php` | Filtro de sección en reportes | Media |
| `enrollment-hub.blade.php` | Árbol de secciones destino | Media |
| `teacher-assignments.blade.php` | Panel de secciones del maestro | Baja |

---

## Fase 5 — Rediseño del Pase de Lista en Aula

**Rama:** `feature/v0.9.0-classroom-swipe`

### Diagnóstico del Flujo Actual

El componente `ClassroomAttendanceLive.php` carga la lista de estudiantes en `$studentStatuses[]` y la vista la renderiza como tabla con botones por fila. Para una clase de 35 estudiantes esto implica mínimo 35 + 5 interacciones.

### Nueva Arquitectura — Vista de Tarjeta con Gestos

El rediseño mantiene el backend PHP intacto. Solo cambia la vista Blade y agrega la lógica de gestos en Alpine.js. Los métodos `loadStudents()`, `setStatus()`, `saveAttendance()` de `ClassroomAttendanceLive.php` no se modifican.

#### Estructura de la Vista

```
┌──────────────────────────────────────┐
│  [ Barra de progreso — 17 de 32 ]   │
├──────────────────────────────────────┤
│      FOTO DEL ESTUDIANTE             │
│      Nombre Completo                 │
│      Cédula / Matrícula              │
│      Badge de estado (si ya marcado) │
│                                      │
│   ←  Ausente    Presente  →          │
│         ↓  Tardanza                  │
├──────────────────────────────────────┤
│  [ ✗ Ausente | ✓ Presente ]         │
│  [ ⏰ Tardanza | ⏭ Saltar ]         │
└──────────────────────────────────────┘
```

#### Implementación Alpine de Gestos

```js
Alpine.data('classroomSwiper', (students, statuses) => ({
    students: students,
    statuses: statuses,
    currentIndex: 0,

    touchStartX: 0,
    touchStartY: 0,
    dragX: 0,
    dragY: 0,
    isDragging: false,
    pendingAction: null,

    SWIPE_THRESHOLD: 80,

    get currentStudent() { return this.students[this.currentIndex] ?? null; },

    get progress() {
        return {
            done:  Object.values(this.statuses).filter(Boolean).length,
            total: this.students.length,
        };
    },

    onTouchStart(e) {
        this.touchStartX = e.touches[0].clientX;
        this.touchStartY = e.touches[0].clientY;
        this.isDragging  = true;
    },

    onTouchMove(e) {
        if (!this.isDragging) return;
        this.dragX = e.touches[0].clientX - this.touchStartX;
        this.dragY = e.touches[0].clientY - this.touchStartY;
        const absX = Math.abs(this.dragX);
        const absY = Math.abs(this.dragY);
        if (absX > absY)        this.pendingAction = this.dragX > 0 ? 'present' : 'absent';
        else if (this.dragY > 0) this.pendingAction = 'late';
        else                     this.pendingAction = null;
    },

    onTouchEnd() {
        const absX = Math.abs(this.dragX);
        const absY = Math.abs(this.dragY);
        if (absX > this.SWIPE_THRESHOLD && absX > absY)
            this.mark(this.dragX > 0 ? 'present' : 'absent');
        else if (absY > this.SWIPE_THRESHOLD && this.dragY > 0 && absY > absX)
            this.mark('late');
        this.resetDrag();
    },

    mark(status) {
        if (!this.currentStudent) return;
        const studentId = this.currentStudent.id;
        this.statuses[studentId] = status;
        @this.setStatus(studentId, status);
        this.nextStudent();
    },

    nextStudent() {
        if (this.currentIndex < this.students.length - 1) this.currentIndex++;
    },

    resetDrag() { this.dragX = 0; this.dragY = 0; this.isDragging = false; this.pendingAction = null; },

    get cardStyle() {
        const rotation = this.dragX * 0.05;
        return `transform: translateX(${this.dragX}px) translateY(${this.dragY > 0 ? this.dragY * 0.3 : 0}px) rotate(${rotation}deg); transition: ${this.isDragging ? 'none' : 'transform 0.3s ease'};`;
    },

    get overlayColor() {
        return { present: 'bg-emerald-500/60', absent: 'bg-red-500/60', late: 'bg-amber-500/60' }[this.pendingAction] ?? '';
    },
}));
```

---

## Fase 6 — Planificación de App Móvil para Tutores

**Estado:** Sin código. Solo decisiones de arquitectura para versiones futuras.

**Stack seleccionado:** Flutter con Firebase Cloud Messaging (FCM) para notificaciones push.

El backend Laravel expondrá una API dedicada bajo `/api/v1/parent/` con Sanctum. Los tutores se autentican con el email y contraseña de su cuenta vinculada al estudiante.

| Evento | Canal | Destinatario |
| :--- | :--- | :--- |
| Estudiante marcado como Presente en plantel | Push | Tutor |
| Estudiante marcado como Tardanza en plantel | Push | Tutor |
| Estudiante marcado como Ausente en plantel | Push | Tutor |
| Estudiante ausente en clase (aula) | Push | Tutor |

---

## Fase 7 — Ajustes Visuales al Navbar en Mobile

**Rama:** `feature/v0.9.0-navbar-mobile`

Correcciones menores en `resources/views/components/app/navbar.blade.php`:

```blade
{{-- 1. Fondo del header en modo módulo en mobile --}}
'bg-white dark:bg-[#0f1828] sm:bg-white/95 sm:dark:bg-dark-card border-b border-slate-200 dark:border-white/8 backdrop-blur-xl shadow-sm h-14' => $isModule,

{{-- 2. Reducir gap en sección derecha en mobile --}}
<div class="flex items-center gap-0.5 sm:gap-1 flex-shrink-0">

{{-- 3. Ocultar botón fullscreen en xs --}}
<div class="hidden sm:block" x-data="{ isFullscreen: false }">
    {{-- lógica de fullscreen sin cambios --}}
</div>
```

---

## Fase 8 — Evaluación del Dominio de Tutores

**Estado:** Sin código. Decisión documentada.

No se creará un módulo de Tutores en v0.9.0. Los campos `tutor_name` y `tutor_phone` en el modelo `Student` son suficientes para las necesidades actuales. La complejidad de un módulo completo (vínculos múltiples tutor↔estudiante, consentimientos biométricos, portales de acceso) justifica una versión propia.

**Pendiente para v1.1.0:** Modelo `Tutor` con relación polimórfica a `Student`, portal de consulta web y credenciales de acceso para tutores.

---

## Fase 9 — Páginas de Error Personalizadas

**Rama:** `feature/v0.9.0-error-pages`

Crear bajo `resources/views/errors/`: `403.blade.php`, `404.blade.php` y `500.blade.php`. Cada vista usa `<x-public-layout>` y sigue la estética del Design System ORVIAN con soporte completo para tema oscuro.

```blade
{{-- Estructura base — replicar para 403 y 500 con ícono y texto apropiados --}}
<x-public-layout>
    <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <div class="font-etna text-[120px] sm:text-[180px] leading-none text-slate-100 dark:text-white/5 select-none">
            404
        </div>
        <div class="mt-[-2rem] mb-6 w-16 h-16 rounded-2xl bg-orvian-orange/10 flex items-center justify-center">
            <x-heroicon-o-map class="w-8 h-8 text-orvian-orange" />
        </div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Página no encontrada</h1>
        <p class="text-slate-500 dark:text-slate-400 max-w-md leading-relaxed mb-8">
            La dirección que buscas no existe o fue movida. Verifica la URL o regresa al inicio.
        </p>
        <div class="flex items-center gap-3 flex-wrap justify-center">
            <x-ui.button onclick="if(history.length > 1) history.back();" variant="secondary" type="outline" iconLeft="heroicon-o-arrow-left">
                Volver
            </x-ui.button>
            <x-ui.button href="{{ Auth::check() ? route('app.dashboard') : route('landing') }}" variant="primary" iconLeft="heroicon-s-home">
                Ir al Inicio
            </x-ui.button>
        </div>
    </div>
</x-public-layout>
```

| Error | Ícono | Título | Descripción |
| :--- | :--- | :--- | :--- |
| 403 | `heroicon-o-lock-closed` | Acceso denegado | No tienes permiso para ver este recurso. Contacta al administrador. |
| 404 | `heroicon-o-map` | Página no encontrada | La dirección no existe o fue movida. Verifica la URL o regresa al inicio. |
| 500 | `heroicon-o-exclamation-triangle` | Error del sistema | Algo salió mal. El equipo fue notificado. Intenta de nuevo en unos minutos. |

---

## Fase 10 — Evolución del Sistema de Toasts

**Rama:** `feature/v0.9.0-toasts-v2`

### 10.1 — Stack Visual (Toasts Acumulativos)

```js
Alpine.data('toastManager', () => ({
    toasts: [],
    stackExpanded: false,

    get visibleToast()  { return this.toasts[this.toasts.length - 1] ?? null; },
    get stackedToasts() { return this.toasts.slice(0, -1); },
    // addToast, saveForRedirect, removeToast — sin cambios
}));
```

### 10.2 — Swipe-to-Dismiss

```js
Alpine.data('toastItem', (toast) => ({
    touchStartX: 0,
    swipeOffset: 0,

    onSwipeStart(e) { this.touchStartX = e.touches[0].clientX; this.pause(); },
    onSwipeMove(e)  { this.swipeOffset = Math.max(0, e.touches[0].clientX - this.touchStartX); },
    onSwipeEnd()    {
        if (this.swipeOffset > 100) this.close();
        else { this.swipeOffset = 0; this.resume(); }
    },
    get swipeStyle() {
        return this.swipeOffset > 0
            ? `transform: translateX(${this.swipeOffset}px); opacity: ${1 - this.swipeOffset / 200}; transition: none;`
            : 'transition: transform 0.3s ease, opacity 0.3s ease;';
    },
}));
```

### 10.3 — Refinamiento Visual

```blade
{{-- ANTES --}}
class="relative w-full max-w-sm overflow-hidden rounded-lg border-l-4 shadow-xl transition-all pointer-events-auto bg-white"

{{-- DESPUÉS --}}
class="relative w-full max-w-sm overflow-hidden rounded-xl border-l-[3px] shadow-lg transition-all pointer-events-auto bg-white dark:bg-dark-card"
```

### 10.4 — Documentación

Crear `docs/ui/toast.md` con descripción de eventos (`@notify`, `@notify-redirect`, `@remove-toast`), formato del payload, ejemplos de uso desde Livewire y Alpine, comportamiento del stack y del swipe, y guía de integración con sesiones PHP.

---

## Fase 11 — Corrección del Kit de Botones

**Rama:** `feature/v0.9.0-button-loading-fix`

### Diagnóstico

`wire:loading.class` en `button.blade.php` sin `wire:target` reactiva al estado de carga global del componente Livewire, haciendo que botones no relacionados parpadeen durante operaciones de guardado.

### Corrección

```blade
{{-- ANTES en button.blade.php --}}
{{ $attributes->except(['type', 'href'])->merge([
    'class' => $getButtonClasses($isIconOnly),
    'style' => $hexStyle,
    'wire:loading.class' => 'opacity-60 pointer-events-none',  // ← ELIMINAR
]) }}

{{-- DESPUÉS — wire:loading ya NO se aplica por defecto --}}
{{ $attributes->except(['type', 'href'])->merge([
    'class' => $getButtonClasses($isIconOnly),
    'style' => $hexStyle,
]) }}
```

Patrón canónico de uso (opt-in con `wire:target` explícito):

```blade
<x-ui.button
    variant="primary"
    wire:click="save"
    wire:loading.class.add="opacity-60 pointer-events-none"
    wire:loading.attr="disabled"
    wire:target="save">
    <span wire:loading.remove wire:target="save">Guardar cambios</span>
    <span wire:loading wire:target="save" class="flex items-center gap-2">
        <x-ui.loading size="sm" /> Guardando...
    </span>
</x-ui.button>
```

### Componentes a Revisar

| Componente | Acción a revisar |
| :--- | :--- |
| `teacher-form.blade.php` | Botón `save()` del formulario de maestro |
| `student-form.blade.php` | Botón `save()` del formulario de estudiante |
| `excuse-index.blade.php` | Botones `submit()`, `approve()`, `reject()` |
| `session-manager.blade.php` | Botones `openSession()` y `closeSession()` |
| `shift-window-manager.blade.php` | Botón `applyAdjustment()` (nuevo en v0.9.0) |

---

## Archivos a Crear / Modificar / Eliminar

### Repositorio `orvian` (Laravel)

| Archivo | Acción | Fase |
| :--- | :--- | :--- |
| `app/Livewire/App/Attendance/AttendanceScanner.php` | **🗑️ ELIMINAR** | Purge |
| `resources/views/livewire/app/attendance/attendance-scanner.blade.php` | **🗑️ ELIMINAR** | Purge |
| `resources/views/livewire/app/attendance/partials/scanner-stats.blade.php` | **🗑️ ELIMINAR** | Purge |
| `resources/views/livewire/app/attendance/partials/scanner-visor.blade.php` | **🗑️ ELIMINAR** | Purge |
| `public/assets/sounds/success.wav` | **🗑️ ELIMINAR** | Purge |
| `public/assets/sounds/error.wav` | **🗑️ ELIMINAR** | Purge |
| `public/vendor/face-api/` | **🗑️ ELIMINAR** (directorio completo) | Purge |
| `app/Livewire/Shared/ProfileModal.php` | Remover prop `$audioFeedback` y `savePreferences()` audio | Purge |
| `resources/views/livewire/shared/profile-modal.blade.php` | Remover sección de preferencia de audio | Purge |
| `resources/views/layouts/app-module.blade.php` | Remover Alpine `audioFeedback` y meta tag | Purge |
| `app/Models/Tenant/School.php` | Agregar trait `HasApiTokens` | 1 |
| `app/Http/Controllers/Api/Kiosk/KioskStatusController.php` | Crear | 1 |
| `app/Http/Controllers/Api/Kiosk/KioskQrRecordController.php` | Crear | 1 |
| `app/Http/Controllers/Api/Kiosk/KioskFacialRecordController.php` | Crear | 1 |
| `app/Http/Requests/Kiosk/RecordQrRequest.php` | Crear | 1 |
| `app/Http/Requests/Kiosk/RecordFacialRequest.php` | Crear | 1 |
| `app/Livewire/App/Settings/SchoolSettings.php` | Agregar `generateKioskToken()` | 1 |
| `routes/api.php` | Agregar grupo `/api/v1/kiosk/` | 1 |
| `database/migrations/xxxx_add_attendance_windows_to_school_shifts.php` | Crear migración | 3 |
| `app/Livewire/App/Attendance/ShiftWindowManager.php` | Crear componente Livewire | 3 |
| `resources/views/livewire/app/attendance/shift-window-manager.blade.php` | Crear vista | 3 |
| `app/Services/Attendance/PlantelAttendanceService.php` | Actualizar `determineStatus()` | 3 |
| `routes/app/attendance.php` | Agregar ruta `app.attendance.shift-windows` | 3 |
| `app/Livewire/Shared/CourseSelectorModal.php` | Crear componente Livewire | 4 |
| `resources/views/livewire/shared/course-selector-modal.blade.php` | Crear vista modal | 4 |
| `resources/views/livewire/app/attendance/manual-attendance.blade.php` | Migrar select al Selector Universal | 4 |
| `resources/views/livewire/app/attendance/classroom-attendance-live.blade.php` | Rediseño con gestos y Selector Universal | 4 + 5 |
| `resources/views/components/app/navbar.blade.php` | Ajustes visuales mobile | 7 |
| `resources/views/errors/403.blade.php` | Crear página de error | 9 |
| `resources/views/errors/404.blade.php` | Crear página de error | 9 |
| `resources/views/errors/500.blade.php` | Crear página de error | 9 |
| `resources/views/components/ui/toasts.blade.php` | Stack visual + swipe-to-dismiss | 10 |
| `docs/ui/toast.md` | Crear documentación | 10 |
| `resources/views/components/ui/button.blade.php` | Eliminar `wire:loading.class` global | 11 |
| `docs/ui/buttons.md` | Actualizar sección de estados de carga | 11 |

## Archivos a Crear — Repositorio `orvian-kiosk-electron` (nuevo)

| Archivo | Acción | Fase |
| :--- | :--- | :--- |
| `main/main.js` | Crear — punto de entrada del proceso principal | 2 |
| `main/config-store.js` | Crear — persistencia de token/configuración | 2 |
| `main/preload.js` | Crear — bridge seguro main↔renderer | 2 |
| `main/hardware/fingerprint.js` | Crear (placeholder) — preparación lector de huella futuro | 2 |
| `renderer/index.html` | Crear — ventana única del kiosko | 2 |
| `renderer/camera.js` | Crear — captura + detección MediaPipe Tasks-Vision | 2 |
| `renderer/api-client.js` | Crear — cliente HTTP hacia `/api/v1/kiosk/` | 2 |
| `renderer/ui-states.js` | Crear — los 4 estados visuales del kiosko | 2 |
| `renderer/setup-screen.js` | Crear — pantalla de configuración inicial (token) | 2 |
| `vendor/mediapipe/wasm/` + `models/` | Incluir en el repositorio | 2 |
| `assets/sounds/success.wav` + `error.wav` | Incluir en el repositorio | 2 |
| `electron-builder.yml` | Crear — configuración de empaquetado | 2 |
| `tailwind.config.js` | Crear — build standalone de Tailwind | 2 |
| `package.json` | Crear | 2 |

---

## Notas de Implementación (revisadas)

Las siguientes notas de la versión original eran específicas de Python y ya no aplican: threading en Tkinter, `pyzbar` + `zbar.dll`, `PyInstaller` con `binaries=[...]`, calidad de imagen JPEG vía OpenCV. Se reemplazan por:

**Seguridad del proceso de renderizado:** Electron debe configurarse con `contextIsolation: true` y `nodeIntegration: false` en el `BrowserWindow`. El renderer (donde corre la cámara y MediaPipe) nunca debe tener acceso directo a Node.js — todo acceso a hardware o sistema de archivos pasa por `preload.js` vía `contextBridge`, evitando que código malicioso embebido en la ventana pueda escalar privilegios.

**MediaPipe y el modelo de un solo hilo:** A diferencia de Python (donde fue necesario separar captura e inferencia en dos hilos para evitar lag visual), el renderer de Electron es JavaScript de un solo hilo con `requestAnimationFrame`. El patrón ya usado en `scanner-visor.blade.php` (un bucle de detección no bloqueante por frame) es directamente aplicable sin necesitar arquitectura de hilos adicional — esto es, de hecho, más simple que el problema que se resolvió en Python.

**Bundling de WASM:** Verificar en cada build de `electron-builder` que `extraResources` copie correctamente la carpeta `vendor/mediapipe/` al directorio de recursos del `.exe` final. Un error común es que `FilesetResolver.forVisionTasks()` reciba una ruta relativa que funciona en desarrollo (`npm start`) pero no en el ejecutable empaquetado, donde la estructura de carpetas cambia. Resolver siempre la ruta vía `process.resourcesPath` en producción.

**Seguridad del Token de Kiosko:** Sin cambios respecto al diseño original — el token con ability `kiosk` se persiste en `electron-store`, no expira por defecto, y es revocable desde Laravel si el dispositivo se pierde o compromete.

**VERSION:** Al completar los entregables de esta fase, actualizar el archivo `VERSION` en la raíz de `orvian` a `0.9.0` y crear el tag `v0.9.0` en el nuevo repositorio `orvian-kiosk-electron` (en lugar de `orvian-desktop-scanner`, que queda descartado).
