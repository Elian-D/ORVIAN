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
| `x-ui.button` con `wire:loading.class` global | v0.3.0 | ⚠️ Corregir — dispara en cualquier acción Livewire (REQ-11.1) |
| `x-ui.toasts` con `toastManager` Alpine | v0.3.0 | ⚠️ Modernizar — stack, swipe-to-dismiss y refinamiento (REQ-10) |
| Páginas de error de Laravel (genéricas) | v0.1.0 | ⚠️ Crear vistas personalizadas 403, 404 y 500 (REQ-09) |
| Navbar de módulos en mobile | v0.8.0 | ✅ Funcional, ajustes visuales menores (REQ-07) |
| Elementos `x-show`/`x-data` sin `x-cloak` en layouts raíz | v0.3.0+ | ⚠️ Corregir FOUC — la regla CSS ya existe, falta el atributo (REQ-11.2) |
| `layouts/app-module.blade.php` — casi idéntico a `layouts/app.blade.php` desde que REQ-07.1 quitó el navbar | v0.8.0 | 🗑️ **ELIMINAR** — unificar en `layouts/app.blade.php` (REQ-11.3) |

---

## Tabla de Requerimientos

| ID | Fase | Área | Descripción | Prioridad | Estado |
| :-- | :-- | :-- | :-- | :-- | :-- |
| REQ-01 | 1 | Asistencia Biométrica | API Gateway para el Kiosko: rutas `/api/v1/kiosk/` protegidas por Sanctum | Alta | Completado |
| REQ-02 | 2 | Asistencia Biométrica | Arquitectura de `orvian-kiosk-electron`: app de escritorio con Electron + MediaPipe Tasks-Vision for Web (WASM local), sin lógica de QR | Alta | Completado |
| REQ-03 | 3 | Configuración | Ventanas horarias configurables por tanda (entrada, tardanza, cierre) | Alta | Completado|
| REQ-04 | 4 | UI / Componentes | Selector Universal de Cursos — componente Livewire reutilizable | Alta | A FUTURO |
| REQ-05 | 5 | Asistencia Aula | Rediseño completo del pase de lista con gestos de deslizamiento | Alta | Completado |
| REQ-06 | 6 | Mobile | Planificación de app móvil Flutter para tutores (sin código) | Media | Planificación  |
| REQ-07 | 7 | UX / Navegación | Rediseño de navegación de escuela: Sidebar en vez de navbar horizontal, breadcrumbs globales, fin de `config/modules.php` inyectado por Livewire, buscador global de rutas | Alta | Completado |
| REQ-07.1 | 7 | UX / Navegación | Migrar layout de escuela de navbar horizontal a Sidebar (mismo patrón que admin) | Alta | Completado |
| REQ-07.2 | 7 | UX / Navegación | Breadcrumbs globales en `layouts.app-module` (no dentro de `module-toolbar`) | Media | Completado |
| REQ-07.3 | 7 | Arquitectura | Eliminar inyección de `config('modules.*')` vía `->layout()` en los 32 Livewire de escuela | Media | Completado |
| REQ-07.4 | 7 | UX / Navegación | Buscador global de rutas — índice desde `->defaults('navigationSearch', ...)` en `routes/app/*.php` (no `config/modules.php`), cache 24h, filtrado por permisos por usuario | Alta | Hecho |
| REQ-07.5 | 7 | UX / Navegación | Contenido del dashboard unificado (reemplaza navbar-mobile original; evitar "un dashboard por módulo") | Media | Sugerencias documentadas — sin spec cerrada |
| REQ-07.6 | 7 | UI | Logo dinámico por escuela en el Sidebar (`application-logo.blade.php`) | Media | Completado |
| REQ-07.7 | 7 | Arquitectura | Modal de perfil (`ProfileModal`) → ruta dedicada `app.profile` (ocultar, no eliminar) | Media | Completado |
| REQ-07.8 | 7 | UX | Deprecar Login v1 — un solo login (azul), quitar selector y cookie (ocultar, no eliminar) | Media | Completado |
| REQ-07.9 | 7 | UX | Preferencia de Sidebar colapsado: de checkbox en Perfil a persistencia automática en `localStorage` | Media | Completado |
| REQ-07.10 | 7 | UI | Íconos de módulo propios (`assets/icons/modules/*.svg`) en `sidebar.item`/`sidebar.dropdown` en vez de Heroicons | Baja | Completado |
| REQ-07.11 | 7 | UI | Limpieza de `navbar/layout.blade.php`: quitar tooltip de sidebar y botón fullscreen; buscador con `x-ui.forms.*` + `x-modal` | Media | Completado |
| REQ-07.12 | 7 | UX | Sidebar colapsado en desktop: hover como overlay (sin empujar `<main>`, sin oscurecer fondo) | Alta | Completado |
| REQ-07.13 | 7 | Arquitectura | Eliminación completa del sistema de Status de Usuario (online/away/busy/offline) | Media | Completado |
| REQ-07.14 | 7 | UX / Navegación | Deprecar `module-toolbar` (tapaba el breadcrumb, sticky bajo el navbar) — reemplazado por `x-ui.page-header` extendido con menú de acciones secundarias (dropdown desktop / bottom sheet mobile) | Alta | Hecho |
| REQ-08 | 8 | Arquitectura | Evaluación del dominio de tutores y padres (sin código) | Media | Análisis |
| REQ-09 | 9 | UI | Páginas de error personalizadas (403, 404, 500) | Baja | Completado |
| REQ-10 | 10 | UI Kit | Toasts acumulativos, swipe-to-dismiss y refinamiento visual | Media | Pendiente |
| REQ-11 | 11 | UI Kit / Arquitectura | Correcciones de UI Kit: `wire:loading` global, FOUC de Alpine, unificación de layouts | Alta | Completado |
| REQ-11.1 | 11 | UI Kit | Corrección de `wire:loading` global en `x-ui.button` | Alta | Completado |
| REQ-11.2 | 11 | UI Kit | Eliminar FOUC de Alpine.js mediante `x-cloak` | Alta | Completado |
| REQ-11.3 | 11 | Arquitectura | Unificar `layouts.app-module` y `components.admin` en `layouts.app` | Media | Completado |

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

# ORVIAN — Fase 6: Notificaciones a Tutores
### Análisis de alternativas (WhatsApp / Email / App Móvil) — v0.9.0

**Estado:** Sin código. Análisis y decisión de arquitectura para versiones futuras.
**Fecha del análisis:** 12 de julio, 2026
**Contexto:** Reemplaza el análisis original de Fase 7/8 (WhatsApp vía Evolution API, ya deprecado y removido). ORVIAN pasó de ser un proyecto final escolar a un producto con dos pilotos activos (tu colegio y PREPARA), lo que cambia el cálculo de qué vale la pena construir ahora.

**Objetivo de la fase:** Documentar y decidir el canal (o combinación de canales) para notificar a los tutores sobre asistencia, tardanzas y ausencias, sin repetir el error de meter esto en el mismo ciclo que las urgencias del piloto.

---

## 0. Restricción de diseño ya acordada

No todas las alertas van por todos los canales. Independientemente de la alternativa elegida, el volumen se reduce desde el diseño:

- **Push/WhatsApp/Email de alta frecuencia** (presente, tardanza, ausente en aula) → quedan como ya están definidas en la tabla original, pero condicionadas al canal más barato disponible (push).
- **WhatsApp/Email reservados para casos importantes**: inasistencias acumuladas, tardanzas múltiples. Esto no es solo una decisión de UX — es lo que hace viable el costo de WhatsApp, que se cobra por conversación/mensaje entregado.

---

## 1. Alternativa A — WhatsApp vía API de Meta (intermediario propio)

### 1.1 Lo que corrigió la investigación sobre automatizado.vip

Tu conclusión es correcta: **automatizado.vip no revende consumo, revende acceso e implementación.** Esto corresponde a un modelo específico dentro del ecosistema de partners de Meta, y vale la pena que quede documentado porque cambia lo que "ser intermediario" significa para ORVIAN:

| Rol | Qué hace | Cómo cobra | Riesgo/implicación |
|---|---|---|---|
| **Solution Partner** (antes "BSP") | Mantiene una línea de crédito con Meta, paga el consumo por adelantado y factura al cliente final | Markup típico de 5–20% sobre la tarifa de Meta, a veces + fee mensual de plataforma | El cliente nunca ve la tarifa real de Meta; el partner asume el riesgo de cobro |
| **Tech Provider / Tech Partner** | Construye software sobre la API de Meta (Embedded Signup), pero **no** mantiene línea de crédito | El cliente final agrega su propio método de pago directo en WhatsApp Manager; Meta le cobra directo | Cero riesgo financiero para el intermediario; cero markup que justificar |

Esto es exactamente lo que automatizado.vip describe cuando dice que tú pagas el consumo directo a Meta: son un **Tech Provider**, no un Solution Partner con línea de crédito. Cobran por implementación, capacitación y soporte — no por el mensaje.

### 1.2 Qué significa esto para ORVIAN

Si la idea es que ORVIAN sea el intermediario técnico para colegios, el modelo **Tech Provider** es el que calza con lo que ya intuías (rapidez de integración, sin exponer a ORVIAN al riesgo de facturación):

- ORVIAN construye la integración vía **Embedded Signup** de Meta dentro del onboarding del colegio.
- Cada colegio conecta **su propio número y método de pago** en WhatsApp Manager.
- Meta cobra directo al colegio por conversación entregada.
- ORVIAN no maneja dinero de terceros, no necesita línea de crédito, y no compite en precio con BSPs establecidos — compite en velocidad de integración y en que ya vive dentro del sistema que el colegio usa a diario.

Esto evita el problema real: si ORVIAN intentara operar como Solution Partner (línea de crédito + markup), se convierte en un negocio financiero paralelo al de software escolar, con obligaciones de cumplimiento distintas.

### 1.3 Costos reales (referencia, sujeto a cambio por parte de Meta)

- Desde julio 2025, Meta cobra **por mensaje de plantilla entregado**, no por ventana de conversación de 24h (ese modelo se retiró).
- Las **conversaciones de servicio** (iniciadas por el usuario) son gratuitas e ilimitadas desde noviembre 2024 — relevante si en el futuro los tutores pueden responder al colegio.
- Existe un límite de frecuencia: aprox. **2 mensajes de plantilla de marketing por usuario cada 24h**, a través de todos los negocios combinados (no aplica igual a utility/authentication).
- Tarifas varían por país destinatario (no por ubicación del colegio) y por categoría (marketing, utility, authentication). Para RD probablemente aplica la tarifa "Rest of World".
- Como Tech Provider sin markup, el colegio paga exactamente la tarifa de Meta — sin capa adicional de ORVIAN.

### 1.4 Checklist — Alternativa WhatsApp

- [ ] Confirmar tarifa "Rest of World" o específica de RD en el rate card oficial de Meta (verificar directo en Meta, no en blogs de terceros)
- [ ] Evaluar registrar ORVIAN como **Tech Provider** de Meta (no Solution Partner) — investigar requisitos de aprobación
- [ ] Diseñar el flujo de Embedded Signup dentro del onboarding de colegio (quién conecta el número: el colegio o ORVIAN en su nombre)
- [ ] Definir qué eventos justifican plantilla de WhatsApp (ya acotado a inasistencias acumuladas y tardanzas múltiples — mantener esa restricción)
- [ ] Someter plantillas de mensajes a aprobación de Meta con antelación (el proceso de aprobación de templates puede tardar)
- [ ] Documentar que el consumo se factura directo al colegio — esto debe quedar claro en el contrato/onboarding del piloto, no asumido
- [ ] Marcar como **fuera de alcance para septiembre**: esta alternativa requiere aprobación de Meta como negocio, no solo configuración técnica — probablemente no cierra a tiempo para el piloto

---

## 2. Alternativa B — Email (Amazon SES u otro)

### 2.1 Amazon SES

