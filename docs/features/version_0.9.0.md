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
| REQ-01 | 1 | Asistencia Biométrica | API Gateway para el Kiosko: rutas `/api/v1/kiosk/` protegidas por Sanctum | Alta | Pendiente |
| REQ-02 | 2 | Asistencia Biométrica | Arquitectura del `orvian-desktop-scanner`: app Python nativa con OpenCV + MediaPipe | Alta | Pendiente |
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

## Fase 2 — Arquitectura del `orvian-desktop-scanner` (REQ-02)

**Repositorio:** `orvian-desktop-scanner` (independiente de `orvian`)

**Rama inicial:** `main`

### 2.1 — Visión General

`orvian-desktop-scanner` es una aplicación de escritorio nativa para Windows (con posible extensión a Linux/macOS en el futuro) que reemplaza completamente al kiosko web. Se instala en el dispositivo físico de portería como una aplicación independiente. Al iniciarse, se conecta al servidor ORVIAN mediante el Token de Kiosko configurado y opera de forma autónoma.

```
┌─────────────────────────────────────────────────────────┐
│             orvian-desktop-scanner (Python)              │
│                                                          │
│  ┌──────────────┐    ┌──────────────┐   ┌────────────┐  │
│  │  Camera      │    │  Strategy    │   │  UI Layer  │  │
│  │  Manager     │───▶│  Context     │──▶│ (CTk)      │  │
│  │  (OpenCV)    │    │              │   │            │  │
│  └──────────────┘    └──────┬───────┘   └────────────┘  │
│                             │                            │
│              ┌──────────────┼──────────────┐             │
│              ▼              ▼              ▼             │
│     ┌──────────────┐ ┌──────────┐ ┌──────────────┐      │
│     │ FacialStrategy│ │QrStrategy│ │(FutureStrategy│     │
│     │(MediaPipe)   │ │(pyzbar)  │ │ Fingerprint) │      │
│     └──────────────┘ └──────────┘ └──────────────┘      │
│                             │                            │
│                    ┌────────▼───────┐                    │
│                    │ ApiClient      │                    │
│                    │ (httpx/requests│                    │
│                    │  + Sanctum)    │                    │
│                    └────────────────┘                    │
└─────────────────────────────────────────────────────────┘
                             │
                    HTTPS + Bearer Token
                             │
                    ┌────────▼───────┐
                    │  Laravel API   │
                    │  /api/v1/kiosk/│
                    └────────────────┘
```

### 2.2 — Stack Tecnológico

| Componente | Librería | Justificación |
| :--- | :--- | :--- |
| Captura de cámara | `opencv-python` | Control total sobre el stream, FPS y resolución; sin restricciones de browser |
| Detección facial | `mediapipe` (Python nativo) | Rendimiento superior al WASM; acceso a GPU si disponible |
| Lectura QR | `pyzbar` + `python-zxing` (fallback) | `pyzbar` envuelve `zbar`, la librería C más rápida para QR; procesamiento directo sobre `numpy array` del frame |
| UI / Ventana | `customtkinter` | Widgets modernos sobre Tkinter; sin dependencias de Electron o navegador |
| HTTP Client | `httpx` (async) | Soporte nativo async/await; multipart para envío de imágenes |
| Audio | `pygame.mixer` | Reproducción de WAV sin overhead; ya distribuido en la mayoría de entornos Python |
| Empaquetado | `PyInstaller` | Genera `.exe` standalone para Windows sin requerir Python instalado |
| Auto-update | Script `launcher.py` personalizado | Ver sección 2.8 |

### 2.3 — Estructura del Repositorio

