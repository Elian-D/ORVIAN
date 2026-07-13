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
| REQ-02 | 2 | Asistencia Biométrica | Arquitectura de `orvian-kiosk-electron`: app de escritorio con Electron + MediaPipe Tasks-Vision for Web (WASM local), sin lógica de QR | Alta | Completado |
| REQ-03 | 3 | Configuración | Ventanas horarias configurables por tanda (entrada, tardanza, cierre) | Alta | Completado|
| REQ-04 | 4 | UI / Componentes | Selector Universal de Cursos — componente Livewire reutilizable | Alta | A FUTURO |
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

## Fase 2.5 — Gestión de Dispositivos Kiosko y PIN de Técnico

> **Por qué existe esta fase:** La Fase 2 dejó documentada la arquitectura base de Electron. Durante su implementación inicial surgieron cambios no planificados en el repositorio `orvian` (Laravel) que deben consolidarse antes de continuar, y dos funcionalidades que son prerequisito para que el kiosko sea operable en producción: (1) soporte para múltiples dispositivos con tokens individuales y (2) el mecanismo de PIN para que un técnico pueda reconfigurar un kiosko cuyo token fue revocado, sin exponer la pantalla de setup a cualquier persona frente a la pantalla.

---

### 2.5.1 — Laravel: Soporte Multi-Token y PIN de Técnico

**Rama nueva:** `feature/v0.9.0-kiosk-devices`
**Base:** `feature/v0.9.0-platform-maturity`

```bash
git checkout feature/v0.9.0-platform-maturity
git checkout -b feature/v0.9.0-kiosk-devices
```

#### Migración: Campo `kiosk_pin` en `schools`

```php
// database/migrations/xxxx_add_kiosk_pin_to_schools_table.php

Schema::table('schools', function (Blueprint $table) {
    // Hash bcrypt del PIN numérico de 4-6 dígitos.
    // Null = sin PIN configurado (primer arranque, setup libre).
    $table->string('kiosk_pin')->nullable()->after('logo_path');
});
```

El PIN se guarda hasheado con `bcrypt()` — nunca en texto plano. El valor almacenado es idéntico en formato al de las contraseñas de `users`, por lo que `Hash::check()` funciona directamente.

#### Actualización del Modelo `School`

```php
// app/Models/Tenant/School.php

// Agregar a $fillable:
'kiosk_pin',

// Agregar al array $hidden para que no aparezca en respuestas JSON genéricas:
'kiosk_pin',
```

#### Actualización de `KioskStatusController`

El endpoint `/status` ya existe. Se le agrega `pin_hash` a la respuesta para que Electron pueda cachearlo y usarlo en validación local cuando el token sea revocado:

```php
// app/Http/Controllers/Api/Kiosk/KioskStatusController.php

public function __invoke(Request $request): JsonResponse
{
    $school = $request->user(); // School model, autenticado por Sanctum

    $activeSession = DailyAttendanceSession::where('school_id', $school->id)
        ->whereDate('date', today())
        ->active()
        ->with('shift')
        ->first();

    return response()->json([
        'school' => [
            'id'   => $school->id,
            'name' => $school->name,
        ],
        'session' => $activeSession ? [
            'id'         => $activeSession->id,
            'shift_name' => $activeSession->shift->type,
            'opened_at'  => $activeSession->opened_at->toIso8601String(),
        ] : null,
        // Hash bcrypt del PIN. Electron lo cachea en electron-store.
        // Nunca es el PIN en texto plano. Null si el director no ha configurado PIN.
        'pin_hash' => $school->kiosk_pin,
    ]);
}
```

#### Reemplazo del método `generateKioskToken()` en `SchoolSettings`

El método actual borra todos los tokens anteriores antes de crear uno nuevo. Esto se reemplaza por un sistema donde cada token tiene un nombre de dispositivo y puede gestionarse individualmente.

```php
// app/Livewire/App/Settings/SchoolSettings.php

// ── Nuevas propiedades para el modal de creación ──────────────

public bool   $showCreateDeviceModal = false;
public string $newDeviceName         = '';
public ?string $generatedToken       = null;   // Solo vive mientras el modal está abierto

// ── Nuevas propiedades para el modal de revocación ────────────

public bool   $showRevokeModal       = false;
public ?int   $tokenToRevokeId       = null;
public string $revokeConfirmName     = '';    // El usuario debe tipear el nombre del dispositivo

// ── Propiedad para gestión del PIN ────────────────────────────

public string $kioskPin              = '';
public string $kioskPinConfirm       = '';

/**
 * Crea un token individual para un dispositivo.
 * NO revoca tokens existentes.
 */
public function createDeviceToken(): void
{
    $this->authorize('settings.update');

    $this->validate([
        'newDeviceName' => ['required', 'string', 'min:3', 'max:50'],
    ]);

    try {
        $school = Auth::user()->school;

        // Verificar que no existe otro token activo con el mismo nombre
        $existingNames = $school->tokens()
            ->where('abilities', json_encode(['kiosk']))
            ->pluck('name');

        if ($existingNames->contains($this->newDeviceName)) {
            $this->addError('newDeviceName', 'Ya existe un dispositivo con ese nombre.');
            return;
        }

        $this->generatedToken = $school->createToken(
            $this->newDeviceName,
            ['kiosk']
        )->plainTextToken;

        // El modal transiciona a mostrar el token. No se cierra aún.
        $this->newDeviceName = '';

    } catch (\Exception $e) {
        Log::error('Error al crear token de dispositivo kiosko', [
            'school_id' => Auth::user()->school_id,
            'error'     => $e->getMessage(),
        ]);

        $this->dispatch('notify',
            type: 'error',
            title: 'Error',
            message: 'No se pudo generar el token. Intente de nuevo.'
        );
    }
}

/**
 * Inicia el flujo de revocación mostrando el modal de confirmación.
 */
public function confirmRevokeDevice(int $tokenId, string $tokenName): void
{
    $this->authorize('settings.update');
    $this->tokenToRevokeId  = $tokenId;
    $this->revokeConfirmName = '';
    // El modal de confirmación muestra el nombre y pide tipearlo
    $this->showRevokeModal  = true;
}

/**
 * Ejecuta la revocación tras la confirmación por nombre.
 */
public function revokeDevice(): void
{
    $this->authorize('settings.update');

    $token = Auth::user()->school->tokens()->find($this->tokenToRevokeId);

    if (!$token) {
        $this->dispatch('notify', type: 'error', message: 'Token no encontrado.');
        $this->resetRevokeModal();
        return;
    }

    // El usuario debe haber tipeado exactamente el nombre del dispositivo
    if ($this->revokeConfirmName !== $token->name) {
        $this->addError('revokeConfirmName', 'El nombre no coincide. Escríbelo exactamente.');
        return;
    }

    $token->delete();

    $this->dispatch('notify',
        type: 'success',
        title: 'Dispositivo desconectado',
        message: "El dispositivo \"{$token->name}\" ya no tiene acceso al sistema."
    );

    $this->resetRevokeModal();
}

/**
 * Guarda o actualiza el PIN de acceso al modo técnico del kiosko.
 */
public function saveKioskPin(): void
{
    $this->authorize('settings.update');

    $this->validate([
        'kioskPin'        => ['required', 'digits_between:4,6'],
        'kioskPinConfirm' => ['required', 'same:kioskPin'],
    ]);

    Auth::user()->school->update([
        'kiosk_pin' => bcrypt($this->kioskPin),
    ]);

    $this->kioskPin        = '';
    $this->kioskPinConfirm = '';

    $this->dispatch('notify',
        type: 'success',
        title: 'PIN actualizado',
        message: 'El nuevo PIN de técnico entrará en efecto en el próximo heartbeat del kiosko.'
    );
}

private function resetRevokeModal(): void
{
    $this->showRevokeModal  = false;
    $this->tokenToRevokeId  = null;
    $this->revokeConfirmName = '';
}
```