- Precio: **~$0.10 por 1,000 emails**, el más barato del mercado con margen amplio.
- Integra con Laravel vía el transporte de **Symfony Mailer** (Laravel 9+ usa Symfony Mailer internamente) — no requiere paquete especial, solo configuración del driver `ses` y credenciales AWS.
- Contras reales para un equipo de un solo desarrollador: cuentas nuevas empiezan en **modo sandbox** (solo se puede enviar a direcciones verificadas manualmente) hasta que AWS aprueba salida a producción (24–48h típico). Hay que armar manejo de *bounces* y *complaints* (vía SNS) o el envío se puede suspender automáticamente si la tasa de rebote supera ~5-10%.
- No trae dashboard, plantillas ni panel de reportes — todo eso se construye aparte.

### 2.2 Alternativas más simples para el volumen de un piloto

Dado que el volumen de un piloto de dos colegios es bajo, el ahorro de SES (fracciones de centavo) probablemente no compensa el tiempo de configurar sandbox exit + SNS + manejo de rebotes. Opciones con integración más directa a Laravel:

| Proveedor | Free tier | Precio pagado (referencia) | Nota |
|---|---|---|---|
| **Brevo** | 9,000 emails/mes (300/día), sin tarjeta | — | Sin modo sandbox, SPF/DKIM automático, swap directo vía SMTP relay |
| **Resend** | 3,000 emails/mes | $20/mes por 50k | SDK oficial de Laravel/PHP, muy buena experiencia de desarrollador |
| **Postmark** | 100/mes (solo pruebas) | $15/mes por 10,000 | Mejor entregabilidad, sin sandbox |
| **Amazon SES** | — (dejó de tener free tier ligado a EC2) | ~$0.10/1,000 | Más barato a escala, más trabajo de configuración |

Para el volumen del piloto (dos colegios, notificaciones acotadas a casos importantes), **Brevo o Resend cubren el uso sin costo** y sin la fricción de sandbox de AWS. SES se vuelve la opción correcta solo si el volumen crece de forma sostenida (varios colegios, miles de notificaciones/mes) — momento en el que además ya se justifica invertir el tiempo en el manejo de bounces.

### 2.3 Checklist — Alternativa Email

- [ ] Decidir proveedor para el piloto: recomendación es **Brevo o Resend** por el free tier sin fricción, dejando SES como candidato post-pilot si el volumen lo justifica
- [ ] Confirmar si el paquete de correo actual de Laravel ya está abstraído detrás de una interfaz propia (para poder cambiar de proveedor sin tocar lógica de negocio)
- [ ] Verificar dominio propio de ORVIAN o del colegio para SPF/DKIM (afecta entregabilidad, sobre todo si los tutores usan Gmail/Hotmail)
- [ ] Definir qué eventos disparan email (mismo criterio que WhatsApp: casos importantes, no cada marca de asistencia)
- [ ] Nota de contexto para el documento: el correo tiene menor uso diario en RD, pero mayor tasa de revisión en usuarios bancarizados — válido como canal secundario, no primario

---

## 3. Alternativa C — App Móvil (Flutter + FCM)

### 3.1 Google Play — restricciones confirmadas

- **Cuenta de desarrollador:** USD $25, pago único (no anual).
- **Requisito de testing cerrado:** aplica a **cuentas personales creadas después del 13 de noviembre de 2023**. Se necesita un mínimo de **12 testers** con opt-in activo durante **14 días consecutivos** antes de poder solicitar acceso a producción (bajó de 20 a 12 testers en diciembre 2024).
  - "Consecutivos" es estricto: si el conteo de testers activos cae por debajo de 12 en algún punto, el conteo se puede reiniciar.
  - Un tester que desinstala sigue contando técnicamente, pero rara vez vuelve a generar actividad — Google evalúa "engagement" real, no solo el número.
- **Dato importante que cambia el cálculo:** las **cuentas de organización** (registradas como entidad legal) están **exentas** de este requisito de 12 testers/14 días y pueden publicar directo a producción. Si ORVIAN se registra como cuenta de organización (no personal), este obstáculo desaparece.
  - Vale la pena confirmar el proceso y tiempos de verificación de cuenta de organización en Play Console antes de descartarlo por complejidad.
- Con el plan piloto (colegio + PREPARA), conseguir 12 testers activos 14 días consecutivos es factible si se recluta con intención (personal administrativo, algunos tutores, tu familiar docente) — pero es una tarea de gestión, no solo técnica: hay que mantenerlos activos, no solo instalados.
- **Requisito adicional no mencionado en tu mensaje:** Google está desplegando un requisito de **verificación de identidad de desarrollador**, con aplicación obligatoria empezando el 30 de septiembre de 2026 en algunos países (Brasil, Indonesia, Singapur, Tailandia) y expansión global después. Es un requisito separado del testing (confirma quién eres, no si la app funciona) — probablemente no bloquea el piloto de septiembre, pero conviene tenerlo en el radar para 2027.

### 3.2 Apple App Store — restricciones confirmadas y una matizada

- **Cuenta de desarrollador:** USD $99/año (no pago único, a diferencia de Google).
- **Requiere Mac + Xcode** en el flujo tradicional: no hay forma de compilar y subir un binario iOS sin pasar por herramientas de Apple en algún punto.
- **Matiz importante:** no necesariamente requiere que **tú** poseas un Mac. Existen servicios de build en la nube que corren Xcode en Macs remotas y suben el binario por ti — la app sigue compilándose "en un Mac", pero no en uno tuyo. Para Flutter, las opciones más usadas son:
  - **Codemagic** (tiene soporte nativo de Flutter, plan gratuito limitado por minutos de build/mes)
  - **GitHub Actions con runners macOS** (de pago por minuto, pero predecible)
  - Servicios equivalentes a EAS de Expo, pero EAS en sí es específico de Expo/React Native, no aplica directo a Flutter.
- Desde el 28 de abril de 2026, todo build subido a App Store Connect debe compilarse con **Xcode 26 o superior / SDK de iOS 26 o superior** — esto es una política anual de Apple, no algo que afecte solo a ORVIAN, pero hay que construir con herramientas actualizadas desde el inicio.
- Apple aprueba ~90% de las apps en 24-48h, pero apps que tocan datos de menores (que es exactamente el caso de ORVIAN — información de estudiantes) suelen recibir revisión más estricta en cuanto a privacidad. Esto no es un bloqueo, pero sí algo a prever en el tiempo de revisión.

### 3.3 Restricciones que tú no mencionaste y vale la pena tener en el radar

- **Política de datos de menores** de ambas tiendas (Google Play Families Policy / Apple Kids Category si aplica, o al menos declaración de manejo de datos de estudiantes) — ORVIAN maneja datos de estudiantes, así que aunque la app sea para tutores (adultos), probablemente haya que declarar en el formulario de privacidad que la app procesa datos vinculados a menores.
- **Costo de firma/certificados**: no hay costo adicional más allá de las cuentas de desarrollador, pero si se usa un servicio de build en la nube (Codemagic, etc.), ese servicio puede tener costo mensual una vez se supera el tier gratuito.
- **FCM en sí es gratuito** sin límite de notificaciones — el costo real de esta alternativa está casi todo concentrado en las cuentas de desarrollador y el tiempo de cumplir los requisitos de testing, no en la infraestructura de mensajería.

### 3.4 Checklist — Alternativa App Móvil

- [ ] Decidir si la cuenta de Google Play se registra como **personal o de organización** — si es viable como organización, se evita el requisito de 12 testers/14 días
- [ ] Si queda como cuenta personal: reclutar y comprometer a 12+ testers reales (no solo instalar, usar la app activamente) desde antes del 20 de este mes, para que los 14 días consecutivos corran en paralelo al desarrollo
- [ ] Evaluar Codemagic (o alternativa) para build de iOS sin necesidad de Mac propio — revisar límites del tier gratuito
- [ ] Presupuestar: $25 (Google, único) + $99/año (Apple) + posible costo de servicio de build en la nube más allá del free tier
- [ ] Confirmar declaración de manejo de datos de menores en ambas tiendas antes de enviar a revisión
- [ ] Construir con Xcode 26 / SDK iOS 26+ desde el inicio del proyecto Flutter para no tener que migrar después
- [ ] Definir alcance mínimo de la app para el piloto: probablemente solo lectura de notificaciones, sin funciones administrativas

---

## 4. Otras alternativas no mencionadas (excluyendo SMS, ya descartado)

| Opción | Por qué podría servir | Por qué probablemente no aplica todavía |
|---|---|---|
| **Web Push (PWA)** | Notificaciones push desde el navegador, sin pasar por App Store ni Google Play — cero cuentas de desarrollador, cero requisito de testers | Menor alcance/confiabilidad en iOS (Apple limita web push en Safari), y el tutor promedio en RD probablemente no sabe "instalar" una PWA |
| **Telegram Bot API** | Completamente gratuito, sin aprobación de Meta, sin cuentas de desarrollador, notificaciones ilimitadas | Adopción de Telegram en RD es baja comparada con WhatsApp — el canal solo sirve si el tutor ya lo usa |
| **OneSignal (o similar) como capa sobre FCM** | Simplifica la gestión de tokens/segmentación de FCM sin escribir esa infraestructura desde cero | Solo resuelve un problema técnico menor dentro de la Alternativa C, no es una alternativa independiente |

Ninguna de estas reemplaza las tres alternativas principales, pero **Web Push vía PWA** vale la pena anotarla como opción de bajo costo si la app nativa se atrasa — podría ser el puente entre "nada" y "app en las tiendas" mientras se cumplen los requisitos de testing de Google/Apple.

---

## 5. Tabla comparativa resumen

| Criterio | WhatsApp (Meta) | Email (SES/Brevo/Resend) | App Móvil (Flutter+FCM) |
|---|---|---|---|
| Costo de entrada | $0 (pero requiere aprobación de Meta como negocio) | $0 (con Brevo/Resend en el tier gratuito) | $25 (Google) + $99/año (Apple) |
| Costo recurrente | Por mensaje entregado, pagado por el colegio directo a Meta | $0 hasta escalar, luego centavos por 1,000 | $0 (FCM gratis), solo cuentas de desarrollador |
| Tiempo hasta viable para piloto de sept. | Alto — depende de aprobación de Meta como Tech Provider y de plantillas | Bajo — configuración de un día | Medio-alto — depende de resolver testers (Google) y build sin Mac (Apple) |
| Alcance real en tutores de RD | Alto (WhatsApp es el canal más usado) | Medio (solo tutores que revisan correo con frecuencia) | Medio (requiere que el tutor instale la app) |
| Riesgo de bloqueo por terceros | Meta puede rechazar aprobación o cambiar tarifas | Bajo | Google/Apple pueden rechazar en revisión |

---

## 6. Checklist general de cierre — Fase 6

- [ ] Documentar esta comparación en el repositorio junto con el resto de las decisiones de arquitectura (mismo lugar que REQ-05.x)
- [ ] Decidir **secuencia**, no solo alternativa: probablemente Email (rápido, barato) como primer canal funcional para el piloto de septiembre, con WhatsApp y App Móvil como líneas de trabajo en paralelo que maduran después
- [ ] Confirmar con tu familiar docente si el correo es realista como canal para los tutores actuales del colegio piloto (validación de dominio, no solo de arquitectura)
- [ ] Antes del 20 de julio: si se decide avanzar con la App Móvil, iniciar el reclutamiento de testers de Google Play ese mismo día, porque el reloj de 14 días es la restricción de tiempo más rígida de las tres alternativas
- [ ] Evaluar si vale la pena registrar la cuenta de Google Play como organización antes de esa fecha, ya que decide si el requisito de testers aplica o no
- [ ] Mantener la restricción ya acordada: solo casos importantes (inasistencias acumuladas, tardanzas múltiples) se notifican por WhatsApp/Email — el push de "presente/tardanza/ausente" individual queda dentro de la app o del kiosco, no dispara mensajería externa por cada evento

---

*Este documento es un análisis de planificación (Fase 6). No implica implementación de código. Las tarifas y requisitos de Meta, Google y Apple citados aquí deben reverificarse directo en la fuente oficial antes de comprometer presupuesto, ya que son términos que cambian con frecuencia.*

---

## Fase 7 — Rediseño de Navegación de Escuela (Sidebar, Breadcrumbs, Buscador)