```
orvian-desktop-scanner/
├── launcher.py                 # Script de arranque con verificación de versión
├── main.py                     # Punto de entrada de la aplicación
├── config.py                   # Carga de configuración desde config.json
├── config.json                 # Token, URL del servidor, preferencias locales
│
├── core/
│   ├── api_client.py           # Cliente HTTP para /api/v1/kiosk/
│   ├── camera_manager.py       # Gestión del stream OpenCV
│   └── audio_manager.py        # Reproducción de feedback de audio
│
├── strategies/
│   ├── base_strategy.py        # Clase abstracta ScannerStrategy
│   ├── facial_strategy.py      # Implementación con MediaPipe
│   └── qr_strategy.py          # Implementación con pyzbar
│
├── ui/
│   ├── kiosk_window.py         # Ventana principal CustomTkinter
│   └── widgets/
│       ├── camera_feed.py      # Widget de preview de cámara
│       └── result_overlay.py   # Overlay de resultado (nombre + estado)
│
├── assets/
│   ├── sounds/
│   │   ├── success.wav
│   │   └── error.wav
│   └── icons/
│       └── orvian.ico
│
├── build/
│   └── orvian-scanner.spec     # Configuración PyInstaller
│
├── requirements.txt
└── README.md
```

### 2.4 — Patrón Strategy para Módulos de Escaneo

El escáner se diseña bajo el **Patrón Strategy** (GoF). El `ScannerContext` delega el procesamiento de cada frame a una estrategia concreta intercambiable, sin conocer los detalles de implementación. Esto garantiza que agregar un módulo futuro (ej. Lector de Huella Digital con `pyfingerprint`) no requiera modificar el núcleo de la aplicación.

#### Clase Abstracta Base

```python
# strategies/base_strategy.py

from abc import ABC, abstractmethod
from dataclasses import dataclass
from typing import Optional
import numpy as np

@dataclass
class ScanResult:
    """Resultado normalizado de cualquier estrategia de escaneo."""
    detected: bool
    payload: Optional[str] = None    # QR code string o encoding facial serializado
    confidence: Optional[float] = None
    frame_annotated: Optional[np.ndarray] = None  # Frame con overlays visuales


class ScannerStrategy(ABC):
    """
    Interfaz común para todos los módulos de escaneo.
    Cada estrategia procesa un frame de OpenCV y retorna un ScanResult.
    """

    @abstractmethod
    def process_frame(self, frame: np.ndarray) -> ScanResult:
        """
        Analiza el frame y retorna el resultado del intento de detección.
        Debe ser no bloqueante. El estado de dwell-time o cooldown
        se gestiona internamente por cada estrategia.
        """
        ...

    @abstractmethod
    def reset(self) -> None:
        """Reinicia el estado interno (dwell timer, cooldown, etc.)."""
        ...

    @abstractmethod
    def release(self) -> None:
        """Libera recursos de hardware o modelos cargados en memoria."""
        ...
```

#### Contexto del Escáner

```python
# core/scanner_context.py

from strategies.base_strategy import ScannerStrategy, ScanResult
import numpy as np


class ScannerContext:
    """
    Mantiene una referencia a la estrategia activa y delega el procesamiento.
    La UI nunca instancia estrategias directamente.
    """

    def __init__(self, strategy: ScannerStrategy) -> None:
        self._strategy = strategy

    def set_strategy(self, strategy: ScannerStrategy) -> None:
        self._strategy.release()
        self._strategy = strategy
        self._strategy.reset()

    def process_frame(self, frame: np.ndarray) -> ScanResult:
        return self._strategy.process_frame(frame)

    def reset(self) -> None:
        self._strategy.reset()
```

#### Estrategia Facial (MediaPipe)