#### Vista — Sección "Dispositivos Kiosko" en `SchoolSettings`

La sección se añade en la vista de configuración de la escuela como una **Zona de Peligro** visualmente separada, colapsada por defecto con Alpine.js.

```html
{{-- resources/views/livewire/app/settings/school-settings.blade.php --}}
{{-- Añadir esta sección al final de la vista, antes del cierre del form --}}

<div x-data="{ open: false }" class="mt-10">

    {{-- Cabecera colapsable de la zona de peligro --}}
    <button
        @click="open = !open"
        class="w-full flex items-center justify-between p-4 rounded-2xl border border-red-200 dark:border-red-800/40 bg-red-50/50 dark:bg-red-900/10 text-left transition-colors hover:bg-red-100/50 dark:hover:bg-red-900/20">
        <div class="flex items-center gap-3">
            <x-heroicon-s-shield-exclamation class="w-5 h-5 text-red-500 flex-shrink-0" />
            <div>
                <p class="text-sm font-bold text-red-700 dark:text-red-400">Zona de Peligro — Dispositivos Kiosko</p>
                <p class="text-xs text-red-600/70 dark:text-red-500/70">
                    Tokens de acceso de terminales físicas y PIN de técnico.
                    Los cambios aquí afectan dispositivos en operación.
                </p>
            </div>
        </div>
        <x-heroicon-s-chevron-down class="w-4 h-4 text-red-400 transition-transform" ::class="open && 'rotate-180'" />
    </button>

    <div x-show="open" x-collapse class="mt-4 space-y-6">

        {{-- ── Lista de Dispositivos Activos ────────────────────── --}}
        <div class="rounded-2xl border border-gray-200 dark:border-dark-border overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-dark-border">
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Terminales registradas</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Cada terminal tiene un token de acceso único e independiente.
                        Revocar un token desconecta únicamente ese dispositivo.
                    </p>
                </div>
                <x-ui.button
                    wire:click="$set('showCreateDeviceModal', true)"
                    type="solid"
                    hex="#e85523"
                    size="sm"
                    iconLeft="heroicon-s-plus">
                    Nueva terminal
                </x-ui.button>
            </div>

            @php
                $kioskTokens = Auth::user()->school->tokens()
                    ->where('abilities', json_encode(['kiosk']))
                    ->latest()
                    ->get();
            @endphp

            @forelse ($kioskTokens as $token)
                <div class="flex items-center justify-between px-5 py-3.5 border-b last:border-0 border-gray-100 dark:border-dark-border/50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center flex-shrink-0">
                            <x-heroicon-s-computer-desktop class="w-4 h-4 text-gray-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $token->name }}</p>
                            <p class="text-xs text-gray-400">
                                Creado {{ $token->created_at->diffForHumans() }}
                                · Último uso {{ $token->last_used_at?->diffForHumans() ?? 'nunca' }}
                            </p>
                        </div>
                    </div>
                    <x-ui.button
                        wire:click="confirmRevokeDevice({{ $token->id }}, '{{ $token->name }}')"
                        type="outline"
                        hex="#ef4444"
                        size="sm"
                        iconLeft="heroicon-s-trash">
                        Revocar
                    </x-ui.button>
                </div>
            @empty
                <div class="px-5 py-8 text-center">
                    <x-heroicon-o-computer-desktop class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                    <p class="text-sm text-gray-400">No hay terminales registradas.</p>
                </div>
            @endforelse
        </div>

        {{-- ── PIN de Técnico ────────────────────────────────────── --}}
        <div class="rounded-2xl border border-gray-200 dark:border-dark-border p-5 space-y-4">
            <div>
                <p class="text-sm font-bold text-gray-900 dark:text-white">PIN de acceso técnico</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Código numérico de 4 a 6 dígitos que el técnico debe ingresar en el kiosko
                    para acceder al formulario de configuración. Si no hay PIN configurado,
                    el formulario de configuración es accesible sin restricciones.
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.forms.input
                    wire:model="kioskPin"
                    type="password"
                    label="Nuevo PIN"
                    placeholder="••••"
                    inputmode="numeric"
                    maxlength="6" />
                <x-ui.forms.input
                    wire:model="kioskPinConfirm"
                    type="password"
                    label="Confirmar PIN"
                    placeholder="••••"
                    inputmode="numeric"
                    maxlength="6" />
            </div>
            <x-ui.button
                wire:click="saveKioskPin"
                type="outline"
                hex="#e85523"
                size="sm">
                Guardar PIN
            </x-ui.button>
        </div>

    </div>
</div>

{{-- ── Modal: Crear nueva terminal ──────────────────────────────── --}}
<x-ui.modal wire:model="showCreateDeviceModal" maxWidth="md">
    @if (!$generatedToken)
        {{-- Paso 1: Ingresar nombre --}}
        <div class="p-6 space-y-5">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Registrar nueva terminal</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Asigna un nombre descriptivo a este dispositivo kiosko.
                    Podrás identificarlo en la lista para revocarlo individualmente si es necesario.
                </p>
            </div>
            <x-ui.forms.input
                wire:model="newDeviceName"
                label="Nombre del dispositivo"
                placeholder="Ej: Portería Principal, Entrada Norte..." />
            <div class="flex justify-end gap-3">
                <x-ui.button wire:click="$set('showCreateDeviceModal', false)" type="ghost" size="sm">Cancelar</x-ui.button>
                <x-ui.button wire:click="createDeviceToken" type="solid" hex="#e85523" size="sm">Generar token</x-ui.button>
            </div>
        </div>
    @else
        {{-- Paso 2: Mostrar el token (única vez) --}}
        <div class="p-6 space-y-5">
            <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40">
                <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                <p class="text-sm text-amber-800 dark:text-amber-300 font-medium">
                    Este token solo se muestra ahora. Una vez cerres este modal,
                    no habrá forma de recuperarlo — deberás generar uno nuevo.
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Token de acceso</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 text-xs bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 font-mono text-gray-800 dark:text-gray-200 break-all select-all">
                        {{ $generatedToken }}
                    </code>
                    <button
                        x-data
                        @click="navigator.clipboard.writeText('{{ $generatedToken }}'); $dispatch('notify', { type: 'success', message: 'Token copiado.' })"
                        class="flex-shrink-0 p-2.5 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                        <x-heroicon-s-clipboard class="w-4 h-4 text-gray-500" />
                    </button>
                </div>
            </div>
            <div class="flex justify-end">
                <x-ui.button
                    wire:click="$set('showCreateDeviceModal', false); $set('generatedToken', null)"
                    type="solid"
                    hex="#e85523"
                    size="sm">
                    Entendido, cerrar
                </x-ui.button>
            </div>
        </div>
    @endif
</x-ui.modal>

{{-- ── Modal: Confirmar revocación ─────────────────────────────── --}}
<x-ui.modal wire:model="showRevokeModal" maxWidth="md">
    <div class="p-6 space-y-5">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-2xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                <x-heroicon-s-trash class="w-5 h-5 text-red-500" />
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">¿Revocar este dispositivo?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    El kiosko asociado perderá acceso inmediatamente y mostrará
                    un error de token inválido. Esta acción no se puede deshacer.
                </p>
            </div>
        </div>
        <div>
            <x-ui.forms.input
                wire:model="revokeConfirmName"
                label="Escribe el nombre del dispositivo para confirmar"
                placeholder="Nombre exacto del dispositivo" />
        </div>
        <div class="flex justify-end gap-3">
            <x-ui.button wire:click="$set('showRevokeModal', false)" type="ghost" size="sm">Cancelar</x-ui.button>
            <x-ui.button wire:click="revokeDevice" type="solid" hex="#ef4444" size="sm" iconLeft="heroicon-s-trash">
                Sí, revocar acceso
            </x-ui.button>
        </div>
    </div>
</x-ui.modal>
```