**Rama:** `feature/v0.9.0-school-navigation` (separada de cualquier fix cosmético — cambia la arquitectura de navegación, no solo estilos)

**Estado:** Análisis UX/QA completado y decisiones tomadas. **Sin código todavía** — este documento deja constancia técnica de qué se va a construir antes de tocar el layout de escuela, siguiendo el mismo formato de Fase 6/8.

> **Nota de metodología:** el análisis se hizo primero por revisión de código, y luego se **verificó en vivo** (login real como admin y como escuela, vía túnel público sobre el entorno Sail local — el navegador de esta sesión no tiene acceso directo a `localhost`/`orvian.test`). Hallazgos confirmados en esta sesión:
> - El Sidebar de admin (`aside`) tiene `position: static` y ancho real `288px` (`w-72`) en estado expandido — confirma en runtime que el toggle de ancho empuja el `<main>` por estar dentro del flujo `flex`, la premisa exacta de 7.12.
> - `breadcrumbs.blade.php` está presente en `/admin/hub` (muestra "Admin Hub") y **ausente** en `/app/academic/courses` (layout de escuela) — confirma 7.2.
> - El navbar de módulo de escuela muestra el link accesible "Volver al Hub" superpuesto al ícono (el flip 3D solo se revela visualmente en hover) — confirma 7.1.2.
> - El buscador decorativo ("Buscar en el sistema... Alt K") y el tooltip "Solo para esta sesión..." están efectivamente en producción en el navbar de admin — confirma 7.11.
> - El botón "Mi Perfil" en el navbar de escuela **sí abre el modal** (`ProfileModal`, con pestañas Información Personal/Seguridad/Preferencias) en vez de navegar a `/app/profile` — confirma 7.7.
> - Los botones de estado (En línea/Ausente/Ocupado/Desconectado) están visibles en tres lugares distintos: dropdown de admin, navbar de escuela y modal de perfil — confirma el alcance de 7.13.
> - Los 9 SVG de `assets/icons/modules/` cargan sin error (200 OK) desde el navbar y el dashboard — confirma que el asset base de 7.10 ya existe y funciona.
> - **Pendiente real:** no se pudo tomar captura de pantalla en esta sesión (el compositor de la herramienta de navegador cuelga en esta página, posiblemente por Laravel Debugbar/`APP_DEBUG=true` activo) — la validación fue por árbol de accesibilidad + red, no visual. Sigue faltando una prueba táctil real en tablet para 7.1.3 (hover no existe en touch).
> - **Nota operativa fuera de alcance de Fase 7:** se encontró y corrigió un archivo `public/hot` obsoleto (de una sesión anterior de `npm run dev`) que hacía que Laravel sirviera referencias a `localhost:5173` en vez de los assets compilados de `npm run build`, rompiendo el CSS. No relacionado con el rediseño de navegación, solo higiene del entorno de pruebas.

### 7.0 — Por qué se revierte la decisión original ("estilo Odoo")

El layout de escuela (`layouts/app-module.blade.php`) se diseñó copiando el patrón hub-and-spoke de Odoo: un Hub central con tarjetas de módulo, y al entrar a un módulo, un navbar horizontal con pestañas (`moduleLinks`) más un ícono que se voltea en hover para "volver al hub". Ese patrón asume un usuario que ya entiende la metáfora de "apps" como íconos de inicio — válido para contadores/administradores que usan ERPs a diario, **no** para el usuario real de ORVIAN (profesores, padres, directores de colegios dominicanos con poca exposición previa a este tipo de software). El panel de SuperAdmin, en cambio, ya usa Sidebar (`layouts/sidebar.blade.php`) — es decir, hoy el sistema le da **más** orientación permanente al staff interno (más técnico) que a los usuarios finales de escuela (menos técnicos). Es la prioridad invertida.

### 7.1 — Sidebar vs. Navbar horizontal para el panel de escuela

**Decisión: migrar a Sidebar**, replicando el patrón ya construido en `resources/views/components/sidebar/*` y `layouts/sidebar.blade.php`.

1. **Carga cognitiva y permanencia de ubicación.** Con el navbar horizontal + Hub, cambiar de módulo es: volver al Hub → escanear tarjetas → entrar al nuevo módulo (mínimo 2 clics + 1 pantalla de tránsito). Con Sidebar, todos los módulos están siempre visibles y un clic te lleva a cualquiera — sin pantalla intermedia. Para un usuario que no tiene un modelo mental previo de "módulos", ver la lista completa de opciones todo el tiempo reduce la carga de memoria (no tiene que recordar qué hay "detrás" del ícono de home).
2. **El gesto de "volver" es el punto más frágil del diseño actual.** En desktop, volver al Hub depende de un hover sobre el ícono del módulo que hace un flip 3D a una flecha (`x-data="{ hovered: false }"` en `navbar.blade.php:66-84`) — una animación novedosa, sin label visible, sin precedente en el resto del sistema. **En touch (tablets, que es exactamente el dispositivo esperable en un colegio) no existe hover**, por lo que ese affordance es directamente invisible; el único camino real en touch es el drawer mobile con el link explícito "Volver al Hub" (`navbar.blade.php:234-239`), que solo aparece si el usuario primero encuentra el botón ☰. Es un flujo de descubrimiento en dos pasos para una acción que debería ser la más obvia del sistema.
3. **Sidebar elimina la necesidad de "volver" como concepto.** Si todos los módulos están en la barra lateral, no hay "adentro" ni "afuera" de un módulo — solo hay "dónde estoy ahora" (ítem resaltado). Esto no es una mejora cosmética al botón de volver: es la eliminación completa del problema que la Fase 7 original intentaba parchar.
4. **Menos clics a funciones internas.** Hoy, para llegar a un sub-link de un módulo (ej. "Excusas" dentro de Asistencia) desde otro módulo hay que: Hub → Asistencia → esperar el navbar de pestañas → click en "Excusas" (3 clics). Con Sidebar (dropdown expandible, igual que `sidebar/dropdown.blade.php` en admin), es Sidebar → Asistencia (expande) → Excusas (2 clics), y el dropdown puede quedar abierto por contexto de ruta activa.
5. **Consistencia entre paneles.** Un usuario que además es dueño/administra varias escuelas (o un SuperAdmin que impersona) hoy salta entre dos paradigmas de navegación distintos (Sidebar en admin, navbar+hub en escuela). Unificar el patrón reduce el reaprendizaje al cambiar de contexto.

**Contras a mitigar en la implementación** (no cambian la decisión, pero hay que resolverlos):
- El Hub (`app.dashboard`) deja de ser el único "home" visual — su rol pasa de "selector de módulos" a "resumen/dashboard", que es un rol más honesto para lo que ya es.
- El ⌘K / buscador (7.4) se vuelve más importante como atajo, precisamente porque Sidebar por sí sola no resuelve "quiero llegar a algo específico sin escanear la lista".
- Mobile necesita seguir siendo un drawer/overlay (igual que admin ya lo resuelve con `sidebarOpen` + overlay) — no hay pérdida de patrón ahí, es directamente reusar lo que ya existe.

### 7.2 — Breadcrumbs: globales en el layout, no dentro de `module-toolbar`

**Decisión: agregar `<x-navbar.breadcrumbs />` en `layouts/app-module.blade.php`**, dentro de `<main>` antes de `{{ $slot }}` — el mismo lugar donde vive en `components/admin.blade.php:48`. **No** meterlo dentro de `components/app/module-toolbar.blade.php`.

Razones:
- `module-toolbar` es un componente **opcional y por-vista** (título, acciones, buscador, secundarias) — su propio comentario dice explícitamente "Solo se incluye en vistas de módulo — no aparece en el hub". Si el breadcrumb vive ahí, el Hub y cualquier vista futura que no use toolbar (o que no lo incluya por decisión de diseño) se queda sin wayfinding, de forma inconsistente vista por vista.
- Breadcrumb es chrome estructural ("dónde estoy en la jerarquía"), no contenido de la vista. Mezclarlo con acciones (`$actions`, `$search`, `$secondary`) rompe la responsabilidad única del componente y obliga a repetir lógica de breadcrumb en cada Livewire que instancie el toolbar.
- `breadcrumbs.blade.php` ya está escrito para funcionar en ambos contextos (`$isAdminContext = request()->is('admin*')` con fallback a `app.dashboard`/"Dashboard") — solo nunca se incluyó en el layout de escuela. Es un olvido de integración, no un problema de diseño del componente.
- Colocarlo en el layout garantiza una sola ubicación consistente para las 32 vistas actuales sin tocar una por una.

### 7.3 — Fin de `config('modules.*')` inyectado por Livewire

Hoy 32 componentes Livewire (`app/Livewire/App/**`) hacen `->layout('layouts.app-module', config('modules.academico'))` para que el navbar sepa qué pestañas mostrar. Con Sidebar, la navegación deja de vivir "por vista" y pasa a vivir en la estructura fija del Sidebar (igual que `layouts/sidebar.blade.php` en admin, que resuelve `:active="request()->routeIs(...)"` directamente contra las rutas, sin recibir nada del componente Livewire que se está renderizando).

- `config/modules.php` **se mantiene** como fuente de verdad de nombre/ícono/sub-links — pero pasa a ser leído *una sola vez* desde el include del Sidebar de escuela (`layouts/sidebar-app.blade.php`, nuevo, análogo a `layouts/sidebar.blade.php`), no repartido en 32 `->layout()` calls.
- Se elimina el segundo argumento de `->layout()` en los 32 Livewire — la vista deja de necesitar `$module`/`$moduleIcon`/`$moduleLinks` como props.
- Este es también el mismo dato que alimenta el buscador global (7.4): `config/modules.php` ya tiene `label` + `route` por sub-link, que es exactamente la forma que necesita un índice de búsqueda.

### 7.4 — Buscador global de rutas (rediseñado)

**Decisión revisada:** la primera versión (implementada en la Fase D) indexaba `config('modules.php')` — es decir, la misma estructura del Sidebar ("Académico", "Estudiantes"). Eso no sirve al objetivo real del buscador: un usuario nuevo que no conoce la jerga del sistema no escribe "Administración de Estudiantes", escribe lo que quiere **hacer** — "crear estudiante", "excusa médica", "importar". `config('modules.php')` no tiene ese nivel de detalle (no describe acciones como "Crear Estudiante", solo el link a la lista). Se separan las dos fuentes:

- **`config('modules.php')`** — sigue siendo la fuente única del **Sidebar** (estructura por módulo). No cambia.
- **Metadatos `->defaults('navigationSearch', [...])` directo en `routes/app/*.php`** — nueva fuente única del **buscador**. Cada ruta indexable declara `title`, `description` y `keywords` en su propia definición, junto a su `->middleware('can:...')` — el permiso que ya protege la ruta es automáticamente el mismo que filtra si aparece en resultados, sin mantener una lista de permisos aparte.

```php
Route::get('/create', ExcuseForm::class)
    ->middleware('can:manage_excuses')
    ->name('create')
    ->defaults('navigationSearch', [
        'title'       => 'Registrar Nueva Excusa',
        'description' => 'Justificar la ausencia o salida temprana de un estudiante',
        'keywords'    => ['enfermo', 'cita', 'justificar', 'ausencia', 'falta', 'médica'],
    ]);
```

No se indexan todas las ~38 rutas de `routes/app/*.php` — se excluyen a propósito las que requieren un `{parámetro}` de una entidad ya existente (`students.show`, `students.edit`, `teachers.assignments`, `roles.edit`, `courses.show`, `attendance.audit`...): navegar ahí sin un ID específico no tiene sentido en un buscador global. Quedaron indexadas 25 rutas — todas las páginas y acciones de nivel superior con sentido como destino de búsqueda.