```python
# strategies/facial_strategy.py

import time
import numpy as np
import mediapipe as mp
from mediapipe.tasks import python as mp_python
from mediapipe.tasks.python import vision as mp_vision

from .base_strategy import ScannerStrategy, ScanResult

DWELL_REQUIRED_MS = 1200   # ms de cara estable antes de capturar
MIN_DETECTION_CONFIDENCE = 0.6


class FacialStrategy(ScannerStrategy):

    def __init__(self, model_path: str) -> None:
        options = mp_vision.FaceDetectorOptions(
            base_options=mp_python.BaseOptions(model_asset_path=model_path),
            running_mode=mp_vision.RunningMode.IMAGE,
            min_detection_confidence=MIN_DETECTION_CONFIDENCE,
        )
        self._detector = mp_vision.FaceDetector.create_from_options(options)
        self._dwell_start: Optional[float] = None

    def process_frame(self, frame: np.ndarray) -> ScanResult:
        rgb = mp.Image(image_format=mp.ImageFormat.SRGB, data=frame)
        result = self._detector.detect(rgb)

        annotated = frame.copy()

        if len(result.detections) != 1:
            self._dwell_start = None
            return ScanResult(detected=False, frame_annotated=annotated)

        detection = result.detections[0]
        bbox = detection.bounding_box

        # Dibujar bounding box en el frame
        self._draw_bbox(annotated, bbox)

        now = time.time() * 1000  # ms
        if self._dwell_start is None:
            self._dwell_start = now

        elapsed = now - self._dwell_start
        progress = min(elapsed / DWELL_REQUIRED_MS, 1.0)

        if progress >= 1.0:
            self._dwell_start = None
            return ScanResult(
                detected=True,
                payload=None,   # El payload es la imagen completa, enviada por el caller
                confidence=detection.categories[0].score if detection.categories else None,
                frame_annotated=annotated,
            )

        return ScanResult(detected=False, frame_annotated=annotated)

    def _draw_bbox(self, frame: np.ndarray, bbox) -> None:
        import cv2
        color = (16, 185, 129)  # Esmeralda ORVIAN
        cv2.rectangle(
            frame,
            (bbox.origin_x, bbox.origin_y),
            (bbox.origin_x + bbox.width, bbox.origin_y + bbox.height),
            color, 2
        )

    def reset(self) -> None:
        self._dwell_start = None

    def release(self) -> None:
        self._detector.close()
```

#### Estrategia QR (pyzbar)

```python
# strategies/qr_strategy.py

import time
import numpy as np
from pyzbar.pyzbar import decode as pyzbar_decode

from .base_strategy import ScannerStrategy, ScanResult

COOLDOWN_MS = 3000  # ms de cooldown tras detección exitosa


class QrStrategy(ScannerStrategy):

    def __init__(self) -> None:
        self._last_detection_time: Optional[float] = None

    def process_frame(self, frame: np.ndarray) -> ScanResult:
        now = time.time() * 1000

        if self._last_detection_time and (now - self._last_detection_time) < COOLDOWN_MS:
            return ScanResult(detected=False)

        codes = pyzbar_decode(frame)
        if not codes:
            return ScanResult(detected=False)

        code = codes[0]
        data = code.data.decode('utf-8')

        self._last_detection_time = now

        return ScanResult(detected=True, payload=data)

    def reset(self) -> None:
        self._last_detection_time = None

    def release(self) -> None:
        pass  # pyzbar no mantiene recursos persistentes
```

### 2.5 — Cliente HTTP (`ApiClient`)

```python
# core/api_client.py

import httpx
from pathlib import Path
from typing import Optional
import numpy as np
import cv2


class ApiClient:

    def __init__(self, base_url: str, token: str) -> None:
        self._base = base_url.rstrip('/') + '/api/v1/kiosk'
        self._headers = {'Authorization': f'Bearer {token}', 'Accept': 'application/json'}

    def get_status(self) -> dict:
        with httpx.Client(headers=self._headers, timeout=5.0) as client:
            resp = client.get(f'{self._base}/status')
            resp.raise_for_status()
            return resp.json()

    def record_qr(self, session_id: int, qr_code: str) -> dict:
        with httpx.Client(headers=self._headers, timeout=10.0) as client:
            resp = client.post(
                f'{self._base}/record/qr',
                json={'session_id': session_id, 'qr_code': qr_code},
            )
            return resp.json()

    def record_facial(self, session_id: int, frame: np.ndarray) -> dict:
        _, buffer = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 90])
        image_bytes = buffer.tobytes()

        with httpx.Client(headers=self._headers, timeout=15.0) as client:
            resp = client.post(
                f'{self._base}/record/facial',
                data={'session_id': session_id},
                files={'photo': ('capture.jpg', image_bytes, 'image/jpeg')},
            )
            return resp.json()
```