### Partialización de la vista de configuración

La vista `resources/views/livewire/app/settings/school-settings.blade.php` tiene 600+ líneas. Para mantener la legibilidad, se hará a cabo una partialización de las secciones de configuracion del centro: Identidad e Información General, Estructura Educativa, Ubicación Física y la nueva Zona de Peligro. En archivos independientes bajo `resources/views/livewire/app/settings/school-partials/` (por si entran más archivos) y se incluirán con `@include()` y cada archivo debe tener el formato de `_nombre-archivo`.

**Archivos parciales:**

- `_identity-info.blade.php`
- `_educational-structure.blade.php`
- `_physical-location.blade.php`
- `_danger-zone.blade.php`

---

### 2.5.2 — Electron: Manejo de Token Revocado y Pantalla de PIN

**Repositorio:** `orvian-kiosk-electron`

Esta sección documenta los dos cambios en Electron que dependen de lo implementado en 2.5.1.

#### Fix estructural: `Accept: application/json` en todas las peticiones

El error `SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON` ocurre porque cuando Sanctum rechaza un token inválido, Laravel devuelve una página HTML de redirección si el cliente no declara explícitamente que espera JSON. La corrección es agregar `Accept: application/json` en el constructor de `ApiClient`, lo que hace que Laravel responda siempre con JSON estructurado (incluyendo `{"message": "Unauthenticated."}` en lugar de HTML):

```javascript
// renderer/api-client.js

export class ApiClient {
    constructor(serverUrl, token) {
        this.base = `${serverUrl.replace(/\/$/, '')}/api/v1/kiosk`;
        this.headers = {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',   // ← Fix crítico
        };
    }

    async getStatus() {
        const resp = await fetch(`${this.base}/status`, { headers: this.headers });

        if (resp.status === 401) {
            throw new TokenRevokedError();
        }

        if (!resp.ok) {
            throw new Error(`Status ${resp.status}`);
        }

        const data = await resp.json();

        // Cachear el pin_hash en electron-store para validación offline posterior
        if (data.pin_hash) {
            await window.orvianConfig.set('cached_pin_hash', data.pin_hash);
        }

        return data;
    }

    async recordFacial(sessionId, blob) {
        const form = new FormData();
        form.append('session_id', sessionId);
        form.append('photo', blob, 'capture.jpg');

        const resp = await fetch(`${this.base}/record/facial`, {
            method: 'POST',
            headers: this.headers,
            body: form,
        });

        if (resp.status === 401) {
            throw new TokenRevokedError();
        }

        return resp.json();
    }
}

export class TokenRevokedError extends Error {
    constructor() {
        super('TOKEN_REVOKED');
        this.name = 'TokenRevokedError';
    }
}
```

#### Cache del `pin_hash` vía `preload.js`

El preload ya expone `orvianConfig.get` y `orvianConfig.set`. No se necesita ningún cambio adicional — `cached_pin_hash` se guarda como cualquier otra clave en `electron-store`.

#### Pantalla de PIN Gate (`renderer/pin-gate.js`)

Cuando cualquier petición lanza `TokenRevokedError`, el flujo de UI llama a `showPinGate()`. Esta función reemplaza la pantalla del kiosko con el formulario de PIN:

```javascript
// renderer/pin-gate.js

import bcrypt from 'bcryptjs';   // npm install bcryptjs

export async function showPinGate(onUnlocked) {
    const cachedHash = await window.orvianConfig.get('cached_pin_hash');

    const container = document.getElementById('app');
    container.innerHTML = `
        <div class="min-h-screen bg-gray-950 flex items-center justify-center p-6">
            <div class="w-full max-w-sm space-y-6">

                <div class="text-center space-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-red-500/10 border border-red-500/20
                                flex items-center justify-center mx-auto">
                        <!-- heroicon: lock-closed -->
                        <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75
                                   9h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25
                                   2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5
                                   12v6.75A2.25 2.25 0 006.75 21.75z" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-white">Token revocado</h1>
                    <p class="text-sm text-gray-400">
                        Este dispositivo ya no tiene acceso al sistema.<br>
                        Ingresa el PIN de técnico para reconfigurar.
                    </p>
                </div>

                ${cachedHash ? `
                    <div class="space-y-3">
                        <input
                            id="pin-input"
                            type="password"
                            inputmode="numeric"
                            maxlength="6"
                            placeholder="PIN de técnico"
                            class="w-full text-center text-2xl tracking-widest bg-white/5 border
                                   border-white/10 rounded-2xl px-4 py-4 text-white placeholder-gray-600
                                   focus:outline-none focus:border-orvian-orange/50 focus:ring-1
                                   focus:ring-orvian-orange/30 transition-all" />
                        <p id="pin-error" class="text-xs text-red-400 text-center hidden">
                            PIN incorrecto. Inténtalo de nuevo.
                        </p>
                        <button
                            id="pin-submit"
                            class="w-full py-3 rounded-2xl bg-orvian-orange hover:bg-orvian-orange-hover
                                   text-white font-bold text-sm transition-colors">
                            Desbloquear
                        </button>
                    </div>
                ` : `
                    <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-center">
                        <p class="text-sm text-amber-300">
                            No hay PIN almacenado en este dispositivo.<br>
                            Puedes reconfigurar directamente.
                        </p>
                    </div>
                    <button
                        id="pin-submit"
                        class="w-full py-3 rounded-2xl bg-orvian-orange hover:bg-orvian-orange-hover
                               text-white font-bold text-sm transition-colors">
                        Ir a configuración
                    </button>
                `}

            </div>
        </div>
    `;

    const submitBtn = document.getElementById('pin-submit');
    const pinInput  = document.getElementById('pin-input');
    const pinError  = document.getElementById('pin-error');

    submitBtn.addEventListener('click', async () => {
        if (!cachedHash) {
            // Sin hash almacenado: acceso libre (primer arranque o dispositivo nunca conectado)
            onUnlocked();
            return;
        }

        const entered = pinInput?.value?.trim();
        if (!entered) return;

        const valid = await bcrypt.compare(entered, cachedHash);

        if (valid) {
            onUnlocked();
        } else {
            pinError.classList.remove('hidden');
            pinInput.value = '';
            pinInput.focus();
        }
    });

    pinInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') submitBtn.click();
    });
}
```

#### Integración en `setup-screen.js`

```javascript
// renderer/setup-screen.js (fragmento — función de heartbeat existente)

import { TokenRevokedError } from './api-client.js';
import { showPinGate } from './pin-gate.js';
import { showSetupForm } from './setup-form.js'; // formulario existente de token + URL

async function evaluateKioskState() {
    try {
        const status = await apiClient.getStatus();
        // ... lógica normal de sesión ...
    } catch (err) {
        if (err instanceof TokenRevokedError) {
            // Detener el heartbeat antes de mostrar el PIN gate
            clearInterval(heartbeatInterval);

            showPinGate(() => {
                // Callback ejecutado al validar el PIN correctamente
                showSetupForm();
            });
        } else {
            // Otros errores (red, timeout, etc.) — mostrar estado de error sin salir del kiosko
            showConnectionError(err.message);
        }
    }
}
```

---

### 2.5.3 — Archivos Nuevos y Modificados

#### En `orvian` (Laravel) — rama `feature/v0.9.0-kiosk-devices`