**`App\Services\Navigation\GlobalSearchService`** — dos capas, no una:
- `index()` — escanea `Route::getRoutes()`, filtra las que tienen `defaults['search']`, y cachea el resultado 24h (`Cache::remember`). Caro (recorre todas las rutas de la app), de ahí el caché. Los datos cacheados son "crudos": incluyen la lista de permisos (`can:`) extraída de cada ruta, sin filtrar por usuario — el caché es el mismo para todos.
- `forUser(User $user)` — filtra `index()` contra `$user->can($permission)` por cada ítem. **No se cachea**: son ~25 items, filtrar es barato, y cachear por usuario/rol sería una capa de invalidación innecesaria para este volumen de datos. Devuelve `[]` si el usuario no tiene `school_id` (mismo alcance que el resto del buscador — sigue sin ser funcional en el panel de admin).

**Extracción de permisos:** `permissionsFor()` recorre `$route->gatherMiddleware()` y toma el primer argumento de cada middleware `can:` (`can:settings.view, settings.update` → solo `settings.view` cuenta, porque así lo interpreta Laravel en runtime: lo que sigue a la primera coma son argumentos extra del Gate, no permisos adicionales). Si una ruta vive dentro de un `Route::middleware('can:x')->group(...)` y además tiene su propio `->middleware('can:y')`, ambos se extraen y **ambos** se exigen (AND) — igual que en runtime.

**Se descartó** la propuesta de un View Composer global (`view()->composer('*', ...)`) inyectando el índice en *todas* las vistas — solo `components/navbar/layout.blade.php` lo necesita; inyectarlo en las ~200 vistas restantes sería trabajo (y payload HTML) sin uso. `GlobalSearchService::forUser()` se llama directo desde ese único archivo.

**Consumo:** `components/navbar/layout.blade.php` — el input de búsqueda (desktop, `Alt+K`) y el modal móvil comparten el mismo `x-data` (`query`, `results` computado filtrando por `title`/`description`/`keywords`). El ícono de cada resultado se resuelve por prefijo del nombre de ruta (`app.attendance.*` → `asistencia.svg`, `app.academic.*` → `academico.svg`, resto → `administracion.svg`) — heurística simple, no depende de `config('modules.php')`.

**Archivos modificados:**

| Archivo | Cambio |
| :--- | :--- |
| `routes/app/attendance.php`, `academic.php`, `users.php`, `role.php`, `school.php`, `core.php` | `->defaults('navigationSearch', [...])` en 25 rutas |
| `app/Services/Navigation/GlobalSearchService.php` | Reescrito: `index()` desde `Route::getRoutes()` (antes `config('modules')`) + `forUser()` nuevo (filtro por permisos) |
| `resources/views/components/navbar/layout.blade.php` | Consume `title`/`description`/`keywords`/`icon` en vez de `label`/`module` |
| `config/modules.php` | Comentario de cabecera corregido — ya no se menciona como fuente del buscador |

**Verificado:** `php -l` limpio, `route:list` confirma las 38 rutas siguen registrando igual (ningún `->defaults()` rompe nada), `view:cache` sin errores, render real vía `tinker` con el índice completo (25 ítems, permisos extraídos correctamente incluyendo el caso de permisos anidados como "Crear Estudiante" → `[students.view, students.create]`), y `forUser()` probado con el usuario real de `Politénico Ruth Elvira Aybar` (School Principal, ve los 25). No se pudo probar el caso de un rol con permisos limitados por falta de un usuario Teacher sembrado en la BD de desarrollo — el mecanismo de filtrado (`$user->can($permission)`) es el mismo ya usado por los middleware `can:` de las rutas, no es código nuevo sin probar. `sail artisan test` sin regresiones.

**Bug post-implementación — clave `search` colisionaba con propiedades Livewire:** `->defaults($key, ...)` inyecta un parámetro de ruta, y Livewire vincula automáticamente parámetros de ruta a propiedades públicas del mismo nombre. La clave original, `'search'`, chocaba con `public string $search` que ya existía en `BiometricKiosk`, `EnrollmentHub` y `StudentPrintManager` (sus propios buscadores locales) — Livewire intentaba asignarles el array de metadatos completo, tirando 500 (`TypeError: Cannot assign array to property ...$search of type string`) en las tres rutas. Renombrado a `'navigationSearch'` en las 25 rutas + `GlobalSearchService`. Verificado con `Livewire::test()` en las 3 rutas afectadas, las 3 OK.

### 7.5 — Reemplazo del navbar mobile original: contenido del Dashboard unificado

El punto original de esta fase (3 ajustes cosméticos a `components/app/navbar.blade.php` en mobile) **queda descartado, no "en espera"**: con Sidebar confirmado (7.1) y no como hipótesis, ese navbar horizontal con `moduleLinks`, drawer y flip 3D deja de existir — no hay superficie donde aplicar esos 3 fixes. Se reemplaza por una pregunta más importante que sí queda abierta: **si el Sidebar ya resuelve la navegación entre módulos, ¿qué le queda por hacer a `app.dashboard`?**

Hoy `resources/views/app/dashboard.blade.php` es, en la práctica, un segundo sistema de navegación: una grilla de `x-ui.app-tile` que duplica exactamente lo que el Sidebar va a mostrar (Administración, Académico, Asistencia, Notas, Classroom, Reportes...). Si se deja así, el usuario tiene **dos** formas de llegar a "Académico" (tile del dashboard, ítem del sidebar) que no aportan nada distinto entre sí — es redundancia, no refuerzo.

**Sugerencias para el contenido del dashboard unificado** (a definir en detalle cuando se implemente, no en esta fase de documentación):

- **Resumen operativo del día**, no un menú: cifras que ya tiene el sistema y que hoy nadie ve consolidadas — asistencia del día (presentes/ausentes/tardanzas del Plantel), excusas pendientes de aprobar, matrículas en proceso desde el Hub de Matriculación. Es información que cambia día a día, a diferencia de un menú de módulos que es siempre igual.
- **"Accesos recientes"** — el placeholder que ya existe en `dashboard.blade.php:142-159` ("Aquí aparecerán tus últimas secciones visitadas") es exactamente el tipo de contenido que sí justifica vivir en un dashboard: es personalizado y cambia por usuario, cosa que un ítem de Sidebar no puede ofrecer.
- **Alertas y pendientes accionables** — ej. "3 excusas esperando aprobación", "Sesión de asistencia sin cerrar" — con link directo a la acción, no al módulo en general. Esto le da al dashboard una razón de ser distinta a "otra forma de navegar".
- **Evitar el anti-patrón "un dashboard por módulo".** No crear vistas tipo `academic/dashboard`, `attendance/dashboard` (ya existe `app.attendance.dashboard`, ver `config/modules.php:45` — evaluar si se fusiona su contenido útil hacia el dashboard unificado en vez de mantenerlo como landing duplicada del módulo Asistencia). Un panel de control por módulo repite el mismo error que el Hub actual: fragmenta la vista general en N pantallas que el usuario tiene que recordar visitar por separado.
- **La grilla de tiles no desaparece necesariamente** — puede quedarse como acceso rápido a 2-3 acciones frecuentes ("Nuevo Estudiante", "Registrar Asistencia"), pero como *acciones*, no como *navegación a módulos completos* — esa responsabilidad pasa 100% al Sidebar.

### 7.6 — Logo dinámico por escuela en el Sidebar

`resources/views/components/application-logo.blade.php` hoy solo sabe mostrar el logo ORVIAN (claro/oscuro, full/icon). Se le agrega lógica de resolución por tenant:

1. `¿Auth::user()->school_id` es null? → usuario sin escuela (SuperAdmin) → logo ORVIAN de siempre, sin cambios.
2. Si tiene `school_id` → cargar `Auth::user()->school` (ya viene resuelto por `IdentifyTenant` en `app('currentSchool')`, evitar una query extra usando ese binding en vez de la relación) y revisar `school->logo_path` (**el campo ya existe** — `app/Models/Tenant/School.php:44` lo tiene en `$fillable`, no hace falta migración).
3. Si `logo_path` existe → mostrarlo, respetando el mismo comportamiento responsive que ya tiene el Sidebar (`sidebar/layout.blade.php:13-31`): tamaño reducido cuando `!sidebarOpen && !hasHover`, tamaño completo cuando el sidebar está expandido/en hover.
4. Si no tiene `logo_path` (o no tiene escuela) → fallback al logo ORVIAN, exactamente como hoy.

**Matiz a documentar para la implementación:** el logo ORVIAN tiene dos variantes reales (`logo-full-*.svg` y `logo-icon-*.svg`) — un ícono cuadrado optimizado para el estado colapsado. Un colegio que sube su logo probablemente solo tiene **un** archivo (su logotipo completo, no una versión cuadrada recortada). Definir antes de codificar: ¿se reescala el mismo archivo en ambos estados (más simple, puede verse mal si el logo es muy horizontal) o se le pide al colegio subir también una variante cuadrada/icono en `SchoolSettings` (más trabajo de UI, mejor resultado visual)? Recomendación: empezar con reescalado simple (`object-contain` dentro de un contenedor cuadrado) y solo agregar el segundo campo si algún colegio piloto reporta que se ve mal.

### 7.7 — Modal de perfil → ruta dedicada

`app/Livewire/Shared/ProfileModal.php` + `resources/views/livewire/shared/profile-modal.blade.php` se crearon para "ahorrar una ruta" pero duplican exactamente lo que ya hace `app/Livewire/Shared/Profile.php` (misma lógica: datos personales, foto, contraseña, preferencias), servido en `routes/app/core.php:7` como `app.profile` — ruta que hoy nadie enlaza desde la navegación de escuela porque el navbar abre el modal en su lugar (`$dispatch('open-modal', 'profile-modal')` en `navbar.blade.php:184`).

**Decisión:** el nuevo bloque de usuario del Sidebar (mismo patrón que ya existe en `sidebar/layout.blade.php:97` para admin, que ya enlaza a `route('admin.profile')` en vez de abrir un modal) apunta a `route('app.profile')` para escuela. Un usuario que edita su perfil un par de veces al año no necesita ahorrarse una navegación completa — el costo de mantener dos componentes Livewire con la misma lógica (y el riesgo de que diverjan, como ya pasó: `ProfileModal` no tiene `sidebar_collapsed` ni algunos campos que sí tiene `Profile`) es mayor que el beneficio de la ruta ahorrada.

**No eliminar `ProfileModal.php` ni su vista** — se deja de referenciar desde el Sidebar/navbar (dead code intencional, mismo criterio que 7.8 con login v1), por si se decide reusar el patrón de modal en otro contexto más adelante.

### 7.8 — Deprecar Login v1 (ocultar, no eliminar)

El sistema hoy decide entre `auth.login` (azul, diseño actual) y `auth.login-v1` (`layouts/guest-v1.blade.php`, diseño "arquitectónico" legado) leyendo una cookie `orvian_login_version` en `AuthenticatedSessionController::create()` (`app/Http/Controllers/Auth/AuthenticatedSessionController.php:20-27`), que a su vez se setea desde el selector visual en Preferencias (`resources/views/livewire/shared/profile.blade.php:406-482`, "Selecciona tu Interfaz de Acceso") y se persiste en `Profile::savePreferences()` (`app/Livewire/Shared/Profile.php:175, 180-184`).

**Decisión:** un usuario que no conoce ERPs no debería tener la opción de elegir entre dos sistemas de login — es una decisión de producto, no una preferencia de usuario. Se deja **un solo login** (el azul, sin prefijo `v1`):

- `AuthenticatedSessionController::create()` deja de leer la cookie y siempre retorna `view('auth.login')`.
- Se quita el selector de "Interfaz de Acceso" de `profile.blade.php` (bloque completo, líneas 406-482) y las líneas asociadas de `Profile.php` (`$loginVersion`, la validación/guardado de `preferences['login_version']` y el `Cookie::queue('orvian_login_version', ...)`).
- **No se eliminan los archivos** `resources/views/layouts/guest-v1.blade.php`, `resources/views/auth/login-v1.blade.php` ni `app/View/Components/GuestV1Layout.php` — quedan en el repo sin ruta que los sirva, por si se quiere retomar el diseño más adelante. Mismo criterio de "ocultar, no borrar" que 7.7.
- El caché de la cookie existente en navegadores de usuarios que ya habían elegido `v1` deja de tener efecto en cuanto el controlador ignore el valor — no hace falta invalidar la cookie activamente, simplemente deja de leerse.