### 2.6 — UX/UI del Kiosko (CustomTkinter)

La interfaz opera en modo pantalla completa permanente con tres estados visuales:

#### Estado 1: Esperando Escaneo (estado base)

```
┌──────────────────────────────────────────────┐
│                                              │
│         [ Logo ORVIAN ]                      │
│                                              │
│   ┌──────────────────────────────────────┐   │
│   │                                      │   │
│   │        PREVIEW DE CÁMARA             │   │
│   │        (OpenCV → CTkLabel)           │   │
│   │                                      │   │
│   └──────────────────────────────────────┘   │
│                                              │
│        ⬤  Esperando escaneo...              │
│        Sesión activa · Tanda Matutina        │
│                                              │
└──────────────────────────────────────────────┘
```

#### Estado 2: Procesando (overlay semi-transparente, ~500ms)

```
┌──────────────────────────────────────────────┐
│         ░░░░░░░░░░░░░░░░░░░░                 │
│         ░  Verificando...  ░                 │
│         ░░░░░░░░░░░░░░░░░░░░                 │
└──────────────────────────────────────────────┘
```

#### Estado 3: Resultado (visible durante 3 segundos, luego vuelve a Estado 1)

```
┌──────────────────────────────────────────────┐
│                                              │
│   ┌──────────────────────────────────────┐   │
│   │  [ Foto del estudiante — 120x120 ]   │   │
│   │                                      │   │
│   │  Ana María Rodríguez Pérez           │   │
│   │  ✅  PRESENTE                        │   │
│   │  07:48 AM                            │   │
│   └──────────────────────────────────────┘   │
│                                              │
│     [ Barra de progreso de 3 segundos ]      │
│                                              │
└──────────────────────────────────────────────┘
```

Para el estado de **error** (QR no encontrado, cara no reconocida, sesión cerrada), el overlay muestra el ícono de error, el mensaje descriptivo del `error_message` de la API, y reproduce `error.wav`. Tras 3 segundos regresa al estado base.

#### Estado 4: Sin Sesión Activa

```
┌──────────────────────────────────────────────┐
│                                              │
│         [ Logo ORVIAN ]                      │
│                                              │
│         ⏸  Sin sesión activa                │
│         No hay sesión de asistencia          │
│         abierta para hoy.                   │
│                                              │
│         Próxima verificación en 60s          │
│                                              │
└──────────────────────────────────────────────┘
```

El cliente hace polling al endpoint `GET /status` cada 60 segundos cuando no hay sesión activa, y cada 30 segundos como heartbeat cuando sí la hay (para detectar cierres de sesión).

#### Ciclo principal de la UI

```python
# ui/kiosk_window.py (fragmento del loop de frames)

def _process_frame_loop(self) -> None:
    """Loop ejecutado en hilo separado — nunca bloquea el hilo de UI."""
    while self._running:
        ret, frame = self._camera.read()
        if not ret:
            continue

        result = self._scanner_context.process_frame(frame)

        # Actualizar preview (thread-safe via queue)
        self._frame_queue.put(frame if result.frame_annotated is None
                              else result.frame_annotated)

        if result.detected:
            self._handle_detection(result)

def _handle_detection(self, result: ScanResult) -> None:
    self._scanner_context.reset()
    self._show_processing_overlay()

    try:
        if isinstance(self._scanner_context._strategy, QrStrategy):
            api_result = self._api.record_qr(self._session_id, result.payload)
        else:
            frame = self._frame_queue.queue[-1]   # último frame capturado
            api_result = self._api.record_facial(self._session_id, frame)

        if api_result.get('success'):
            self._audio.play_success()
            self._show_result_overlay(api_result)
        else:
            self._audio.play_error()
            self._show_error_overlay(api_result.get('message', 'Error desconocido'))

    except Exception as exc:
        self._audio.play_error()
        self._show_error_overlay(f'Error de conexión: {exc}')

    finally:
        # Volver al estado de espera tras 3 segundos
        self.after(3000, self._show_waiting_state)
```