| Archivo | Acción |
| :--- | :--- |
| `database/migrations/xxxx_add_kiosk_pin_to_schools_table.php` | Crear |
| `app/Models/Tenant/School.php` | Modificar — agregar `kiosk_pin` a `$fillable` y `$hidden` |
| `app/Http/Controllers/Api/Kiosk/KioskStatusController.php` | Modificar — agregar `pin_hash` a la respuesta |
| `app/Livewire/App/Settings/SchoolSettings.php` | Modificar — reemplazar `generateKioskToken()`, agregar métodos de gestión multi-token y PIN |
| `resources/views/livewire/app/settings/school-settings.blade.php` | Modificar — agregar sección Zona de Peligro con lista de dispositivos, modales y formulario de PIN |

#### En `orvian-kiosk-electron` — rama `main`

| Archivo | Acción |
| :--- | :--- |
| `renderer/api-client.js` | Modificar — agregar `Accept: application/json`, manejo de 401 con `TokenRevokedError`, cacheo de `pin_hash` |
| `renderer/pin-gate.js` | Crear — pantalla de PIN gate con validación bcrypt local |
| `renderer/setup-screen.js` | Modificar — interceptar `TokenRevokedError` y delegar a `showPinGate()` |
| `package.json` | Modificar — agregar dependencia `bcryptjs` |

---

### 2.5.4 — Notas de Implementación

**¿Por qué `bcryptjs` en Electron y no una llamada a Laravel?**
Cuando el token es revocado, Electron no tiene credenciales válidas para hacer ninguna petición autenticada. Crear un endpoint público de validación de PIN introduce una superficie de ataque — cualquiera que conozca la URL podría intentar fuerza bruta. La solución de cachear el hash y comparar localmente es la estándar en aplicaciones offline-capable: el PIN se verifica con el mismo algoritmo bcrypt que usó Laravel para guardarlo, pero sin necesitar conexión en ese momento.

**¿Qué pasa si el director cambia el PIN mientras el kiosko está activo?**
El hash viejo permanece en `cached_pin_hash` de `electron-store` hasta el próximo heartbeat exitoso. Esto significa que durante máximo 30 segundos (intervalo del heartbeat), el kiosko todavía validaría con el PIN anterior. En la práctica esto no es un problema operativo — cambiar el PIN es un evento infrecuente, y el margen de 30 segundos es irrelevante.

**¿Qué pasa si el director nunca configuró un PIN?**
`pin_hash` vendrá `null` en la respuesta de `/status`. `electron-store` guardará `null`. La pantalla de PIN gate detecta `null` y muestra directamente el botón "Ir a configuración" sin pedir código. Esto es el comportamiento correcto para instalaciones nuevas.

**Sobre la rama de Electron:**
El repositorio `orvian-kiosk-electron` usa `main` directamente (repositorio nuevo, sin historial previo que proteger). Los cambios de esta fase van en `main` sin rama de feature.

# ORVIAN v0.9.0 — Fase 2.6: QR por Lector USB y Control de Features por Plan

> **Alcance:** Esta fase añade dos capacidades al ecosistema `orvian-kiosk-electron`:
> 1. Registro de asistencia por código QR mediante lector USB (modo *keyboard wedge*), desacoplado de la cámara facial.
> 2. Control de modo de operación del kiosko basado en las features del plan de la escuela: el endpoint `/status` informa qué tiene habilitado el plan, y Electron renderiza la interfaz correspondiente sin cámara (QR only) o con cámara (Facial + QR).
>
> **No requiere rama nueva en `orvian`** — los cambios en Laravel son solo en `KioskStatusController` (ya existente) y en el `CSP` del `index.html`. Los cambios de UI son exclusivamente en `orvian-kiosk-electron`.

---

## Contexto: cómo funcionan los lectores USB en modo wedge

Un lector QR o de barras en modo *keyboard wedge* no requiere driver ni SDK. El sistema operativo lo reconoce como un teclado HID. Cuando el lector escanea un código, "tipea" el contenido del código seguido de un carácter de terminación (generalmente `Enter` o `Tab`). Desde el punto de vista de Electron, es exactamente como si el usuario hubiera escrito el código con el teclado.

Esto tiene una implicación de diseño importante: **el input que captura los códigos no debe estar visible en pantalla ni ser accesible para el estudiante**. Se implementa como un `<input>` oculto que mantiene el foco permanentemente, acumulando caracteres hasta recibir el `Enter` del lector.

---

## Cambios en Laravel — `KioskStatusController`

El endpoint `/status` ya existe y funciona. Se le agregan dos campos al JSON de respuesta para informar al kiosko qué features tiene habilitadas el plan de la escuela:

```php
// app/Http/Controllers/Api/Kiosk/KioskStatusController.php

public function __invoke(Request $request): JsonResponse
{
    $school = $request->user();

    // Cargar el plan con sus features en una sola query
    $school->loadMissing('plan.features');

    $session = DailyAttendanceSession::query()
        ->where('school_id', $school->id)
        ->whereDate('date', today())
        ->active()
        ->with('shift')
        ->first();

    return response()->json([
        'school_name'      => $school->name,
        'session_active'   => (bool) $session,
        'session_id'       => $session?->id,
        'server_time'      => now()->toIso8601String(),
        'pin_hash'         => $school->kiosk_pin,

        // Features del plan — Electron decide qué interfaz mostrar
        'features' => [
            'attendance_qr'     => $school->plan?->hasFeature('attendance_qr') ?? false,
            'attendance_facial' => $school->plan?->hasFeature('attendance_facial') ?? false,
        ],
    ]);
}
```

**Respuesta de ejemplo — plan básico (QR solamente):**

```json
{
  "school_name": "Colegio San José",
  "session_active": true,
  "session_id": 12,
  "server_time": "2026-06-27T07:45:00-04:00",
  "pin_hash": "$2y$10$...",
  "features": {
    "attendance_qr": true,
    "attendance_facial": false
  }
}
```

**Respuesta de ejemplo — plan pro:**

```json
{
  "features": {
    "attendance_qr": true,
    "attendance_facial": true
  }
}
```

El campo `features` es el único cambio. El contrato existente con `session_active`, `session_id` y `pin_hash` no se modifica.

### Corregir envio de foto

En `app/Http/Controllers/Api/Kiosk/KioskQrRecordController.php` se agrego el parceo de la ruta ya que utilizaba photo_url y es photo_path, pero adiconalmente se envia ya correctamente con la url.

```php

    /**
     * Solución al formateo de la URL:
     * Como tu base de datos guarda "schools/1/students/archivo.jpg", al concatenarlo con 'storage/'
     * y pasarlo por asset(), Laravel generará automáticamente:
     * Local: http://orvian.test/storage/schools/1/students/archivo.jpg
     * Prod:  https://orvian.com.do/storage/schools/1/students/archivo.jpg
     *
     * Nota: Usamos asset() aquí porque evita las falsas alertas de error en el IDE que suele dar Storage::url()
     */
    $photoUrl = $result->student->photo_path 
        ? asset('storage/' . $result->student->photo_path) 
        : null;


    return response()->json([
        'success'    => true,
        'student'    => [
            'id'         => $result->student->id,
            'full_name'  => $result->student->full_name,
            'photo_url'  => $photoUrl, // Enviamos la URL absoluta resuelta
        ],
        'status'     => $result->attendanceStatus,  // 'present' | 'late'
        'recorded_at'=> $result->recordedAt->toIso8601String(),
    ]);

```


### Corregir componente

En `resources/views/livewire/admin/plans/plan-features.blade.php` se estaba usando un componente dinamico para los iconos de los módulos, el cuál anteriormente se había cambiado. Este fue reemplazado por el componente de `ui.module-icon`

```html
    <x-ui.module-icon
        :name="$features->first()->getIcon()"
        class="w-5 h-5 opacity-60 group-hover/item:opacity-100 transition-opacity"
    />
```


---

## Cambios en Electron — `orvian-kiosk-electron`

### Estado global de features

En `setup-screen.js`, el objeto de features recibido del `/status` se guarda en una variable de módulo. Esto evita tener que pasar el estado como parámetro por toda la cadena de funciones:

```javascript
// renderer/setup-screen.js

// Variable de módulo — se actualiza en cada evaluateKioskState exitoso
let kioskFeatures = {
    attendance_qr:     false,
    attendance_facial: false,
};
```

En `evaluateKioskState`:

```javascript
async function evaluateKioskState(client) {
    try {
        const status = await client.getStatus();
        if (status?.school_name) lastKnownSchoolName = status.school_name;

        // Actualizar features en cada heartbeat (el plan puede cambiar en el servidor)
        if (status?.features) {
            kioskFeatures = status.features;
        }

        // Cachear pin_hash para validación offline
        await window.orvianConfig.set('cached_pin_hash', status.pin_hash ?? null);

        if (status?.session_active) {
            currentSessionId = status.session_id;
            await activateKioskMode(client); // ← reemplaza turnOnCamera()
        } else {
            currentSessionId = null;
            deactivateKioskMode();
        }
    } catch (err) {
        if (err instanceof TokenRevokedError) {
            clearInterval(heartbeatInterval);
            showPinGate(() => showConfigForm());
        } else {
            console.warn('Error de conexión:', err.message);
        }
    }
}
```

---

### `activateKioskMode()` — decide qué interfaz mostrar

Esta función reemplaza `turnOnCamera()`. Evalúa las features para determinar el modo de operación:

```javascript
// renderer/setup-screen.js

async function activateKioskMode(client) {
    if (kioskFeatures.attendance_facial) {
        // Plan Pro: interfaz con cámara facial + QR listener en paralelo
        await turnOnCamera();
        startQrListener(client); // El QR listener corre siempre si el plan lo tiene
    } else if (kioskFeatures.attendance_qr) {
        // Plan básico: solo QR, sin cámara
        turnOffCamera();         // Asegurar que la cámara no esté encendida
        UI.renderQrOnly(lastKnownSchoolName);
        startQrListener(client);
    } else {
        // Sin features de asistencia — no debería ocurrir, pero se maneja
        UI.render('no_session', { school_name: lastKnownSchoolName });
    }
}

function deactivateKioskMode() {
    stopQrListener();
    turnOffCamera();
    UI.render('no_session', { school_name: lastKnownSchoolName });
}
```

---

### `qr-listener.js` — captura de lector USB

El lector USB "tipea" caracteres en el sistema operativo. El listener mantiene un `<input>` oculto con foco permanente. Cuando recibe `Enter`, interpreta el buffer acumulado como el código QR y lo envía a Laravel:

```javascript
// renderer/qr-listener.js

let _client = null;
let _sessionIdRef = null; // función que devuelve el sessionId actual
let _isListening = false;

export function startQrListener(client, getSessionId) {
    if (_isListening) return;
    _client = client;
    _sessionIdRef = getSessionId;
    _isListening = true;

    // Input oculto — el foco del lector va aquí
    let hiddenInput = document.getElementById('qr-wedge-input');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.id = 'qr-wedge-input';
        hiddenInput.setAttribute('aria-hidden', 'true');
        // Fuera del viewport, invisible al usuario
        hiddenInput.style.cssText = `
            position: fixed; top: -9999px; left: -9999px;
            opacity: 0; width: 1px; height: 1px;
            pointer-events: none;
        `;
        document.body.appendChild(hiddenInput);
    }

    hiddenInput.addEventListener('keydown', handleQrKeydown);

    // Mantener el foco en el input oculto continuamente
    // (el usuario no debe poder escribir en ningún input visible)
    document.addEventListener('focusin', refocusHiddenInput);

    // Foco inicial
    hiddenInput.focus();
}

export function stopQrListener() {
    if (!_isListening) return;
    _isListening = false;

    const hiddenInput = document.getElementById('qr-wedge-input');
    if (hiddenInput) {
        hiddenInput.removeEventListener('keydown', handleQrKeydown);
    }
    document.removeEventListener('focusin', refocusHiddenInput);
}

function refocusHiddenInput(event) {
    // No redirigir el foco si el pin-gate o el config form están activos
    const globalOverlay = document.getElementById('global-overlay');
    if (globalOverlay && !globalOverlay.classList.contains('hidden')) return;

    const hiddenInput = document.getElementById('qr-wedge-input');
    if (hiddenInput && event.target !== hiddenInput) {
        hiddenInput.focus();
    }
}

let buffer = '';
let bufferTimer = null;

function handleQrKeydown(event) {
    if (event.key === 'Enter') {
        const code = buffer.trim();
        buffer = '';
        clearTimeout(bufferTimer);

        if (code.length > 0) {
            processQrCode(code);
        }
        return;
    }

    // Acumular caracteres en el buffer
    if (event.key.length === 1) {
        buffer += event.key;
    }

    // Timeout de seguridad: si pasan 200ms sin Enter, limpiar buffer
    // (previene contaminación por teclas accidentales del usuario)
    clearTimeout(bufferTimer);
    bufferTimer = setTimeout(() => { buffer = ''; }, 200);
}

async function processQrCode(code) {
    const sessionId = _sessionIdRef ? _sessionIdRef() : null;

    if (!sessionId) {
        // No hay sesión activa — ignorar silenciosamente
        return;
    }

    // Prevenir doble escaneo mientras se procesa
    if (document.getElementById('app')?.dataset.state === 'processing') return;

    UI.render('processing');

    try {
        const result = await _client.recordQr(sessionId, code);

        if (result.success) {
            const timeString = new Date().toLocaleTimeString('es-DO', {
                hour: '2-digit', minute: '2-digit', hour12: true
            });
            UI.render('success', {
                name:      result.student.full_name,
                photo_url: result.student.photo_url,
                time:      timeString,
                status:    result.status || 'Presente',
            });
        } else {
            UI.render('error', { message: result.message || 'Código no reconocido' });
        }
    } catch (e) {
        UI.render('error', { message: 'Error de comunicación' });
    }
}
```

El timeout de 200ms en el buffer es clave: un lector USB envía todos los caracteres de un código en ~20-50ms. Si pasan más de 200ms entre caracteres, es una pulsación manual del usuario, no un escaneo — el buffer se descarta.

---

### `api-client.js` — agregar `recordQr`

```javascript
// renderer/api-client.js — agregar al ApiClient existente

async recordQr(sessionId, qrCode) {
    const resp = await fetch(`${this.base}/record/qr`, {
        method: 'POST',
        headers: {
            ...this.headers,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            session_id: sessionId,
            qr_code:    qrCode,
        }),
    });

    if (resp.status === 401) {
        throw new TokenRevokedError();
    }

    return resp.json();
}
```

---

### `ui-states.js` — estado `qr_only`

Para el plan básico sin cámara, se agrega el estado `qr_only` al switch de `UI.render()`. Este estado reemplaza el `#scanner-view` del 70% con un panel simple centrado:

```javascript
// renderer/ui-states.js — agregar al switch de render()

case 'qr_only':
    processingOverlay.classList.add('opacity-0', 'pointer-events-none');
    errorOverlay.classList.add('opacity-0', 'pointer-events-none');
    globalOverlay.classList.add('hidden');
    scannerPrompt.classList.add('opacity-0');

    badgeDot.className = "w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse";
    badgeText.textContent = "Escáner QR Activo";

    // Reemplazar el área de cámara con panel QR minimalista
    const scannerView = document.getElementById('scanner-view');
    if (scannerView && !scannerView.dataset.qrMode) {
        scannerView.dataset.qrMode = 'true';
        // El video y canvas quedan ocultos — la cámara no está encendida
        document.getElementById('webcam').style.display = 'none';
        document.getElementById('output-canvas').style.display = 'none';

        // Panel QR minimalista centrado en el 70%
        const qrPanel = document.createElement('div');
        qrPanel.id = 'qr-only-panel';
        qrPanel.className = `
            absolute inset-0 flex flex-col items-center justify-center gap-6
            bg-[#0a0a0b]
        `;
        qrPanel.innerHTML = `
            <div class="w-32 h-32 rounded-3xl border-4 border-[#f78904]/30 flex items-center justify-center">
                <svg class="w-16 h-16 text-[#f78904]/60" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621
                           0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125
                           1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5Z
                           M3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621
                           0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125
                           1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5Z
                           M13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621
                           0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125
                           1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.75h.75v.75h-.75v-.75ZM16.75 6.75h.75v.75h-.75v-.75Z
                           M13.5 13.5h.75v.75H13.5v-.75ZM13.5 19.5h.75v.75H13.5v-.75Z
                           M16.5 16.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75Z
                           M19.5 19.5h.75v.75h-.75v-.75Z" />
                </svg>
            </div>
            <div class="text-center space-y-1">
                <p class="text-sm font-bold text-gray-300">Acerque el carnet al lector</p>
                <p class="text-xs text-gray-600">${data.school_name || 'Registro de Asistencia'}</p>
            </div>
        `;
        scannerView.appendChild(qrPanel);
    }
    break;
```

Y agrega también un método público `renderQrOnly` en el objeto `UI` para que `setup-screen.js` pueda invocarlo con el nombre de la escuela:

```javascript
// Dentro del objeto UI, fuera del switch
renderQrOnly(schoolName) {
    this.render('qr_only', { school_name: schoolName });
},
```

---

### Integración en `setup-screen.js`

```javascript
// renderer/setup-screen.js

import { ApiClient, TokenRevokedError  } from './api-client.js';
import { UI } from './ui-states.js';
import { initFaceDetector, detectLoop } from './camera.js';
import { showPinGate } from './pin-gate.js';
import { startQrListener, stopQrListener } from './qr-listener.js'; // ← nuevo

let currentSessionId = null;
let localStream = null;
let lastKnownSchoolName = "Politécnico Orvian";
let heartbeatInterval = null;
let kioskFeatures = { attendance_qr: false, attendance_facial: false };

// ... showConfigForm() sin cambios ...

async function init() {
    await UI.init(); // inicializar rutas de recursos

    const url = await window.orvianConfig.get('server_url');
    const token = await window.orvianConfig.get('kiosk_token');

    if (!token) {
        await showConfigForm();
        return;
    }

    const client = new ApiClient(url, token);

    try {
        // MediaPipe solo se inicializa si el plan puede necesitar facial
        // Se inicializa de forma especulativa — si el plan no lo tiene,
        // la cámara nunca se enciende pero el detector está listo por si cambia
        await initFaceDetector();

        const videoEl = document.getElementById('webcam');
        const canvasEl = document.getElementById('output-canvas');

        detectLoop(videoEl, canvasEl, async (activeVideoEl) => {
            if (!kioskFeatures.attendance_facial) return; // plan no lo tiene, ignorar
            UI.render('processing');
            const captureCanvas = document.createElement('canvas');
            captureCanvas.width = 640;
            captureCanvas.height = 480;
            const ctx = captureCanvas.getContext('2d');
            ctx.drawImage(activeVideoEl, 0, 0, 640, 480);

            captureCanvas.toBlob(async (blob) => {
                try {
                    const result = await client.recordFacial(currentSessionId || '0', blob);
                    if (result.success) {
                        const timeString = new Date().toLocaleTimeString('es-DO', {
                            hour: '2-digit', minute: '2-digit', hour12: true
                        });
                        UI.render('success', {
                            name:      result.student.full_name,
                            photo_url: result.student.photo_url,
                            time:      timeString,
                            status:    result.status || 'Presente',
                        });
                    } else {
                        UI.render('error', { message: result.message || "No identificado" });
                    }
                } catch (e) {
                    UI.render('error', { message: "Error de comunicación" });
                }
            }, 'image/jpeg', 0.85);
        });

        await evaluateKioskState(client);
        heartbeatInterval = setInterval(() => evaluateKioskState(client), 30000);

    } catch (error) {
        console.error("Fallo inicialización:", error);
        UI.render('error', { message: "Fallo al cargar sistema" });
    }

    // Botón de acceso técnico
    document.getElementById('tech-access-btn')?.addEventListener('click', () => {
        showPinGate(() => showConfigForm());
    });
}

async function activateKioskMode(client) {
    if (kioskFeatures.attendance_facial) {
        await turnOnCamera();
        startQrListener(client, () => currentSessionId);
    } else if (kioskFeatures.attendance_qr) {
        turnOffCamera();
        UI.renderQrOnly(lastKnownSchoolName);
        startQrListener(client, () => currentSessionId);
    } else {
        UI.render('no_session', { school_name: lastKnownSchoolName });
    }
}

function deactivateKioskMode() {
    stopQrListener();
    turnOffCamera();
    UI.render('no_session', { school_name: lastKnownSchoolName });
}

async function evaluateKioskState(client) {
    try {
        const status = await client.getStatus();
        if (status?.school_name) lastKnownSchoolName = status.school_name;
        if (status?.features) kioskFeatures = status.features;

        await window.orvianConfig.set('cached_pin_hash', status.pin_hash ?? null);

        if (status?.session_active) {
            currentSessionId = status.session_id;
            await activateKioskMode(client);
        } else {
            currentSessionId = null;
            deactivateKioskMode();
        }
    } catch (err) {
        if (err instanceof TokenRevokedError) {
            clearInterval(heartbeatInterval);
            stopQrListener();
            showPinGate(() => showConfigForm());
        } else {
            console.warn('Error de conexión:', err.message);
        }
    }
}

// turnOnCamera() y turnOffCamera() sin cambios

init();
```

---

## Archivos nuevos y modificados

### En `orvian` (Laravel)

| Archivo | Acción | Nota |
| :--- | :--- | :--- |
| `app/Http/Controllers/Api/Kiosk/KioskStatusController.php` | Modificar | Agregar `features.attendance_qr` y `features.attendance_facial` al JSON |

### En `orvian-kiosk-electron`

| Archivo | Acción | Nota |
| :--- | :--- | :--- |
| `renderer/qr-listener.js` | Crear | Captura de lector USB wedge, buffer con timeout, llama a `recordQr` |
| `renderer/api-client.js` | Modificar | Agregar método `recordQr(sessionId, qrCode)` |
| `renderer/ui-states.js` | Modificar | Agregar estado `qr_only` y método `renderQrOnly()` |
| `renderer/setup-screen.js` | Modificar | Variables `kioskFeatures`, funciones `activateKioskMode()` y `deactivateKioskMode()`, import de `qr-listener.js` |

---

## Notas de prueba con lector de barras

Para probar el flujo antes de tener lector QR:

1. **Generar códigos de barras** con el mismo string que tiene el campo `qr_code` de un estudiante en la BD (formato `ORV-2024-XXXXX`). Cualquier generador online de Code 128 sirve.
2. **Conectar el lector de barras** por USB. No requiere drivers — Windows lo detecta como teclado HID.
3. **El flujo es idéntico** al QR: el lector escanea, el buffer de `qr-listener.js` acumula el string, recibe el `Enter`, y llama a `client.recordQr()`.
4. Si el código leído coincide con un `qr_code` en la BD de la escuela autenticada, el controlador responde con éxito.

**Para verificar que el lector wedge funciona antes de integrar con la app:** abre el Bloc de Notas en Windows y escanea un código — debe aparecer el string impreso. Si aparece, el lector está en modo wedge correcto.

---

## Nota sobre el CSP de `index.html`

El QR listener no agrega peticiones a nuevos dominios, por lo que el `Content-Security-Policy` existente no necesita modificación. Las peticiones de `recordQr` van al mismo `connect-src` ya declarado (`http://localhost:80` o `https://orvian.com.do`).

---

## Fase 3 — Ventanas Horarias Configurables por Tanda

**Rama:** `feature/v0.9.0-shift-windows`

### Contexto

La lógica actual en `PlantelAttendanceService::determineStatus()` usa `SchoolShift::start_time` + un margen fijo de 15 minutos para determinar si un estudiante llegó tarde. Esto no contempla:
- Ventanas de registro pre-apertura (entrada temprana).
- Cierre automático de registro (nadie puede entrar después de X hora).

### Migración de Base de Datos

```php
// database/migrations/xxxx_add_attendance_windows_to_school_shifts.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('school_shifts', function (Blueprint $table) {
            // Minutos después del start_time que se considera "Tardanza"
            $table->unsignedSmallInteger('late_threshold_minutes')->default(0)->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_shifts', function (Blueprint $table) {
            $table->dropColumn([
                'late_threshold_minutes',
            ]);
        });
    }
};
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

**Agregar label**

Colocar la nueva ruta en `config/modules.php`

```php
['label' => 'Configuración Horaria',            'route' => 'app.attendance.shift-windows'],
```

### Limitar apertura de tandas

Ahora que se colca el cambio de hora es necesario limitar en la vista de sesion abrir las seciones a menos que falte 1 hora y 30 minutos para que haya un tiempo de confugracion. 

**Agregar asesores en el modelo**

```php

    /**
     * Determina si la tanda ya puede ser abierta con una ventana de tiempo estricta.
     */
    public function getCanBeOpenedAttribute(): bool
    {
        $now = Carbon::now();
        
        // Ventana de apertura: 1 hora y media antes del start_time
        $openingWindowStart = Carbon::today()
            ->setTime($this->start_time->hour, $this->start_time->minute)
            ->subMinutes(90);

        // Límite de cierre de ventana: No permitir abrir si ya pasó la hora de entrada 
        // (o puedes cambiarlo a $this->end_time si permites aperturas extremadamente tardías)
        $openingWindowEnd = Carbon::today()
            ->setTime($this->start_time->hour, $this->start_time->minute);

        // El botón solo se activa si la hora actual cae EXACTAMENTE dentro del rango del día de hoy
        return $now->between($openingWindowStart, $openingWindowEnd);
    }

    /**
     * Devuelve un string legible con el estado o tiempo restante para la apertura.
     */
    public function getTimeUntilOpeningAttribute(): string
    {
        $now = Carbon::now();
        
        $openingWindowStart = Carbon::today()
            ->setTime($this->start_time->hour, $this->start_time->minute)
            ->subMinutes(90);

        $openingWindowEnd = Carbon::today()
            ->setTime($this->start_time->hour, $this->start_time->minute);

        // Caso 1: Aún no es hora de abrir (Falta tiempo)
        if ($now->lessThan($openingWindowStart)) {
            return 'Disponible en ' . $now->shortAbsoluteDiffForHumans($openingWindowStart);
        }

        // Caso 2: Ya pasó la hora de entrada reglamentaria para iniciar la sesión
        if ($now->greaterThan($openingWindowEnd)) {
            return 'Horario de apertura vencido para el día de hoy.';
        }

        return '';
    }
```

Usar esos asesores en el livewire de `app/Livewire/App/Attendance/AttendanceSessionManager.php` y usar el boton de `ui.buttond` más un tooltip para desactivar el boton en `resources/views/livewire/app/attendance/session-manager.blade.php`.

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

# Fase 5 — Asistencia de Plantel y Excusas (alcance reducido para el piloto)

> Decisión: la asistencia de aula queda **desactivada por completo** para el piloto — ni obligatoria ni opcional. La columna vertebral vuelve a ser exclusivamente Plantel (entradas, vía kiosko), con un dominio de excusas simplificado atado solo a Plantel. Nada de salidas tempranas, ciclo de regreso, ticket QR, actividad institucional, ni módulo Orientación en esta fase.

## Por qué se reduce el alcance

El módulo de aula introducía criterio humano no programable (pasilleo, tolerancia por maestro) y dependía de un segundo registro paralelo al Registro Anecdótico oficial sin producir ningún documento que lo justificara. Las salidas tempranas con ciclo de regreso agregaban una segunda capa de estado (afuera/adentro) sobre un proceso que, sin el ticket físico digitalizado, no tiene forma confiable de capturarse. Plantel — entrada automatizada por kiosko, con excusa simple — es la única parte del dominio que es 100% mecánica, sin ambigüedad de criterio, y es la que el director realmente pidió.

## Estructura de Ramas

```
release/v0.9.0
└── feature/v0.9.0-plantel-excuses-fase5    (única rama — ya no hay Sub-fase B de aula)
```

## Tabla de Requisitos

| ID | Descripción | Estado |
| :-- | :-- | :-- |
| REQ-05.7 | Dominio de excusas simplificado, atado solo a Plantel | Completado |
| REQ-05.12 | `AttendanceAudit` — dos transiciones sobre Plantel, restringidas a hoy | Completado |
| REQ-05.13 | Ocultar módulo de Aula (pase de lista, dashboard, reportes, historial) | Completado|

### Fuera de alcance del piloto — no se implementa, no se revierte porque no se construyó

| Requisito anterior | Estado |
| :-- | :-- |
| Actividad Institucional (antes REQ-05.8/05.9) | Descartado — dependía de aula |
| Base del módulo Orientación (antes REQ-05.10) | Descartado — sin aula ni ciclo de salidas no hay caso de uso que lo requiera ahora |
| Ticket QR de salida/regreso, modo portería en Electron (antes REQ-05.11) | Descartado — Plantel no maneja salidas |
| Interacción tipo tarjeta / swipe UI (antes REQ-05.9 de Sub-fase B) | Descartado — nunca se llegó a construir, no hay nada que revertir |
| `EarlyDepartureControl` | Descartado — si llegó a scaffolearse algún archivo, eliminar; si solo quedó documentado, no requiere acción |

### Ya completado, se deja intacto y oculto (no se revierte, no se toca)

| Requisito | Motivo para no tocarlo |
| :-- | :-- |
| REQ-05.1 — Gate de sesión cerrada por tanda | Vive dentro de `ClassroomAttendanceLive`, queda oculto junto con el resto de aula (REQ-05.13) |
| REQ-05.3 — Estado inicial "Sin Marcar" | Idem |
| REQ-05.5 — Trazabilidad del Modo Sustituto | Idem |
| REQ-05.6 — Fix de validación cruzada | Idem — sin registros de aula que cruzar, queda inerte pero no daña nada estando oculto |

---

## REQ-05.7 — Dominio de Excusas Simplificado (versión final, solo Plantel)

### Regla central (sin cambios)

Una excusa nunca modifica un registro de asistencia ya creado, sin importar qué tan en el pasado esté. `markAbsences()` consulta la excusa confirmada en el momento de crear cada registro — si no existía cuando el registro se creó, se queda como estaba, para siempre.

### Tipos — dos motivos, sin más

```php
// AttendanceExcuse.php
public const TYPE_MEDICAL  = 'medical';
public const TYPE_PERSONAL = 'personal';

public const TYPE_LABELS = [
    self::TYPE_MEDICAL  => 'Motivo Médico',
    self::TYPE_PERSONAL => 'Motivo Personal',
];
```

Ambos tipos se crean bajo el mismo permiso `attendance.manage_excuses` — sin split de autoridad entre Administración y Orientación, sin rol nuevo.

### Terminología y máquina de estados — Confirmación/Cancelación

```php
public const STATUS_PENDING   = 'pending';
public const STATUS_CONFIRMED = 'confirmed'; // antes: approved
public const STATUS_CANCELLED = 'cancelled'; // antes: rejected
```

Dos transiciones válidas únicamente:

- `pending → confirmed`: única forma de avanzar, con modal de resumen (estudiante + foto + cédula/sección + tipo + fechas) como segundo factor antes de confirmar.
- `confirmed → cancelled`: exclusiva para el error detectado **después** de confirmar (ej. excusa confirmada para el estudiante equivocado). No existe `pending → cancelled` — una pendiente mal hecha se corrige editando en `ExcuseForm`, no se cancela.

Cancelar una excusa confirmada no revierte ningún `plantel_attendance_record` ya creado — solo la saca de las consultas futuras (`markAbsences()`, `getActivelyExcusedStudentIds()`). Un registro que ya nació `excusado` mientras la excusa estaba vigente se corrige, si hace falta, por Audit — no por cancelar la excusa.

### Validaciones — un solo punto de verdad, en el momento correcto (ya corregido)

```php
// ExcuseService.php

/** Se evalúa en cada guardado (crear Y editar) — depende solo del propio valor. */
public function canCreateForDate(Carbon $dateStart): bool
{
    return $dateStart->greaterThanOrEqualTo(today());
}

/**
 * Se evalúa EXCLUSIVAMENTE al confirmar, nunca al crear ni al editar —
 * es la única forma de que no quede obsoleta por ediciones posteriores.
 */
public function validateForConfirmation(AttendanceExcuse $excuse): void
{
    if ($this->hasOverlappingConfirmedExcuse(
        $excuse->student_id, $excuse->date_start, $excuse->date_end, excludeId: $excuse->id
    )) {
        throw new ExcuseValidationException('Ya existe una excusa confirmada que se traslapa con este rango.');
    }
}

public function hasOverlappingConfirmedExcuse(int $studentId, Carbon $dateStart, Carbon $dateEnd, ?int $excludeId = null): bool
{
    return AttendanceExcuse::where('student_id', $studentId)
        ->where('status', AttendanceExcuse::STATUS_CONFIRMED)
        ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
        ->where('date_start', '<=', $dateEnd->toDateString())
        ->where('date_end', '>=', $dateStart->toDateString())
        ->exists();
}
```

> **Se elimina `canCreatePersonalExcuse()` (tope de 2 días consecutivos).** Era una regla de autoridad que dependía de tener a quién escalar el tercer caso (Orientación). Sin ese rol, bloquear sin nadie que evalúe el bloqueo genera más fricción que la que resuelve. Queda solo el traslape, que es integridad de datos, no política. Si el piloto demuestra que hace falta un control de frecuencia, se agrega con evidencia real, no antes.

`EarlyDepartureControl` no existe en este alcance — no hay excepción que documentar porque no hay salidas que registrar.

### Vistas

- `excuse-index.blade.php`: listado con foto, sección junto al nombre, badges "Pendiente"/"Confirmado"/"Cancelado".
- `ExcuseForm.php` / `.blade.php`: vista separada (mismo patrón de `StudentForm`), editable solo mientras `status === PENDING`, guard también server-side. Personal: solo estudiante editable (fecha = hoy, fija). Médica: estudiante y rango de fechas.

### Aviso informativo — excusa multi-día activa que se presenta (sin cambios, ya diseñado)

```php
// ExcuseService.php
public function getMultiDayExcuseStillPendingForStudent(int $studentId, Carbon $date): ?AttendanceExcuse
{
    return AttendanceExcuse::where('student_id', $studentId)
        ->where('status', AttendanceExcuse::STATUS_CONFIRMED)
        ->where('date_start', '<=', $date->toDateString())
        ->where('date_end', '>', $date->toDateString())
        ->first();
}
```

Se anota en `metadata` del registro de Plantel al momento de crearse — informativo, no cambia ningún status. Visible desde el listado de Plantel del día; no requiere panel de Hub dedicado para el piloto.

---

## REQ-05.12 — `AttendanceAudit` (Plantel, sin dependencia de aula)

Dos transiciones, ambas restringidas a `today()`:

1. **`ausente → tardanza`**: estudiante marcado ausente sin excusa que llega tarde sin haber avisado. Modal de confirmación.
2. **`excusado → presente`**: sesión cerró antes de que el kiosko capturara la llegada de un estudiante con excusa confirmada. Modal de confirmación + foto.

```php
Schema::table('plantel_attendance_records', function (Blueprint $table) {
    $table->foreignId('corrected_by_user_id')->nullable()->constrained('users');
    $table->timestamp('corrected_at')->nullable();
});
```

```php
DB::transaction(function () use ($record) {
    $record->update([
        'status'               => PlantelAttendanceRecord::STATUS_LATE, // o STATUS_PRESENT, según transición
        'corrected_by_user_id' => Auth::id(),
        'corrected_at'         => now(),
    ]);
    $this->session->decrement('total_absent');   // o total_excused, según transición
    $this->session->increment('total_late');     // o total_present, según transición
});
```

Cualquier sesión distinta de hoy se renderiza en modo solo lectura, sin excepción — misma justificación de siempre: no reescribir el pasado.

**Migración pendiente, aún no aplicada.**

---

## REQ-05.13 — Ocultar Módulo de Aula

```php
// config/modules.php
'asistencia' => [
    // ...
    'sub_links' => [
        // Pase de Lista, Dashboard (si depende de aula), Reportes de aula,
        // Historial de aula → visible: false
        // Sesión del Día, Excusas, Auditoría, Configuración Horaria → se quedan visibles
    ],
],
```

Mismo patrón usado en v0.4.1 para módulos incompletos. Las rutas ocultas devuelven 404 si se accede directamente — no se borra código, se apaga el acceso. Reversible sin reescribir nada si en algún momento se retoma aula.

---

## Archivos a Crear / Modificar

| Archivo | Acción | REQ |
| :--- | :--- | :--- |
| `app/Models/Tenant/AttendanceExcuse.php` | Enum `medical`/`personal`; status `confirmed`/`cancelled` | 05.7 |
| `database/migrations/xxxx_update_attendance_excuses_types_and_status.php` | Migración de tipo + datos de status (`approved`→`confirmed`, `rejected`→`cancelled`) | 05.7 |
| `app/Services/Attendance/ExcuseService.php` | `canCreateForDate()`, `validateForConfirmation()`, `hasOverlappingConfirmedExcuse()`; **eliminar** `canCreatePersonalExcuse()` si ya se agregó | 05.7 |
| `app/Livewire/App/Attendance/ExcuseForm.php` | Crear/ajustar — guard de edición por `status`, validación solo al confirmar | 05.7 |
| `resources/views/livewire/app/attendance/excuse-form.blade.php` | Crear/ajustar | 05.7 |
| `resources/views/livewire/app/attendance/excuse-index.blade.php` | Listado, foto, cédula/sección, badges nuevos | 05.7 |
| `app/Livewire/App/Attendance/EarlyDepartureControl.php` | Eliminar si llegó a crearse | — |
| `app/Models/Tenant/InstitutionalActivity.php` + migración | Eliminar si llegó a crearse | — |
| `database/console/commands/SeedDemoSchoolData.php` | Actualizar Paso 5: status `approved` → `confirmed` | 05.7 |
| `app/Livewire/App/Attendance/AttendanceAudit.php` | Dos transiciones, restricción `today()` | 05.12 |
| `database/migrations/xxxx_add_correction_fields_to_plantel_attendance_records.php` | `corrected_by_user_id`, `corrected_at` — pendiente | 05.12 |
| `config/modules.php` | `visible: false` en sublinks de aula | 05.13 |

## Git

Revertir únicamente commits específicos de: enum `institutional_activity`, cualquier scaffolding de `EarlyDepartureControl`/`InstitutionalActivity`/rol `Orientadora` si llegaron a crearse. El resto de v0.9.0, y todo lo ya completado de aula (REQ-05.1/05.3/05.5/05.6), queda intacto y oculto — no se toca.

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