### 7.9 — Preferencia de Sidebar colapsado: de checkbox a persistencia automática (localStorage)

Hoy "colapsar menú lateral por defecto" es un checkbox en Preferencias (`profile.blade.php:384-404`, solo visible `@if($isAdmin)`) que se guarda en `$user->preferences['sidebar_collapsed']` en base de datos y se lee en `components/admin.blade.php:4-6` para fijar el estado inicial de Alpine (`sidebarOpen`). Es decir: para cambiar algo tan simple como "quiero el sidebar cerrado", el usuario tiene que ir a Perfil → Preferencias → marcar un checkbox → Guardar → recargar.

**Decisión:** reemplazar por el patrón que ya usan la mayoría de apps con sidebar colapsable (el que el usuario describe como "tipo Alegra"): el estado se guarda automáticamente en `localStorage` en el momento en que el usuario abre o cierra el sidebar con el propio botón toggle — sin pasos intermedios, sin ir a Preferencias, sin round-trip al servidor.

- `x-data="{ sidebarOpen: ... }"` en `components/admin.blade.php:10` pasa de inicializarse desde `$sidebarInitial` (preferencia PHP) a inicializarse desde `localStorage.getItem('sidebarOpen')`, con `$watch('sidebarOpen', val => localStorage.setItem('sidebarOpen', val))` (mismo patrón que ya usa `layouts/guest.blade.php:3-4` para `darkMode`, que es exactamente este mecanismo aplicado a otra preferencia).
- Se elimina el checkbox de `profile.blade.php` y el campo `sidebar_collapsed` de `Profile.php` (`public bool $sidebar_collapsed`, su carga en `mount()`, su guardado en `savePreferences()`).
- **Nota de decisión sobre el `$user->preference('sidebar_collapsed', ...)`:** queda como dato huérfano en el JSON de preferencias existentes — no se migra ni se borra activamente, simplemente deja de leerse. Es dato histórico sin impacto si queda ahí.
- Esto aplica igual para admin (hoy el único con Sidebar) y para escuela una vez 7.1 esté implementado — mismo mecanismo para ambos, sin diferenciar por tipo de usuario como pedía el checkbox original.

### 7.10 — Iconos de módulo (SVG propio) en vez de Heroicons en el Sidebar

`sidebar/item.blade.php` y `sidebar/dropdown.blade.php` usan `<x-dynamic-component :component="$icon" />` (Heroicons genéricos: `heroicon-s-academic-cap`, `heroicon-s-user-group`, etc. — ver `layouts/sidebar.blade.php:2-51`). Mientras tanto, `public/assets/icons/modules/` ya tiene una suite de íconos propios por módulo (`academico.svg`, `asistencia.svg`, `administracion.svg`, `notas.svg`, `classroom.svg`, `reportes.svg`, `conversaciones.svg`, `horarios.svg`, `web.svg`) consumida hoy solo por `x-ui.module-icon` en el navbar de módulo y en las tiles del dashboard.

**Decisión: sí, vale la pena para este nivel de usuario.** Un ícono de línea genérico (Heroicons) exige que el usuario lea el label para saber qué es "Académico" vs. "Asistencia" — dos conceptos que en Heroicons pueden terminar pareciendo iconografía intercambiable (`academic-cap` vs. `clipboard-document-check`, por poner un ejemplo). Los íconos propios de `assets/icons/modules/` ya están diseñados específicamente para representar cada módulo de ORVIAN — reconocerlos de un vistazo reduce la dependencia de leer texto, que es justamente el objetivo con usuarios de baja alfabetización tecnológica (reconocimiento visual > lectura). Además da consistencia: hoy el mismo módulo "Académico" se representa con un heroicon en el Sidebar de admin pero con `academico.svg` en el navbar/dashboard de escuela — dos íconos distintos para el mismo concepto según en qué panel estés.

**Implementación a futuro (sin código ahora):**
- `sidebar/item.blade.php` y `sidebar/dropdown.blade.php` agregan un modo alternativo de ícono: mantener `icon` (heroicon) para ítems que no son "módulo" (ej. "Roles del Sistema", "Usuarios Globales" en admin no tienen SVG propio en `assets/icons/modules/`), y agregar un prop nuevo tipo `moduleIcon` que renderice `<x-ui.module-icon :name="..." />` cuando el ítem representa un módulo de la suite existente.
- No es un reemplazo total de Heroicons en el Sidebar — es un uso dirigido donde ya existe el asset correcto, para no forzar SVGs nuevos en ítems que nunca los tuvieron (login, roles, logs del sistema, etc. siguen con heroicon).

### 7.11 — Limpieza de `components/navbar/layout.blade.php`

Esta barra superior delgada (usada hoy en `components/admin.blade.php:44`, y candidata a reusarse o no según lo que se decida en 7.1/7.5 para escuela) tiene tres problemas independientes:

1. **Botón hamburguesa + tooltip obsoleto** (`navbar/layout.blade.php:6-43`): el tooltip explica "Solo para esta sesión, para que persista cámbialo en Preferencias" — mensaje que deja de tener sentido en cuanto 7.9 elimina el checkbox de Preferencias y hace que el toggle **siempre** persista (vía `localStorage`, automáticamente). Se elimina el bloque completo del tooltip (`x-data="{ showTip, tipTimer }"` y su `<div x-show="showTip">`); el botón de toggle se queda, pero ya no necesita explicar nada porque su comportamiento pasa a ser el esperado por defecto.
2. **Buscador no funcional y sin los componentes de `docs/ui/ui-forms.md`**: el input de búsqueda (`navbar/layout.blade.php:50-62`) es un `<input>` hecho a mano, sin `x-ui.forms.input` (pierde el manejo de estados/foco/error documentado). El modal de búsqueda móvil (`navbar/layout.blade.php:132-144`) reimplementa su propio contenedor (`<div x-show="show">` con transiciones manuales) en vez de usar `resources/views/components/modal.blade.php`, que ya resuelve foco atrapado, `Escape`, click-fuera y transiciones — exactamente lo que el modal casero reinventa peor. Ambos se corrigen cuando se implemente el buscador real de 7.4: el input pasa a `x-ui.forms.input` con `icon-left="heroicon-s-magnifying-glass"`, y el modal móvil pasa a `<x-modal name="global-search">` reusando la lógica ya existente en `components/modal.blade.php`.
3. **Botón de pantalla completa** (`navbar/layout.blade.php:72-128`): se elimina por completo. No es una función que un director o profesor vaya a buscar, agrega superficie de interacción sin valor claro para el perfil de usuario objetivo, y ya tiene un historial de necesitar parches (`Fase 7.5` original solo lo ocultaba en mobile — la solución más simple es no tenerlo).

### 7.12 — Sidebar colapsado en desktop: overlay en hover, no "empuje" de contenido

Hoy el estado colapsado del Sidebar (`w-20`, `!sidebarOpen && !hasHover`) se expande a `w-72` en `mouseover` (`sidebar/layout.blade.php:2-11`) — y como el Sidebar es un elemento en el flujo normal (`flex` dentro de `components/admin.blade.php:27`), ese cambio de ancho **empuja** el contenido principal (`<main>`) cada vez que el mouse pasa por encima, incluso sin intención de abrirlo. Es la misma familia de problema que 7.1.2 (el flip 3D del navbar de escuela): una animación que se dispara por accidente y mueve todo el layout, lo cual es exactamente el tipo de "movimiento brusco" que deteriora la percepción de estabilidad de la interfaz.

**Decisión:** el estado colapsado con hover en desktop debe comportarse como el propio Sidebar **ya se comporta en mobile** (`sidebar/layout.blade.php:9-10`: `fixed inset-y-0 left-0 translate-x-0` cuando `sidebarOpen && window.innerWidth < 1024`) — superpuesto (`fixed`/`absolute`, saca del flujo), no empujando el `<main>`. La diferencia explícita que pide el usuario: **sin el overlay oscuro de fondo** que sí tiene el drawer mobile (`components/admin.blade.php:30-39`, el `bg-black/50 backdrop-blur-sm`) — en desktop el hover debe sentirse como una expansión momentánea y liviana, no como abrir un modal que bloquea el resto de la pantalla. Esto significa:
- Cuando `!sidebarOpen && hasHover` (colapsado + mouse encima) en desktop: el aside se posiciona en `absolute`/`fixed` sobre el contenido (con `shadow-xl` para dar sensación de estar "flotando" sobre el resto, ya presente hoy), en vez de cambiar su `width` dentro del flujo `flex`.
- El `<main>` mantiene su ancho fijo (basado en el estado real `sidebarOpen`, no en `hasHover`) — no reacciona al hover en absoluto.
- Cuando `sidebarOpen` es `true` (fijado por el usuario, ver 7.9), el comportamiento actual de "empujar" el contenido sí es correcto — eso es una decisión explícita del usuario (abrir el sidebar), no un efecto secundario del mouse pasando por encima.

### 7.13 — Eliminación del sistema de Status de Usuario (online/away/busy/offline)

Se concluye, con el usuario, que el indicador de presencia (`online`/`away`/`busy`/`offline`) no aporta funcionalidad más allá de "ver quién está o estuvo conectado" — sin chat en tiempo real ni colaboración simultánea dentro de ORVIAN que dependa de saber si alguien está activo ahora mismo, y con `feature/activity-log` como el sustituto correcto para trazabilidad de acciones (que sí es información accionable, a diferencia de un punto de color). Se decide **eliminar por completo**, no ocultar (a diferencia de 7.7/7.8, aquí no hay ambigüedad de "podría reusarse después" — es una feature completa y autocontenida sin dependencias de otras partes del sistema).

**Superficie a eliminar** (confirmado por revisión de código, no solo lo que mencionó el usuario):

| Componente | Acción |
| :--- | :--- |
| `database` — columna `status` en `users` (`database/migrations/2026_03_13_004716_add_profile_fields_to_users_table.php:19`) | Nueva migración que la elimina (no editar la migración original) |
| `app/Models/User.php:28` — `'status'` en `$fillable` | Quitar |
| `app/Models/User.php` — columna `last_login_at` | **Evaluar aparte**: se usa también para "última conexión" fuera del status en sí; confirmar si tiene otro consumidor antes de tocarla (no estaba en el alcance que describió el usuario) |
| `app/Livewire/Shared/UserStatus.php` | Eliminar (componente completo: `setStatus()`, `mount()`) |
| `resources/views/livewire/shared/user-status.blade.php` | Eliminar |
| `@livewire('shared.user-status')` en `components/admin.blade.php` / futuro Sidebar de escuela / `sidebar/layout.blade.php:93` | Quitar la inclusión |
| `app/Console/Commands/UpdateUserStatus.php` (`orvian:update-user-status`) | Eliminar comando |
| `routes/console.php:15` — `Schedule::command('orvian:update-user-status')->everyFiveMinutes()` | Quitar del scheduler |
| `app/Listeners/UpdateUserStatusListener.php` (`handleLogin`/`handleLogout` que setean `status`) | Eliminar el listener; si el `EventServiceProvider` no tiene más listeners para `Login`/`Logout`, quitar también el registro del evento |
| `app/View/Components/Ui/Avatar.php` — prop `showStatus`, `$statusColor`, `$statusSize`, `getStatusColor()`, `getStatusSize()` | Quitar toda la lógica de status del componente |
| `resources/views/components/ui/avatar.blade.php:18-26` — bloque `@if($showStatus)` | Quitar el `<span>` indicador |
| Todos los `showStatus` en vistas (`navbar.blade.php`, `sidebar/layout.blade.php`, `livewire/shared/profile.blade.php`) | Quitar el prop de cada `<x-ui.avatar>` |

### 7.14 — Deprecar `module-toolbar` (tapaba el breadcrumb)

`components/app/module-toolbar.blade.php` se agregó copiando el patrón de barra secundaria de Odoo, sin un propósito propio más allá de "verse bien". Con el Sidebar como navegación principal (7.1) y los breadcrumbs activos (7.2), el toolbar quedó `sticky top-0` justo encima del breadcrumb, tapándolo visualmente y sin aportar nada que `x-ui.page-header` (ya usado en la mayoría de vistas con tabla) no resuelva mejor.