### 2.7 — Modo Dual: Facial + QR Simultáneo

El cliente de escritorio puede operar en modo dual donde ambas estrategias se ejecutan en el mismo frame de forma alternada (frame par → Facial, frame impar → QR), idéntico al concepto planteado en el antiguo REQ-02 Slim Client, pero ahora con el rendimiento de las librerías nativas.

La implementación usa una estrategia compuesta `DualStrategy` que envuelve `FacialStrategy` y `QrStrategy`, alternando entre ellas por contador de frame. El `ScannerContext` no necesita modificaciones.

### 2.8 — Auto-Update (Concepto)

El ejecutable distribuido se acompaña de un `launcher.py` que actúa como script de arranque. Antes de iniciar la aplicación principal, el launcher consulta al servidor ORVIAN un endpoint dedicado (fuera del scope de v0.9.0, pero diseñado desde esta versión):

```
GET /api/v1/kiosk/version
→ { "latest_version": "1.2.0", "download_url": "https://..." }
```

Si la versión instalada (leída desde `version.txt` en el directorio del ejecutable) es anterior a `latest_version`, el launcher descarga el nuevo `.exe`, reemplaza el actual y relanza la aplicación. Si el servidor no responde, el launcher inicia la aplicación con la versión existente sin interrumpir la operación.

Este mecanismo garantiza que los dispositivos kiosko en las escuelas siempre corran la versión más reciente sin intervención manual del administrador del centro.

### 2.9 — Configuración Local y Persistencia

La aplicación de escritorio no utiliza archivos `.env`, ya que está diseñada para compilarse como un ejecutable autónomo. En su lugar, utiliza un archivo `config.json` administrado directamente desde la interfaz gráfica del kiosko.

```json
// config.json — generado automáticamente en %APPDATA%/OrvianScanner/ en el primer arranque

{
  "server_url": "http://localhost", 
  "kiosk_token": "",
  "camera_index": 0,
  "scan_mode": "dual",
  "display_fullscreen": true,
  "audio_enabled": true,
  "result_display_seconds": 3,
  "status_poll_interval_seconds": 60
}
```

El token se configura una sola vez en la instalación inicial del dispositivo (pegando el token generado desde la UI de configuración de la escuela en Laravel). Puede actualizarse desde la pantalla de configuración del propio kiosko, protegida por un PIN de administrador.

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

### Repositorio `orvian-desktop-scanner` (nuevo)

| Archivo | Acción | Fase |
| :--- | :--- | :--- |
| `main.py` | Crear — punto de entrada | 2 |
| `launcher.py` | Crear — script de arranque con auto-update | 2 |
| `config.py` + `config.json` | Crear — configuración local | 2 |
| `core/api_client.py` | Crear — cliente HTTP | 2 |
| `core/camera_manager.py` | Crear — gestión de stream OpenCV | 2 |
| `core/audio_manager.py` | Crear — reproducción de audio | 2 |
| `strategies/base_strategy.py` | Crear — interfaz Strategy | 2 |
| `strategies/facial_strategy.py` | Crear — implementación MediaPipe | 2 |
| `strategies/qr_strategy.py` | Crear — implementación pyzbar | 2 |
| `ui/kiosk_window.py` | Crear — ventana principal CustomTkinter | 2 |
| `ui/widgets/camera_feed.py` | Crear — widget preview de cámara | 2 |
| `ui/widgets/result_overlay.py` | Crear — overlay de resultado | 2 |
| `assets/sounds/success.wav` + `error.wav` | Incluir en el repositorio | 2 |
| `build/orvian-scanner.spec` | Crear — configuración PyInstaller | 2 |
| `requirements.txt` | Crear | 2 |