**Decisión:** eliminar `module-toolbar.blade.php` por completo. Extender `x-ui.page-header` con un slot `$secondary` — acciones que no ameritan botón propio se agrupan en un menú "···" (kebab): dropdown en desktop, bottom sheet en mobile, mismo patrón ya usado en `data-table/column-selector.blade.php` (no se inventó uno nuevo). El slot `$actions` existente de `page-header` absorbe lo que antes era el slot `actions` del toolbar.

**Archivos modificados:**

| Archivo | Acción |
| :--- | :--- |
| `resources/views/components/app/module-toolbar.blade.php` | **Eliminado** |
| `resources/views/components/app/search.blade.php` | **Eliminado** — quedó huérfano (nunca se instanció fuera del toolbar) |
| `resources/views/components/ui/page-header.blade.php` | Slot `$secondary` nuevo (dropdown desktop / bottom sheet mobile) |
| `resources/views/livewire/app/attendance/classroom-attendance-live.blade.php` | Toolbar → `page-header` (título dinámico vía `<x-slot:title>`) |
| `resources/views/livewire/app/attendance/attendance-reports.blade.php` | Toolbar → `page-header` nuevo (no tenía uno); "Historial del Plantel" pasa al slot `secondary` |
| `resources/views/livewire/app/attendance/classroom-attendance-history.blade.php` | Toolbar eliminado, acciones fusionadas al `page-header` ya existente |
| `resources/views/livewire/app/academic/students/index.blade.php` | Toolbar eliminado, acciones/secundarias fusionadas al `page-header` ya existente |
| `resources/views/livewire/app/academic/teachers/teacher-assignments.blade.php` | Toolbar → `page-header` nuevo |
| `resources/views/livewire/app/academic/teachers/teacher-index.blade.php` | Toolbar eliminado, acciones/secundarias fusionadas al `page-header` ya existente |
| `resources/views/livewire/app/academic/biometric-kiosk.blade.php` | Toolbar → `page-header` nuevo; su barra de controles sticky pasa de `top-[7rem]` a `top-0` (ya no hay navbar fijo ni toolbar encima) |
| `resources/views/livewire/app/academic/course-form.blade.php` | Toolbar → `page-header` nuevo |
| `resources/views/livewire/app/academic/course-index.blade.php` | Toolbar y el `<h1>` duplicado que ya tenía la vista → un solo `page-header` |
| `resources/views/livewire/app/academic/course-show.blade.php` | Toolbar → `page-header` nuevo |
| `resources/views/livewire/app/academic/enrollment-hub.blade.php` | Toolbar → `page-header` nuevo; `h-[calc(100vh-9rem)]` ajustado a `14rem` (aproximado, pendiente de verificación visual) |
| `resources/views/livewire/app/attendance/plantel-attendance-index.blade.php` | Toolbar eliminado, acciones fusionadas al `page-header` ya existente |

**Verificado:** `view:cache` sin errores de sintaxis, `Livewire::test()` en 11 de los 12 componentes (el doceavo, `TeacherAssignments`, no tenía datos de maestro en la BD de prueba para montar la ruta — su cambio es mecánicamente idéntico a los otros 11), `sail artisan test` completo sin regresiones nuevas, y confirmado en vivo vía túnel: breadcrumb ya no tapado, botón "Más acciones" funcionando en `/app/attendance/reports`.

### Checklist de cierre — Fase 7

- [x] Click-through manual en admin y escuela (desktop) — hecho en esta sesión vía túnel público (Cloudflare Tunnel) sobre Sail local; hallazgos listados en la nota de metodología arriba.
- [ ] Falta el mismo click-through en tablet/touch real para confirmar 7.1.3 (el navegador de esta sesión no simula touch/hover de forma fiable).
- [x] Deprecar `module-toolbar` y migrar sus 12 usos a `x-ui.page-header` con slot `secondary` (7.14) — hecho.
- [x] Crear `layouts/sidebar-app.blade.php` a partir de `config/modules.php` (7.1) — hecho.
- [x] Bug encontrado post-implementación: el Sidebar mostraba todos los links de módulo sin filtrar por permiso (cualquier rol veía todo, algunos llevaban a 403). `config/modules.php` cada `moduleLink` ahora declara `permission` (mismo permiso que protege la ruta vía `can:` middleware); `sidebar-app.blade.php` filtra con `auth()->user()->can(...)` y oculta el módulo entero si queda sin links visibles. Verificado revocando permisos dentro de una transacción revertida.
- [x] Agregar `<x-navbar.breadcrumbs />` a `layouts/app-module.blade.php` (7.2) — hecho.
- [x] Quitar el segundo argumento de `->layout()` en los 32 Livewire de `app/Livewire/App/**` (7.3) — hecho.
- [x] Buscador global funcional (7.4) — hecho, rediseñado. Fuente: `->defaults('navigationSearch', ...)` en `routes/app/*.php` (25 rutas), no `config/modules.php`. Cacheado 24h vía `GlobalSearchService::index()`, filtrado por permisos por usuario vía `forUser()`, consumido en `components/navbar/layout.blade.php` (desktop + modal móvil).
- [ ] Definir contenido real del dashboard unificado (7.5) antes de tocar `app/dashboard.blade.php` — por ahora solo hay sugerencias, no una spec cerrada. Pendiente a propósito, sin tocar.
- [x] Implementar resolución de logo por escuela en `application-logo.blade.php` (7.6) — hecho, reescala la única imagen subida.
- [x] Repuntar el enlace "Mi Perfil" del Sidebar/navbar de escuela hacia `route('app.profile')`; dejar `ProfileModal` sin referencias (7.7) — hecho.
- [x] `AuthenticatedSessionController::create()` deja de leer la cookie de versión de login; quitar el selector de `profile.blade.php` (7.8) — hecho.
- [x] Migrar persistencia de `sidebarOpen` de preferencia PHP a `localStorage` (7.9); quitar checkbox de Preferencias — hecho, en admin y escuela.
- [x] Extender `sidebar/item.blade.php` y `sidebar/dropdown.blade.php` con soporte de `moduleIcon` vía `x-ui.module-icon` (7.10) — hecho.
- [x] Limpiar `components/navbar/layout.blade.php`: quitar tooltip de sidebar, quitar fullscreen, migrar buscador a `x-ui.forms.input` + `x-modal` (7.11) — hecho. `components/app/navbar.blade.php` (navbar horizontal antiguo, sin referencias tras la Fase B) eliminado.
- [x] Rehacer el hover del Sidebar colapsado en desktop como overlay sin oscurecer el fondo, sin empujar `<main>` (7.12) — hecho.
- [x] Eliminar sistema de Status de Usuario completo según la tabla de 7.13 (incluye migración de columna, comando, listener, componente Livewire y prop de Avatar) — hecho. Se encontraron y limpiaron también dos usos no documentados originalmente: el filtro "Estado" y la columna de status en `admin/users/index.blade.php` y `app/users/index.blade.php` (con sus `StatusFilter`, entradas en `TenantUserTableConfig`/`AdminUserTableConfig`, y los helpers `statusColor()`/`statusLabel()` en ambos `UserIndex.php`).


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

### Contexto y Decisión de Diseño

El motivador original de esta fase fue una molestia de uso propio (acumulación de toasts completos apilados, sin agrupar). Antes de codificar se hizo un análisis dual: (1) desde la experiencia de un usuario avanzado de ERPs, y (2) desde el usuario real de Orvian (directores, maestros, padres), cuyo perfil técnico es mixto — no homogéneamente experto ni homogéneamente novato — y que interactúa **solo por tap**, sin `hover` disponible en móvil. Ese segundo ángulo impone dos restricciones duras sobre cualquier solución:

1. Cualquier affordance visual (que "hay más toasts") debe ser **auto-explicativo por diseño**, no dependiente de descubrir un gesto o de un tooltip por hover.
2. El mecanismo de descarte principal debe seguir siendo el botón "X" (tap directo, cero curva de aprendizaje). Gestos como swipe son una mejora secundaria, no el único camino.

Referencia de patrón: EasyPanel apila hasta 3 toasts completos en cascada (con las tarjetas de atrás asomando ligeramente, lo cual ya comunica visualmente "hay más" sin texto ni hover) y agrupa el resto a partir del 4to en un chip contador.

### 10.1 — Stack Visual en Cascada (máx. 3 + agrupación)

Se muestran hasta **3 toasts completos** en cascada (offset visual decreciente, bordes de las tarjetas de atrás visibles — ese asomo es el affordance, no requiere hover ni texto). Del 4to toast en adelante, se agrupan en un chip contador (`+N`) al pie de la pila.

```js
Alpine.data('toastManager', () => ({
    toasts: [],

    get cascadeToasts() { return this.toasts.slice(-3); },       // hasta 3 tarjetas completas, en cascada
    get overflowCount()  { return Math.max(0, this.toasts.length - 3); }, // resto agrupado
    // addToast, saveForRedirect, removeToast — sin cambios
}));
```

**Interacción del chip `+N`:** un tap descarta *todos* los toasts agrupados de una vez ("Descartar todo"). Se decide intencionalmente **no** abrir una lista expandible — resolvería la acumulación pero reintroduciría la misma fricción que motivó esta fase (obligar a leer/cerrar uno por uno). Si en el futuro se necesita revisar el historial de toasts, debe ser una vista aparte, no una expansión inline del stack.

### 10.2 — Timers y Política de Duración por Tipo

| Tipo | Duración | Razón |
| :--- | :--- | :--- |
| `success` / `info` | 5000ms | Sin cambio — mensajes de confirmación, lectura rápida |
| `warning` | 7000ms | Uso poco frecuente pero requiere más tiempo de lectura que un success |
| `error` | 10000ms | Se usa mayormente en fallos de request (POST/GET); tiempo suficiente para leer sin necesidad de cerrarlo manualmente |

Los valores son **fijos por tipo**, no dinámicos según longitud del mensaje — evita complejidad innecesaria y mantiene el comportamiento predecible.

**Regla de pausa por posición en la cascada:** el countdown de un toast solo corre mientras es una de las 3 tarjetas visibles en cascada. Si un toast queda agrupado en el chip `+N` (por llegar toasts nuevos encima), su timer se pausa y no se reanuda — permanece agrupado hasta que el usuario lo descarte vía el chip, nunca expira solo estando oculto. Esto evita que un error con duración larga desaparezca sin haber sido leído, que era justamente el problema que la duración extendida buscaba resolver.

### 10.3 — Swipe-to-Dismiss (gesto secundario, no reemplaza el botón "X")

```js
Alpine.data('toastItem', (toast) => ({
    touchStartX: 0, touchStartY: 0,
    swipeOffset: 0,
    swipeAxis: null, // 'x' | 'y' | null — se determina en el primer movimiento

    onSwipeStart(e) {
        this.touchStartX = e.touches[0].clientX;
        this.touchStartY = e.touches[0].clientY;
        this.swipeAxis = null;
        this.pause();
    },
    onSwipeMove(e) {
        const dx = e.touches[0].clientX - this.touchStartX;
        const dy = e.touches[0].clientY - this.touchStartY;

        if (!this.swipeAxis) {
            // Determina el eje dominante una sola vez, para no competir con scroll vertical
            this.swipeAxis = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
        }
        if (this.swipeAxis === 'x') {
            this.swipeOffset = dx; // ambos sentidos permitidos (izquierda y derecha)
        }
    },
    onSwipeEnd() {
        if (Math.abs(this.swipeOffset) > 100) this.close();
        else { this.swipeOffset = 0; this.resume(); }
    },
    get swipeStyle() {
        return this.swipeOffset !== 0
            ? `transform: translateX(${this.swipeOffset}px); opacity: ${1 - Math.abs(this.swipeOffset) / 200}; transition: none;`
            : 'transition: transform 0.3s ease, opacity 0.3s ease;';
    },
}));
```

El botón "X" (tap) sigue siendo el mecanismo de descarte principal y documentado; swipe es un atajo opcional para quien lo descubra. La detección de eje dominante (`swipeAxis`) evita que un scroll vertical cerca del toast se interprete como un swipe horizontal parcial.