---

## Notas de Implementación

**Separación de repositorios:** `orvian-desktop-scanner` es un repositorio Git independiente. No comparte código ni dependencias con el monorepo Laravel. La única interfaz entre ambos sistemas son los tres endpoints del API Gateway definidos en Fase 1.

**Sanctum y tokenable School:** Laravel Sanctum soporta múltiples tokenables. Para que `School` pueda emitir tokens, debe implementar `HasApiTokens` e incluirse en el `sanctum.guard` si se usa la autenticación de guards. Verificar que `config/sanctum.php` liste el guard correcto o que el middleware `auth:sanctum` resuelva el modelo correctamente.

Las llamadas a `ApiClient` dentro de `_handle_detection` deben ejecutarse de forma asíncrona o enviarse a un `ThreadPoolExecutor`. De lo contrario, la petición HTTP síncrona bloqueará el hilo de captura de OpenCV, congelando el feed de video del usuario mientras espera la respuesta del servidor.

**Autenticación de Kiosko con Sanctum:** Para que el middleware `auth:sanctum` resuelva correctamente el modelo `School` en lugar del modelo `User`, se debe configurar un nuevo guard y provider en `config/auth.php` para las escuelas, o en su defecto, crear un middleware personalizado `KioskAuthMiddleware` que extraiga el modelo directamente usando `PersonalAccessToken::findToken($request->bearerToken())->tokenable`.

**Seguridad del Token de Kiosko:** El token de kiosko tiene la ability `kiosk` y no tiene fecha de expiración por defecto (los dispositivos de portería operan indefinidamente). Si un dispositivo es robado o comprometido, el administrador puede revocar el token desde la configuración de la escuela y generar uno nuevo. El dispositivo detectará el error `INVALID_TOKEN` en el próximo polling y mostrará la pantalla de configuración solicitando el nuevo token.

**Threading en la UI Python:** El loop de cámara (`_process_frame_loop`) corre en un hilo `daemon` separado del hilo principal de CustomTkinter. Las actualizaciones de UI se pasan a través de una `queue.Queue` y el método `after()` de Tkinter para garantizar thread-safety. Nunca se llaman métodos de UI directamente desde el hilo de cámara.

**pyzbar en Windows:** `pyzbar` requiere que `zbar.dll` esté disponible en el PATH o en el directorio del ejecutable. PyInstaller no lo incluye automáticamente; debe agregarse explícitamente en el `.spec` mediante `binaries=[('path/to/zbar.dll', '.')]`.

**Calidad de imagen para reconocimiento facial:** El cliente de escritorio captura el frame en la resolución nativa de OpenCV (típicamente 1280×720) y lo comprime al 90% de calidad JPEG antes de enviarlo. Si el microservicio `orvian-facial-recognition` tiene restricciones de tamaño, ajustar la calidad o reducir la resolución del crop facial en `facial_strategy.py` antes de serializar.

**Polling de sesión:** El intervalo de 60 segundos para el polling de `GET /status` es configurable en `config.json`. En escuelas con sesiones que abren exactamente a la hora, considerar reducirlo a 30 segundos para reducir la latencia de detección de sesión.

**Gestos en desktop (Fase 5):** Los eventos `touchstart`/`touchmove`/`touchend` no disparan en desktop. Los botones de Presente / Ausente / Tardanza son el método principal en desktop. Los gestos son aceleradores para tablets y móviles del maestro.

**Corrección de botones — migración incremental (Fase 11):** El cambio en `button.blade.php` rompe el loading automático en todos los formularios sin `wire:target` explícito. Aplicar la corrección al inicio de la fase y revisar los formularios críticos antes de mergear.

**VERSION:** Al completar todos los entregables, actualizar el archivo `VERSION` en la raíz del proyecto `orvian` a `0.9.0` y crear el tag `v0.9.0` en el repositorio `orvian-desktop-scanner`.