### 10.4 — Redundancia con Errores de Formulario Inline

El bloque de ingesta de `$errors->any()` (ver `resources/views/components/ui/toasts.blade.php`) dispara hoy un toast genérico "Error de validación" en **todo** envío de formulario con errores. Esto duplica la información que `x-ui.forms.input`/`select`/`textarea` ya muestran inline bajo cada campo (ver `docs/ui/ui-forms.md`, sección "Props de Mensaje") — el usuario ve el mismo error dos veces, una en el campo y otra en el toast.

**Cambio:** limitar el toast automático de validación a los casos donde el campo con error no está visible en el formulario actual (ej. un paso anterior de un wizard, un tab no activo, o un campo fuera del viewport de un formulario largo). Si todos los errores corresponden a campos visibles en el mismo formulario, no se dispara el toast — el error inline es suficiente.

### 10.5 — Refinamiento Visual

```blade
{{-- ANTES --}}
class="relative w-full max-w-sm overflow-hidden rounded-lg border-l-4 shadow-xl transition-all pointer-events-auto bg-white"

{{-- DESPUÉS --}}
class="relative w-full max-w-sm overflow-hidden rounded-xl border-l-[3px] shadow-lg transition-all pointer-events-auto bg-white dark:bg-dark-card"
```

### 10.6 — Documentación

Actualizar `docs/ui/toast.md` con descripción de eventos (`@notify`, `@notify-redirect`, `@remove-toast`), formato del payload, ejemplos de uso desde Livewire y Alpine, comportamiento de la cascada (3 + agrupación), política de duración por tipo, swipe como gesto secundario, y guía de integración con sesiones PHP.

### 10.7 — Rutas de Showcase del UI Kit (`/demo/*`)

`resources/views/examples/` ya contenía varios showcases interactivos (`toast-components-demo`, `module-icons-demo`, `form-components-demo`, `badge-components-demo`, `button-components-demo`), pero solo un par tenían su ruta realmente registrada. Se registran todas en `routes/web.php`, agrupadas y **restringidas a `app()->environment('local')`** — son páginas de desarrollo interno, no deben quedar accesibles en producción:

```php
// routes/web.php

// ── Showcase del UI Kit (solo entorno local) ────────────────────────
if (app()->environment('local')) {
    Route::view('/demo/toasts', 'examples.toast-components-demo');
    Route::view('/demo/module-icons', 'examples.module-icons-demo');
    Route::view('/demo/form', 'examples.form-components-demo');
    Route::view('/demo/badges', 'examples.badge-components-demo');
    Route::view('/demo/buttons', 'examples.button-components-demo');
}
```

| Ruta | Vista | Componente |
| :--- | :--- | :--- |
| `/demo/toasts` | `examples.toast-components-demo` | `x-ui.toasts` (esta fase) |
| `/demo/module-icons` | `examples.module-icons-demo` | Íconos de módulo (REQ-07.10) |
| `/demo/form` | `examples.form-components-demo` | `x-ui.forms.*` |
| `/demo/badges` | `examples.badge-components-demo` | `x-ui.badge` |
| `/demo/buttons` | `examples.button-components-demo` | `x-ui.button` |

---

## Fase 11 — Correcciones de UI Kit y Unificación de Layout

**Rama:** `feature/v0.9.0-ui-kit-fixes` (reemplaza a `feature/v0.9.0-button-loading-fix` — el alcance creció más allá del botón)

**Objetivo:** Además de la corrección original de `wire:loading` (REQ-11.1), esta fase absorbe dos hallazgos adicionales detectados durante la validación manual de v0.9.0: (1) el parpadeo por FOUC de Alpine.js en elementos que usan `x-show`/`x-data` sin `x-cloak` (REQ-11.2), y (2) la constatación de que, tras eliminar el navbar horizontal en la Fase 7, `layouts/app-module.blade.php` quedó prácticamente idéntico a `layouts/app.blade.php` — se unifican en un solo layout (REQ-11.3).

---

### 11.1 — Corrección de `wire:loading` global en `x-ui.button`

**Diagnóstico (confirmado en código):** `resources/views/components/ui/button.blade.php:44` sigue teniendo `'wire:loading.class' => 'opacity-60 pointer-events-none'` en el `$attributes->merge()`, sin `wire:target`. Esto reactiva la clase en **cualquier** botón de la página ante **cualquier** request Livewire en curso, no solo el que originó la acción — de ahí el parpadeo de botones no relacionados.

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

**Alcance real (auditado, no estimado):** `<x-ui.button` aparece en **56 vistas**. De esas, **46** combinan el componente con al menos un `wire:click` en el mismo archivo. De esas 46, **13 no tienen ningún `wire:loading` propio todavía** — es decir, hoy dependen por completo del comportamiento global que se va a eliminar, y se quedarán **sin ningún feedback de carga** si no se les agrega el patrón opt-in de arriba:

| Archivo |
| :--- |
| `resources/views/components/ui/plan-card.blade.php` |
| `resources/views/components/ui/empty-state.blade.php` |
| `resources/views/livewire/app/settings/school-partials/_danger-zone.blade.php` |
| `resources/views/livewire/app/attendance/shift-window-manager.blade.php` |
| `resources/views/livewire/app/attendance/manual-attendance.blade.php` |
| `resources/views/livewire/app/attendance/attendance-session-hub.blade.php` |
| `resources/views/livewire/app/academic/course-show.blade.php` |
| `resources/views/livewire/app/academic/teachers/teacher-assignments.blade.php` |
| `resources/views/livewire/auth/register-install.blade.php` |
| `resources/views/livewire/admin/plans/plan-features.blade.php` |
| `resources/views/livewire/tenant/wizard/_intro.blade.php` |
| `resources/views/livewire/tenant/wizard/steps/_step-3-academic.blade.php` |
| `resources/views/livewire/tenant/wizard/steps/_step-4-plan.blade.php` |

No todos requieren necesariamente feedback de carga (algunos son navegación simple o toggles instantáneos) — esta lista es el punto de partida para decidir, botón por botón, cuáles sí lo necesitan antes de mergear el cambio.

---

### 11.2 — Eliminar FOUC de Alpine.js mediante `x-cloak`

**Diagnóstico:** Alpine.js evalúa `x-show`/`x-data` después de que el HTML ya se pintó, así que cualquier elemento con `x-show="false"` (o que depende de un valor inicial de Alpine) se ve brevemente en su estado "crudo" antes de que Alpine lo oculte — el clásico *Flash of Unchanged Content*. La regla CSS que lo previene:

```css
[x-cloak] { display: none !important; }
```

**ya existe** en `resources/css/app.css:7` — el proyecto simplemente no está usando el atributo `x-cloak` donde más FOUC produce. Ya hay 22 archivos que sí usan `x-cloak` correctamente (ej. `resources/views/components/navbar/layout.blade.php`), así que el patrón está establecido; falta aplicarlo en los layouts raíz y en el toast.

**Archivos a actualizar (agregar `x-cloak` a los elementos `x-show`):**

| Archivo | Elemento a marcar |
| :--- | :--- |
| `resources/views/layouts/app.blade.php` | Overlay del sidebar móvil (`x-show="sidebarOpen"`) |
| `resources/views/layouts/app-module.blade.php` | Overlay del sidebar móvil (`x-show="sidebarOpen"`) — ver 11.3, este archivo se elimina y el fix se hereda del unificado |
| `resources/views/components/ui/toasts.blade.php` | Revisar si algún elemento con `x-show` sin cloak parpadea al primer render (ej. el chip `+N más`) |
| `docs/ui/toast.md` | Documentar la recomendación de `x-cloak` para quien extienda el componente |

No hace falta tocar `resources/css/app.css` — la regla ya está. Sí conviene revisar, de forma general, cualquier otro `x-show` del proyecto que no esté en la lista de los 22 archivos ya conformes, por si aparecen más casos de FOUC no reportados aún.

---

### 11.3 — Unificación de `layouts.app-module` **y** `components.admin` en `layouts.app`

**Contexto:** En la Fase 7 (REQ-07.1) se reemplazó el navbar horizontal de escuela por el Sidebar, y `layouts/app-module.blade.php` pasó a compartir exactamente el mismo shell (`sidebar-app` + `x-navbar.layout`) que `layouts/app.blade.php` ya usaba para el Hub. Comparando ambos archivos, la diferencia real está en el `<main>`:

| Diferencia | `layouts/app.blade.php` (Hub) | `layouts/app-module.blade.php` (módulos) |
| :--- | :--- | :--- |
| Wrapper del `<main>` | `flex-1 overflow-y-auto ... relative` con `<div class="relative z-10 flex flex-col items-center py-12 md:py-16 px-4 sm:px-6">` | `flex-1 overflow-y-auto ... flex flex-col` |
| Breadcrumbs | No tiene | `<div class="p-4 md:p-6 pb-0"><x-navbar.breadcrumbs /></div>` |
| Footer | No tiene | `<x-ui.footer />` |
| Título por defecto | `Hub` | `App` |
| `@stack('scripts')` | No tiene | Sí, después de `@livewireScripts` |

**Ampliación del alcance:** revisando `resources/views/components/admin.blade.php` (el layout de SuperAdmin), su `<main>` es prácticamente idéntico al de `app-module.blade.php` (breadcrumbs + footer), así que se suma a la unificación. Ojo: **no es solo el título** — hay tres diferencias, no una:

| Diferencia | `components/admin.blade.php` (SuperAdmin) | `layouts/app-module.blade.php` (escuela) |
| :--- | :--- | :--- |
| Sidebar incluido | `@include('layouts.sidebar')` — navegación fija de SuperAdmin (escuelas, usuarios globales, planes, roles, Pulse, Log Viewer) | `@include('layouts.sidebar-app')` — navegación generada desde `config('modules')`, filtrada por permisos del usuario |
| Título | `{{ $title ?? config('app.name') }} \| SuperAdmin` | `{{ $title ?? 'App' }} \| {{ config('app.name') }}` |
| Wrapper del `<main>` | `<div class="flex-1 p-4 md:p-6 pb-4 md:pb-4 relative">` + breadcrumbs + `<div class="animate-fade-in">{{ $slot }}</div>` + footer | `<div class="p-4 md:p-6 pb-0">` breadcrumbs, luego `<div class="flex-1">{{ $slot }}</div>` sin animación, luego footer |

El include del sidebar **sí es una diferencia funcional real** (contenido de navegación distinto, no una variante cosmética) — hay que resolverla con una condición en el layout unificado, no asumir que solo cambia el título.

**Plan actualizado:** un único `layouts/app.blade.php` sirve a Hub, módulos de escuela y SuperAdmin, decidiendo por contexto:

```blade
{{-- Sidebar: la misma condición que ya usa Profile.php (`request()->routeIs('admin.profile')`) --}}
@include(request()->routeIs('admin.*') ? 'layouts.sidebar' : 'layouts.sidebar-app')
...
<title>{{ $title ?? config('app.name') }} | {{ request()->routeIs('admin.*') ? 'SuperAdmin' : config('app.name') }}</title>
```

El wrapper del `<main>` adopta el tratamiento más completo (padding + `animate-fade-in` + breadcrumbs + footer) que hoy solo tiene `components/admin.blade.php`, aplicado a los tres contextos por igual.

`resources/views/layouts/app-module.blade.php`, `app/View/Components/AppModuleLayout.php` **y** `resources/views/components/admin.blade.php` se eliminan.

**Nota sobre `AppModuleLayout.php`:** no se encontró ningún uso de `<x-app-module-layout>` en todo el proyecto — la clase existe pero no se invoca como componente Blade en ningún lado. Se puede eliminar sin reemplazo, no hay que "migrarla". (`components/admin.blade.php` sí se invoca, vía el atributo `#[Layout('components.admin')]` en los 7 componentes admin de abajo — a diferencia de `AppModuleLayout.php`, no es código muerto, solo queda redundante tras la unificación).

**Hallazgo adicional — simplificar el mecanismo de layout con el atributo `#[Layout(...)]`:** los 29 componentes de escuela fijan el layout así:

```php
public function render()
{
    /** @var \Livewire\Features\SupportPageComponents\View $view */
    $view = view('livewire.app.settings.school-settings');

    return $view->layout('layouts.app-module');
}
```

El comentario `/** @var ... View $view */` es un parche para que intelephense no marque el tipo de retorno de `view()` como incompatible con `->layout()` — de ahí el diagnóstico `P1131` que arrastran estos 29 archivos. Ahora que REQ-07.3 (Fase 7) ya quitó el segundo argumento `config('modules.*')` de estas llamadas a `->layout()`, no queda ninguna razón para seguir fijando el layout dentro de `render()`: Livewire 3 soporta el atributo de clase `#[Layout('layouts.app')]` (`use Livewire\Attributes\Layout;`), que ya se usa consistentemente en los 7 componentes de `app/Livewire/Admin/*` (ninguno de ellos usa el patrón `render()`+`->layout()`). Adoptar el mismo atributo en el lado de escuela:

- Elimina la llamada `->layout(...)` y el docblock `@var` que dispara `P1131` — en los **29 archivos**, no solo en `SchoolSettings.php`.
- En los componentes donde `render()` no hace nada más que devolver la vista (ej. `SchoolSettings.php`, `CourseIndex.php`), `render()` se reduce a `return view('livewire.app.settings.school-settings');` — a verificar caso por caso si incluso puede omitirse por completo apoyándose en la resolución de vista por convención de Livewire.
- En los componentes que sí pasan datos a la vista (ej. `AttendanceDashboard.php`, que arma `shifts`, `sections`, `calendarDays`, `calendarLabel`), `render()` se conserva pero sin el paso intermedio de `$view->layout(...)`: `return view('livewire.app.attendance.attendance-dashboard', [...]);`.

**Efecto sobre los 3 archivos con layout condicional:** `Profile.php`, `RoleForm.php` y `RolePermissions.php` eligen hoy entre `'components.admin'` y `'layouts.app-module'` según `$isAdmin`/`$isGlobal`. Con la unificación completa (admin + escuela = el mismo `layouts.app`), **ambas ramas del condicional resuelven al mismo valor** — la lógica de selección de layout queda muerta y se reemplaza por un simple `#[Layout('layouts.app')]` de clase. La propiedad `$isAdmin`/`$isGlobal` en sí **no se elimina**: en `Profile.php` se sigue usando en otras partes del componente (líneas 67 y 82, para lógica ajena al layout).

**Archivos a modificar — cambiar `'layouts.app-module'` por `'layouts.app'`, y migrar de `render()`+`->layout()` a `#[Layout('layouts.app')]` (29 archivos):**

`app/Livewire/App/Academic/BiometricKiosk.php` · `CourseForm.php` · `CourseIndex.php` · `CourseShow.php` · `EnrollmentHub.php` · `Students/StudentForm.php` · `Students/StudentImportWizard.php` · `Students/StudentIndex.php` · `Students/StudentPrintManager.php` · `Students/StudentShow.php` · `Teachers/TeacherAssignments.php` · `Teachers/TeacherForm.php` · `Teachers/TeacherIndex.php` · `Teachers/TeacherShow.php` · `app/Livewire/App/Attendance/AttendanceAudit.php` · `AttendanceDashboard.php` · `AttendanceReports.php` · `AttendanceSessionHub.php` · `AttendanceSessionManager.php` · `ClassroomAttendanceHistory.php` · `ClassroomAttendanceLive.php` · `ExcuseForm.php` · `ExcuseIndex.php` · `ManualAttendance.php` · `PlantelAttendanceIndex.php` · `ShiftWindowManager.php` · `app/Livewire/App/Roles/RoleIndex.php` · `app/Livewire/App/Settings/SchoolSettings.php` · `app/Livewire/App/Users/UserIndex.php`

**Archivos a modificar — layout condicional colapsa a `#[Layout('layouts.app')]` fijo, `$isAdmin`/`$isGlobal` se conserva para el resto de su lógica (3 archivos):**

`app/Livewire/Shared/Profile.php` · `app/Livewire/Shared/Roles/RoleForm.php` · `app/Livewire/Shared/Roles/RolePermissions.php`

**Archivos a modificar — cambiar el string del atributo existente `#[Layout('components.admin')]` → `#[Layout('layouts.app')]` (7 archivos, ya usan el patrón de atributo, cambio trivial):**

`app/Livewire/Admin/Users/UserIndex.php` · `app/Livewire/Admin/Dashboard/StatsOverview.php` · `app/Livewire/Admin/Schools/SchoolIndex.php` · `app/Livewire/Admin/Schools/SchoolShow.php` · `app/Livewire/Admin/Plans/PlanFeatures.php` · `app/Livewire/Admin/Plans/PlanIndex.php` · `app/Livewire/Admin/Roles/RoleIndex.php`

**Archivos a eliminar:**

- `resources/views/layouts/app-module.blade.php`
- `resources/views/components/admin.blade.php`
- `app/View/Components/AppModuleLayout.php`

**Limpieza menor (no funcional):** `resources/views/components/sidebar/layout.blade.php` tiene un comentario que menciona `layouts/app-module.blade.php` como ejemplo — actualizar la referencia a `layouts/app.blade.php` al pasar por el archivo.

**Verificación pendiente tras implementar:** confirmar que (1) el parpadeo de botones desaparece con el fix de `wire:loading`, (2) no hay FOUC visible al cargar cualquier vista con `x-cloak` aplicado, (3) toda vista que antes usaba `layouts.app-module` o `components.admin` renderiza igual (sidebar correcto según contexto, título correcto, breadcrumbs, footer) bajo el `layouts.app` unificado, y (4) el diagnóstico intelephense `P1131` desaparece en los 29 archivos migrados al atributo `#[Layout(...)]`.

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
| `resources/views/layouts/sidebar-app.blade.php` | Crear — Sidebar de escuela análogo a `layouts/sidebar.blade.php` | 7.1 |
| `resources/views/layouts/app-module.blade.php` | Reemplazar navbar horizontal por Sidebar; agregar `<x-navbar.breadcrumbs />` | 7.1, 7.2 |
| `resources/views/layouts/app.blade.php` | Reemplazar Hub sin sidebar por el mismo shell (Sidebar + `<x-navbar.layout />`), conserva su `<style>` propio | 7.1 |
| `resources/views/components/app/module-toolbar.blade.php` | `sticky top-[52px]` → `sticky top-0` (el navbar ya no es `fixed`, efecto colateral necesario de 7.1) | 7.1 |
| `app/Livewire/App/**` (32 componentes) | Quitar segundo argumento `config('modules.*')` de `->layout()` | 7.3 |
| `app/Services/Navigation/GlobalSearchService.php` | `index()` (scan de rutas + cache 24h) y `forUser()` (filtro por permisos) — ver detalle en 7.4 | 7.4 |
| `routes/app/*.php` (6 archivos) | `->defaults('navigationSearch', [...])` en 25 rutas indexables | 7.4 |
| `resources/views/components/navbar/layout.blade.php` | Buscador funcional (desktop + modal móvil), consume el índice de `GlobalSearchService` | 7.4 |
| `resources/views/app/dashboard.blade.php` | Rediseño: resumen operativo + accesos recientes + pendientes accionables, no grilla de módulos | 7.5 |
| `resources/views/components/application-logo.blade.php` | Resolución de logo por `school_id` → `logo_path`, con fallback a ORVIAN | 7.6 |
| `resources/views/components/sidebar/layout.blade.php` | Enlace "Mi Perfil" → `route('app.profile')` / `route('admin.profile')`; quitar `@livewire('shared.user-status')` | 7.7, 7.13 |
| `app/Livewire/Shared/ProfileModal.php` + `livewire/shared/profile-modal.blade.php` | Dejar de referenciar (dead code intencional, no eliminar) | 7.7 |
| `resources/views/components/app/navbar.blade.php` | Quitar `$dispatch('open-modal', 'profile-modal')`, enlazar a `route('app.profile')` | 7.7 |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | `create()` deja de leer cookie `orvian_login_version`, siempre `auth.login` | 7.8 |
| `resources/views/livewire/shared/profile.blade.php` | Quitar selector "Interfaz de Acceso" (login v1/v2) y checkbox "Colapsar menú lateral" | 7.8, 7.9 |
| `app/Livewire/Shared/Profile.php` | Quitar `$loginVersion`, `$sidebar_collapsed` y su guardado/cookie en `savePreferences()` | 7.8, 7.9 |
| `resources/views/components/admin.blade.php` | `sidebarOpen` inicial desde `localStorage` en vez de preferencia PHP | 7.9 |
| `resources/views/components/sidebar/item.blade.php` + `sidebar/dropdown.blade.php` | Soporte de prop `moduleIcon` vía `x-ui.module-icon` | 7.10 |
| `resources/views/components/navbar/layout.blade.php` | Quitar tooltip de sidebar y botón fullscreen; buscador con `x-ui.forms.input` + `x-modal` | 7.11 |
| `resources/views/components/sidebar/layout.blade.php` | Hover colapsado en desktop → overlay (`fixed`/`absolute`) sin empujar `<main>`, sin backdrop oscuro | 7.12 |
| `database/migrations/xxxx_remove_status_from_users_table.php` | Crear migración — elimina columna `status` de `users` | 7.13 |
| `app/Models/User.php` | Quitar `'status'` de `$fillable` | 7.13 |
| `app/Livewire/Shared/UserStatus.php` + `livewire/shared/user-status.blade.php` | **Eliminar** | 7.13 |
| `app/Console/Commands/UpdateUserStatus.php` | **Eliminar** | 7.13 |
| `routes/console.php` | Quitar `Schedule::command('orvian:update-user-status')` | 7.13 |
| `app/Listeners/UpdateUserStatusListener.php` | **Eliminar** (+ revisar registro de eventos Login/Logout si queda vacío) | 7.13 |
| `app/View/Components/Ui/Avatar.php` + `components/ui/avatar.blade.php` | Quitar prop `showStatus` y toda la lógica/UI de color de estado | 7.13 |
| `resources/views/errors/403.blade.php` | Crear página de error | 9 |
| `resources/views/errors/404.blade.php` | Crear página de error | 9 |
| `resources/views/errors/500.blade.php` | Crear página de error | 9 |
| `resources/views/components/ui/toasts.blade.php` | Stack visual + swipe-to-dismiss | 10 |
| `docs/ui/toast.md` | Actualizar documentación | 10 |
| `resources/views/components/ui/button.blade.php` | Eliminar `wire:loading.class` global | 11.1 |
| `docs/ui/buttons.md` | Actualizar sección de estados de carga | 11.1 |
| 13 vistas sin `wire:loading` propio (ver detalle en 11.1) | Agregar patrón opt-in donde aplique feedback de carga | 11.1 |
| `resources/views/layouts/app.blade.php` | Agregar `x-cloak` al overlay del sidebar; absorbe breadcrumbs/footer/`@stack('scripts')` del layout unificado | 11.2, 11.3 |
| `resources/views/components/ui/toasts.blade.php` | Revisar/agregar `x-cloak` en elementos `x-show` | 11.2 |
| `docs/ui/toast.md` | Documentar recomendación de `x-cloak` | 11.2 |
| `resources/views/layouts/app-module.blade.php` | **🗑️ ELIMINAR** — unificado en `layouts/app.blade.php` | 11.3 |
| `app/View/Components/AppModuleLayout.php` | **🗑️ ELIMINAR** — sin uso como componente Blade | 11.3 |
| 29 componentes Livewire con `->layout('layouts.app-module')` (ver detalle en 11.3) | Cambiar a `->layout('layouts.app')` | 11.3 |
| `app/Livewire/Shared/Profile.php`, `Shared/Roles/RoleForm.php`, `Shared/Roles/RolePermissions.php` | Cambiar rama `else` del layout condicional a `'layouts.app'` | 11.3 |
| `resources/views/components/sidebar/layout.blade.php` | Actualizar comentario que referencia `layouts/app-module.blade.php` | 11.3 (cosmético) |

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
