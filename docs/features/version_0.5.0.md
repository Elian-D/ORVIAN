# ORVIAN v0.5.0 — Módulo de Comunicaciones

**RAMA PADRE:** `feature/communications-module`

**Objetivo:** Activar el **Módulo de Comunicaciones** integrando Chatwoot como plataforma de mensajería institucional (embebido vía Iframe con SSO) y Evolution API como gateway de WhatsApp para alertas automáticas de asistencia. ORVIAN actúa exclusivamente como **emisor** de notificaciones y como **cliente** de Chatwoot — no gestiona respuestas entrantes ni levanta estos servicios localmente. La infraestructura ya está operativa en el VPS de producción.

---

## Estado de la Base — v0.4.1 como Fundación

Esta versión se construye directamente sobre los entregables consolidados de v0.4.1. Los siguientes elementos se consideran **base sólida disponible**, no tareas pendientes:

| Componente | Origen | Estado |
| :--- | :--- | :--- |
| `StudentObserver@created` genera `User` automático desde RNC | v0.4.1 / Fase 1.1 | ✅ Completado |
| Redirección post-login por `school_id` | v0.4.1 / Fase 1.2 | ✅ Completado |
| Nueva interfaz de login v2 + coexistencia v1/v2 por Cookie | v0.4.1 / Fase 1.3 & 4 | ✅ Completado |
| `View::share('appVersion')` con `Cache::rememberForever` | v0.4.1 / Fase 2 | ✅ Completado |
| Toast simplificado sin progress bar | v0.4.1 / Fase 3.1 | ✅ Completado |
| Dashboard filtra módulos por flag `visible` en `config/modules.php` | v0.4.1 / Fase 3.2 | ✅ Completado |
| Módulo de asistencia con `PlantelAttendanceRecord`, `ClassroomAttendanceRecord`, `Student` con `tutor_name` | v0.4.0 | ✅ Completado |
| Multi-tenancy con `school_id` y `GlobalScope` en modelos de tenant | v0.2.0 | ✅ Completado |
| `CompleteOnboardingAction` y `CompleteTenantOnboardingAction` con clonación de roles | v0.3.0 | ✅ Completado |

---

## Tabla de Requerimientos

| ID | Fase | Área | Descripción | Prioridad | Estado |
| :-- | :-- | :-- | :-- | :-- | :-- |
| REQ-01 | 1 | UI / Auth | Integración del Iframe de Chatwoot con SSO (HMAC-SHA256) en `ConversationsController` | Alta | Pendiente |
| REQ-02 | 1 | Servicio | Implementar `ChatwootService` — cliente HTTP hacia VPS (crear agente, generar hash SSO, conteo de conversaciones) | Alta | Pendiente |
| REQ-03 | 1 | Action | Invocar `ChatwootService::createAgent()` al final de `CompleteOnboardingAction` y `CompleteTenantOnboardingAction` | Alta | Pendiente |
| REQ-04 | 2 | BD / Modelo | Agregar `tutor_phone` (string, nullable) y `tutor_name` (si aún no existe) a tabla `students` con migración incremental | Alta | Pendiente |
| REQ-05 | 2 | Servicio | Implementar `WhatsAppService` — cliente HTTP hacia Evolution API en VPS. ORVIAN solo **envía**, no procesa respuestas | Alta | Pendiente |
| REQ-06 | 2 | Servicio | Implementar `WhatsAppTemplates` con plantillas de ausencia y tardanza | Media | Pendiente |
| REQ-07 | 3 | Job | Implementar `SendAttendanceAlertJob` con 3 reintentos y anti-duplicado mediante caché | Alta | Pendiente |
| REQ-08 | 3 | Evaluador | Implementar `AttendanceAlertEvaluator` con umbrales configurables y caché anti-spam semanal | Alta | Pendiente |
| REQ-09 | 3 | Comando | Registrar y programar `orvian:evaluate-attendance-alerts` (diario, 16:00) | Media | Pendiente |
| REQ-10 | 3 | Webhook | `EvolutionWebhookController` para recibir evento `connection.update` y actualizar estado en caché | Media | Pendiente |
| REQ-11 | 4 | UI | Vista `app/conversations` con Iframe de Chatwoot + SSO para Directores | Media | Pendiente |
| REQ-12 | 4 | UI | Vista `admin/conversations` con Iframe directo a panel admin de Chatwoot (Owner / TechnicalSupport) | Baja | Pendiente |
| REQ-13 | 4 | UI | Componente `WhatsappStatusIndicator` en `module-toolbar` — lee estado desde caché | Media | Pendiente |
| REQ-14 | 5 | Config | Rutas, permisos, seeders y `config/modules.php` actualizados para el módulo de conversaciones | Media | Pendiente |

---

## Arquitectura del Módulo

### Separación de Responsabilidades

**Lo que hace ORVIAN:**
- Embebe Chatwoot como Iframe con SSO (HMAC) para que los Directores gestionen conversaciones dentro del panel.
- Registra al Director como agente en Chatwoot al completar el onboarding (desde las Actions).
- Envía alertas automáticas de WhatsApp a tutores cuando un estudiante supera los umbrales de ausencia o tardanza.
- Recibe el webhook `connection.update` de Evolution para mantener el estado de la instancia en caché.

**Lo que NO hace ORVIAN:**
- No recibe ni procesa mensajes de WhatsApp. Chatwoot maneja las conversaciones de forma nativa en el VPS.
- No gestiona Chatwoot ni Evolution API — ambos corren como servicios independientes en `chat.orvian.com.do` y `evolution.orvian.com.do`.
- No levanta estos servicios en Docker local.

### Modelo de Conectividad

```
┌──────────────────────────────────────────────────────────────────┐
│  ENTORNO LOCAL (ORVIAN en Docker / Sail)                         │
│                                                                  │
│  Laravel App ──► HTTP POST ──► https://evolution.orvian.com.do   │
│                                (solo envío de mensajes)          │
│                                                                  │
│  Laravel App ──► HTTP GET/POST ─► https://chat.orvian.com.do     │
│                                (crear agente, SSO hash)          │
│                                                                  │
│  Webhooks entrantes de Evolution (solo connection.update):       │
│    evolution.orvian.com.do ──► ngrok / Expose ──► localhost      │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  VPS (chat.orvian.com.do / evolution.orvian.com.do)             │
│                                                                  │
│  ┌──────────────┐   Integración nativa    ┌────────────────────┐ │
│  │ Evolution API│ ──────────────────────► │ Chatwoot           │ │
│  │ (WhatsApp GW)│                         │ (Conversaciones)   │ │
│  └──────────────┘                         └────────────────────┘ │
│         │                                                         │
│         └── Webhook connection.update ──► ORVIAN (solo estado)   │
└──────────────────────────────────────────────────────────────────┘
```

---

## Configuración Cloud — Variables de Entorno

```dotenv
# --- INTEGRACIÓN CHATWOOT (VPS) ---
CHATWOOT_BASE_URL=https://chat.orvian.com.do
CHATWOOT_API_ACCESS_TOKEN=           # Token de Superadmin en Chatwoot
CHATWOOT_ACCOUNT_ID=1                # ID de la cuenta (normalmente 1)
CHATWOOT_HMAC_TOKEN=                 # Token HMAC para SSO/Identity Verification

# --- INTEGRACIÓN EVOLUTION API (VPS) ---
EVOLUTION_API_URL=https://evolution.orvian.com.do
EVOLUTION_API_KEY=                   # API Key configurada en el VPS
EVOLUTION_INSTANCE_NAME=orvian_school

# --- UMBRALES DE NOTIFICACIÓN ---
ALERT_ABSENCE_THRESHOLD=3
ALERT_TARDINESS_THRESHOLD=3
```

### `config/communications.php`

```php
<?php

return [

    'chatwoot' => [
        'base_url'   => env('CHATWOOT_BASE_URL', 'https://chat.orvian.com.do'),
        'api_token'  => env('CHATWOOT_API_ACCESS_TOKEN'),
        'account_id' => env('CHATWOOT_ACCOUNT_ID', 1),
        'hmac_token' => env('CHATWOOT_HMAC_TOKEN'),
    ],

    'evolution' => [
        'base_url'       => env('EVOLUTION_API_URL', 'https://evolution.orvian.com.do'),
        'api_key'        => env('EVOLUTION_API_KEY'),
        'instance_name'  => env('EVOLUTION_INSTANCE_NAME', 'orvian_school'),
    ],

    'notifications' => [
        'absence_threshold'   => env('ALERT_ABSENCE_THRESHOLD', 3),
        'tardiness_threshold' => env('ALERT_TARDINESS_THRESHOLD', 3),
    ],

];
```


---

## Fase 1 — Centro de Mensajes con Iframe y SSO
**Rama:** `feature/comms-chatwoot-iframe`

### Alcance de la Fase

Chatwoot se integra **exclusivamente como un servicio externo embebido mediante Iframe**. Los Directores acceden al chat profesional sin salir del panel de ORVIAN. El SSO elimina la necesidad de un login separado en Chatwoot.

**Límite de integración:** ORVIAN no sincroniza conversaciones, no almacena mensajes ni procesa la lógica de chat. Todo eso ocurre en el VPS de Chatwoot.

### 1.1 — `ChatwootService` — Cliente HTTP

Servicio único autorizado para comunicarse con la API REST de Chatwoot. Se registra como singleton en `AppServiceProvider`.

```php
<?php

namespace App\Services\Communications;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class ChatwootService
{
    protected string $baseUrl;
    protected string $apiToken;
    protected int    $accountId;

    public function __construct()
    {
        $this->baseUrl   = config('communications.chatwoot.base_url');
        $this->apiToken  = config('communications.chatwoot.api_token');
        $this->accountId = config('communications.chatwoot.account_id');
    }

    /**
     * Genera el identifier_hash para SSO/Identity Verification de Chatwoot.
     * Siempre se computa server-side. Nunca debe exponerse al cliente.
     */
    public function generateIdentifierHash(string $email): string
    {
        return hash_hmac('sha256', $email, config('communications.chatwoot.hmac_token'));
    }

    /**
     * Busca un agente por email. Evita duplicados en las Actions de onboarding.
     */
    public function findAgentByEmail(string $email): ?array
    {
        $response = Http::withHeaders(['api_access_token' => $this->apiToken])
            ->get("{$this->baseUrl}/api/v1/accounts/{$this->accountId}/agents");

        if ($response->failed()) {
            return null;
        }

        return collect($response->json())
            ->firstWhere('email', $email);
    }

    /**
     * Crea un agente en Chatwoot.
     * Invocado al final de CompleteOnboardingAction y CompleteTenantOnboardingAction.
     */
    public function createAgent(string $name, string $email, string $role = 'agent'): Response
    {
        return Http::withHeaders(['api_access_token' => $this->apiToken])
            ->post("{$this->baseUrl}/api/v1/accounts/{$this->accountId}/agents", [
                'name'                => $name,
                'email'               => $email,
                'role'                => $role,
                'availability_status' => 'online',
            ]);
    }

    /**
     * Obtiene el conteo de conversaciones abiertas.
     * Usado por el Dashboard para el badge del tile de Conversaciones.
     */
    public function getPendingConversationsCount(): int
    {
        try {
            $response = Http::withHeaders(['api_access_token' => $this->apiToken])
                ->get("{$this->baseUrl}/api/v1/accounts/{$this->accountId}/conversations", [
                    'status'        => 'open',
                    'assignee_type' => 'assigned',
                ]);

            return $response->successful()
                ? ($response->json('data.meta.all_count') ?? 0)
                : 0;

        } catch (\Throwable) {
            return 0;
        }
    }
}
```

- [ ] Registrar en `AppServiceProvider`: `$this->app->singleton(ChatwootService::class);`

### 1.2 — Sincronización del Director en las Actions de Onboarding

La sincronización del Director con Chatwoot **se ejecuta directamente al final de las Actions**, no mediante Observers ni Listeners. Esto garantiza que el rol `School Principal` ya está asignado y que la operación ocurre dentro de la misma transacción de onboarding.

#### Integración en `CompleteOnboardingAction`

```php
// app/Actions/Tenant/CompleteOnboardingAction.php
// (Solo se muestran las adiciones — el resto de la Action permanece igual)

use App\Services\Communications\ChatwootService;

class CompleteOnboardingAction
{
    public function __construct(
        protected CreateSchoolPrincipalAction $createPrincipal,
        protected \App\Services\School\SchoolRoleService $roleService,
        protected ChatwootService $chatwootService,   // ← Inyectar servicio
    ) {}

    public function execute(array $wizardData): School
    {
        return DB::transaction(function () use ($wizardData) {

            // ... [pasos 1–5 sin cambios: crear escuela, sincronizar relaciones,
            //      seedDefaultRoles, createPrincipal, resetear setPermissionsTeamId] ...

            // 6. Disparar evento de configuración
            event(new SchoolConfigured($school, $wizardData['academic']));

            // 7. Registrar al Director como Agente en Chatwoot (fuera de la TX si falla)
            //    Se ejecuta DESPUÉS de que el rol 'School Principal' está garantizado.
            $this->syncPrincipalToChatwoot($wizardData['principal']);

            return $school;
        });
    }

    /**
     * Sincroniza el Director recién creado con Chatwoot como Agente.
     * Si Chatwoot no está disponible, registra el error sin interrumpir el flujo.
     */
    protected function syncPrincipalToChatwoot(array $principalData): void
    {
        $email = $principalData['email'];

        try {
            $existing = $this->chatwootService->findAgentByEmail($email);

            if ($existing) {
                \Log::info("CompleteOnboardingAction: Agente ya existe en Chatwoot [{$email}]");
                return;
            }

            $response = $this->chatwootService->createAgent(
                name:  $principalData['name'],
                email: $email,
                role:  'agent',
            );

            if ($response->successful()) {
                \Log::info("CompleteOnboardingAction: Agente creado en Chatwoot [{$email}]");
            }

        } catch (\Throwable $e) {
            // No rompe el flujo de ORVIAN si Chatwoot no está disponible
            \Log::error("CompleteOnboardingAction: Fallo al crear agente en Chatwoot", [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

#### Integración en `CompleteTenantOnboardingAction`

```php
// app/Actions/Tenant/CompleteTenantOnboardingAction.php
// (Solo se muestran las adiciones)

use App\Services\Communications\ChatwootService;

class CompleteTenantOnboardingAction
{
    public function __construct(
        protected SchoolRoleService $roleService,
        protected ChatwootService $chatwootService,   // ← Inyectar servicio
    ) {}

    public function execute(int $schoolId, array $wizardData, User $principalUser): School
    {
        return DB::transaction(function () use ($schoolId, $wizardData, $principalUser) {

            // ... [pasos 1–6 sin cambios: actualizar escuela, sincronizar relaciones,
            //      seedDefaultRoles, asignar rol School Principal, resetear scope] ...

            // 7. Disparar evento de configuración
            event(new SchoolConfigured($school, $wizardData['academic']));

            // 8. Sincronizar Director con Chatwoot — justo después de que el rol fue asignado.
            $this->syncPrincipalToChatwoot($principalUser);

            return $school;
        });
    }

    /**
     * Sincroniza el Director (usuario existente) con Chatwoot como Agente.
     * El rol 'School Principal' ya fue asignado en el paso 6 antes de esta llamada.
     */
    protected function syncPrincipalToChatwoot(User $user): void
    {
        try {
            $existing = $this->chatwootService->findAgentByEmail($user->email);

            if ($existing) {
                \Log::info("CompleteTenantOnboardingAction: Agente ya existe en Chatwoot [{$user->email}]");
                return;
            }

            $response = $this->chatwootService->createAgent(
                name:  $user->name,
                email: $user->email,
                role:  'agent',
            );

            if ($response->successful()) {
                \Log::info("CompleteTenantOnboardingAction: Agente creado en Chatwoot [{$user->email}]");

                // Persistir el chatwoot_agent_id en preferences para uso futuro
                $user->updateQuietly([
                    'preferences' => array_merge($user->preferences ?? [], [
                        'chatwoot_agent_id' => $response->json('id'),
                    ]),
                ]);
            }

        } catch (\Throwable $e) {
            \Log::error("CompleteTenantOnboardingAction: Fallo al crear agente en Chatwoot", [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

> **Decisión de arquitectura:** La sincronización con Chatwoot se invoca directamente en las Actions (no en Observers ni Listeners) porque ambas Actions ya controlan el orden exacto de ejecución y garantizan que `hasRole('School Principal')` es verdadero en el momento de la llamada. Eliminar la indirección del Observer/Listener simplifica la trazabilidad del flujo de onboarding y evita condiciones de carrera.

### 1.3 — `ConversationsController` — SSO con Iframe

```php
<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Communications\ChatwootService;
use Illuminate\View\View;

class ConversationsController extends Controller
{
    public function index(ChatwootService $chatwoot): View
    {
        $user         = auth()->user();
        $chatwootBase = config('communications.chatwoot.base_url');

        // Director / Agente — SSO automático vía Identity Verification (HMAC-SHA256)
        $identifierHash = $chatwoot->generateIdentifierHash($user->email);

        $chatwootUrl = "{$chatwootBase}?email=" . urlencode($user->email)
            . "&identifier_hash={$identifierHash}"
            . "&name=" . urlencode($user->name);

        return view('app.conversations.index', compact('chatwootUrl'));
    }
}
```

> `CHATWOOT_HMAC_TOKEN` debe tratarse con el mismo nivel de confidencialidad que `APP_KEY`. El `identifier_hash` se computa siempre en el servidor y nunca se expone en el cliente.

- [ ] Crear `app/Http/Controllers/App/ConversationsController.php`
- [ ] Crear `app/Http/Controllers/Admin/ConversationsController.php` (acceso directo sin SSO para Owner/TechnicalSupport)

---

## Fase 2 — Migración de Tutores y Gateway WhatsApp
**Rama:** `feature/whatsapp-service`

### 2.1 — Migración: `tutor_phone` y `tutor_name` en Tabla `students`

`tutor_phone` es la pieza central para el envío de alertas. `tutor_name` se añade si aún no existe en la tabla (verificar migración de v0.4.0 antes de ejecutar).

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Solo agregar tutor_name si no existe — validar contra migraciones de v0.4.0
            if (! Schema::hasColumn('students', 'tutor_name')) {
                $table->string('tutor_name', 120)
                      ->nullable()
                      ->after('last_name')
                      ->comment('Nombre completo del tutor o responsable del estudiante');
            }

            // tutor_phone es nuevo en v0.5.0
            $table->string('tutor_phone', 20)
                  ->nullable()
                  ->after('tutor_name')
                  ->comment('Número WhatsApp del tutor en formato E.164. Ej: +18091234567');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('tutor_phone');
            // No hacer dropColumn de tutor_name si existía antes de esta migración
        });
    }
};
```

- [ ] Ejecutar: `php artisan make:migration add_tutor_fields_to_students_table`
- [ ] Actualizar `$fillable` del modelo `Student` con `'tutor_phone'` (y `'tutor_name'` si aplica).
- [ ] Actualizar `StudentService` para guardar/actualizar `tutor_phone`.
- [ ] Agregar campo en formulario de edición de estudiante con validación E.164 (`regex:/^\+[1-9]\d{7,14}$/`).
- [ ] El campo `tutor_phone` solo debe ser visible para usuarios con permiso `manage conversations` o superior — aplicar Policy de autorización.

### 2.2 — `WhatsAppService` — Gateway de Envío

ORVIAN actúa exclusivamente como **emisor**. Este servicio encapsula el envío de mensajes hacia Evolution API. No procesa respuestas ni gestiona conversaciones — eso es responsabilidad de Chatwoot en el VPS.

```php
<?php

namespace App\Services\Communications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $instance;

    public function __construct()
    {
        $this->baseUrl  = config('communications.evolution.base_url');
        $this->apiKey   = config('communications.evolution.api_key');
        $this->instance = config('communications.evolution.instance_name');
    }

    /**
     * Envía un mensaje de texto al tutor.
     * ORVIAN solo envía — no espera ni procesa respuestas.
     *
     * @param  string  $phone    Número en formato E.164 (ej. +18091234567)
     * @param  string  $message  Texto con soporte de formato WhatsApp (*negrita*, _cursiva_)
     */
    public function sendTextMessage(string $phone, string $message): bool
    {
        // Evolution API espera el número sin el símbolo '+'
        $normalizedPhone = ltrim($phone, '+');

        try {
            $response = Http::withHeaders([
                'apikey'       => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/message/sendText/{$this->instance}", [
                'number'  => $normalizedPhone,
                'options' => [
                    'delay'    => 1200,
                    'presence' => 'composing',
                ],
                'textMessage' => [
                    'text' => $message,
                ],
            ]);

            if ($response->successful()) {
                Log::info('WhatsAppService: Mensaje enviado', [
                    'phone'  => $normalizedPhone,
                    'msg_id' => $response->json('key.id'),
                ]);
                return true;
            }

            Log::warning('WhatsAppService: Respuesta no exitosa', [
                'phone'  => $normalizedPhone,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

        } catch (\Throwable $e) {
            Log::error('WhatsAppService: Excepción al enviar mensaje', [
                'phone' => $normalizedPhone,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Verifica si la instancia de WhatsApp está conectada en el VPS.
     * Usado como fallback por WhatsappStatusIndicator cuando la caché no tiene dato.
     */
    public function getInstanceStatus(): array
    {
        try {
            $response = Http::withHeaders(['apikey' => $this->apiKey])
                ->get("{$this->baseUrl}/instance/connectionState/{$this->instance}");

            return $response->json() ?? ['state' => 'unknown'];
        } catch (\Throwable $e) {
            return ['state' => 'error', 'message' => $e->getMessage()];
        }
    }
}
```

- [ ] Registrar en `AppServiceProvider`: `$this->app->singleton(WhatsAppService::class);`

### 2.3 — `WhatsAppTemplates` — Plantillas de Mensajes

Centraliza todos los textos de notificación. Ningún mensaje debe estar hardcodeado fuera de esta clase.

```php
<?php

namespace App\Services\Communications;

use App\Models\Tenant\Student;

class WhatsAppTemplates
{
    public static function absenceAlert(Student $student, int $count, string $month): string
    {
        return <<<MSG
        📋 *ORVIAN — Notificación de Asistencia*

        Estimado/a tutor(a) de *{$student->full_name}*,

        Le informamos que su representado/a ha acumulado *{$count} ausencia(s) injustificada(s)* durante el mes de {$month}.

        Le solicitamos comunicarse con la dirección del centro para coordinar el seguimiento correspondiente.

        _Este mensaje es generado automáticamente por el Sistema de Gestión ORVIAN._
        MSG;
    }

    public static function tardinessAlert(Student $student, int $count, string $month): string
    {
        return <<<MSG
        ⏰ *ORVIAN — Aviso de Puntualidad*

        Estimado/a tutor(a) de *{$student->full_name}*,

        Le notificamos que su representado/a ha registrado *{$count} llegada(s) tarde* durante el mes de {$month}.

        La puntualidad es fundamental para el aprovechamiento académico. Le agradecemos su atención.

        _Este mensaje es generado automáticamente por el Sistema de Gestión ORVIAN._
        MSG;
    }
}
```

---

## Fase 3 — Motor de Notificaciones Automáticas (`AttendanceAlertJob`)
**Rama:** `feature/attendance-alerts`

### Flujo Completo de una Alerta

```
PlantelAttendanceService::closeDay()
    │
    ▼
orvian:evaluate-attendance-alerts (diario 16:00)
    │
    ▼
AttendanceAlertEvaluator::evaluate(Student $student)
    │  Consulta PlantelAttendanceRecord del mes actual
    │  Compara contra umbrales (config/communications.php)
    │  Verifica caché anti-spam (TTL 7 días por semana)
    │
    ▼ (solo si umbral superado y sin alerta enviada esta semana)
SendAttendanceAlertJob::dispatch($student, $type, $count, $month)
    │  Queue: 3 reintentos, backoff 60s
    │
    ▼
WhatsAppService::sendTextMessage($student->tutor_phone, $message)
    │  POST https://evolution.orvian.com.do/message/sendText/{instancia}
    │  ORVIAN no espera respuesta — el envío es fire-and-forget asíncrono
    ▼
Evolution API (VPS) entrega el mensaje al tutor vía WhatsApp
```

### 3.1 — `AttendanceAlertEvaluator`

Lógica de negocio pura. Consulta los datos de asistencia (v0.4.0), evalúa umbrales y despacha Jobs. No envía mensajes directamente.

```php
<?php

namespace App\Services\Communications;

use App\Jobs\Communications\SendAttendanceAlertJob;
use App\Models\Tenant\Student;
use App\Models\Tenant\PlantelAttendanceRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AttendanceAlertEvaluator
{
    protected int $absenceThreshold;
    protected int $tardinessThreshold;

    public function __construct()
    {
        $this->absenceThreshold   = config('communications.notifications.absence_threshold', 3);
        $this->tardinessThreshold = config('communications.notifications.tardiness_threshold', 3);
    }

    /**
     * Evalúa un estudiante y despacha alertas si supera los umbrales.
     * Protegido por caché anti-spam: máximo una alerta del mismo tipo por semana.
     */
    public function evaluate(Student $student): void
    {
        if (empty($student->tutor_phone)) {
            Log::debug('AttendanceAlertEvaluator: Sin tutor_phone, omitiendo', [
                'student_id' => $student->id,
            ]);
            return;
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();
        $monthName    = Carbon::now()->translatedFormat('F Y');
        $weekKey      = Carbon::now()->weekOfYear;

        $records = PlantelAttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $absences  = $records->where('status', 'absent')->count();
        $tardiness = $records->where('status', 'late')->count();

        // ── Alerta de ausencias ──
        if ($absences >= $this->absenceThreshold) {
            $cacheKey = "alert_absence_{$student->id}_{$weekKey}";
            if (! Cache::has($cacheKey)) {
                SendAttendanceAlertJob::dispatch($student, 'absence', $absences, $monthName);
                Cache::put($cacheKey, true, now()->addDays(7));
                Log::info('AttendanceAlertEvaluator: Job de ausencia despachado', [
                    'student_id' => $student->id,
                    'count'      => $absences,
                ]);
            }
        }

        // ── Alerta de tardanzas ──
        if ($tardiness >= $this->tardinessThreshold) {
            $cacheKey = "alert_tardiness_{$student->id}_{$weekKey}";
            if (! Cache::has($cacheKey)) {
                SendAttendanceAlertJob::dispatch($student, 'tardiness', $tardiness, $monthName);
                Cache::put($cacheKey, true, now()->addDays(7));
                Log::info('AttendanceAlertEvaluator: Job de tardanza despachado', [
                    'student_id' => $student->id,
                    'count'      => $tardiness,
                ]);
            }
        }
    }
}
```

**Anti-spam:** La clave de caché `alert_{tipo}_{student_id}_{weekOfYear}` garantiza que un tutor no reciba más de una alerta del mismo tipo por semana, aunque el comando se ejecute múltiples veces al día. TTL: 7 días. Configurar `CACHE_DRIVER=redis` en producción.

### 3.2 — Job `SendAttendanceAlertJob`

```php
<?php

namespace App\Jobs\Communications;

use App\Models\Tenant\Student;
use App\Services\Communications\WhatsAppService;
use App\Services\Communications\WhatsAppTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAttendanceAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 3;
    public int $backoff = 60; // Segundos entre reintentos

    public function __construct(
        public readonly Student $student,
        public readonly string  $type,   // 'absence' | 'tardiness'
        public readonly int     $count,
        public readonly string  $month,
    ) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        if (empty($this->student->tutor_phone)) {
            Log::warning('SendAttendanceAlertJob: Estudiante sin tutor_phone, descartando', [
                'student_id' => $this->student->id,
            ]);
            return;
        }

        $message = match ($this->type) {
            'absence'   => WhatsAppTemplates::absenceAlert($this->student, $this->count, $this->month),
            'tardiness' => WhatsAppTemplates::tardinessAlert($this->student, $this->count, $this->month),
            default     => throw new \InvalidArgumentException("Tipo de alerta desconocido: {$this->type}"),
        };

        $whatsApp->sendTextMessage($this->student->tutor_phone, $message);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendAttendanceAlertJob: Falló definitivamente tras todos los reintentos', [
            'student_id' => $this->student->id,
            'type'       => $this->type,
            'error'      => $exception->getMessage(),
        ]);
    }
}
```

### 3.3 — Comando `orvian:evaluate-attendance-alerts`

```php
<?php

namespace App\Console\Commands;

use App\Models\Tenant\Student;
use App\Services\Communications\AttendanceAlertEvaluator;
use Illuminate\Console\Command;

class EvaluateAttendanceAlertsCommand extends Command
{
    protected $signature   = 'orvian:evaluate-attendance-alerts {--school= : ID del centro. Si se omite, evalúa todos.}';
    protected $description = 'Evalúa umbrales de asistencia y despacha notificaciones WhatsApp a tutores.';

    public function handle(AttendanceAlertEvaluator $evaluator): int
    {
        $query = Student::query()->active()->with('section');

        if ($schoolId = $this->option('school')) {
            $query->where('school_id', $schoolId);
        }

        $students = $query->get();

        $this->info("Evaluando {$students->count()} estudiante(s)...");
        $this->withProgressBar($students, fn (Student $student) => $evaluator->evaluate($student));
        $this->newLine();
        $this->info('Evaluación completada.');

        return Command::SUCCESS;
    }
}
```

```php
// routes/console.php — Ejecución diaria a las 4:00 PM (posterior al cierre de sesión escolar)
Schedule::command('orvian:evaluate-attendance-alerts')
    ->dailyAt('16:00')
    ->withoutOverlapping()
    ->description('Evalúa alertas de asistencia y notifica tutores por WhatsApp');
```

---

## Fase 4 — UI: Iframe de Chatwoot e Indicador de Estado
**Rama:** `feature/comms-ui`

### 4.1 — Vista del Centro de Mensajes (Directores)

```blade
{{-- resources/views/app/conversations/index.blade.php --}}
@extends('layouts.app-module', config('modules.conversaciones'))

@section('content')
<div class="flex flex-col h-[calc(100vh-3.5rem-3rem)] -mx-4 -mb-4">

    <x-app.module-toolbar>
        <x-slot:actions>
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">
                Centro de Mensajes
            </span>
        </x-slot:actions>
        <x-slot:secondary>
            <livewire:app.conversations.whatsapp-status-indicator />
        </x-slot:secondary>
    </x-app.module-toolbar>

    <div class="flex-1 overflow-hidden">
        <iframe
            src="{{ $chatwootUrl }}"
            class="w-full h-full border-0"
            allow="camera; microphone"
            title="Centro de Mensajes ORVIAN"
        ></iframe>
    </div>
</div>
@endsection
```

### 4.2 — Vista de Administración (Owner / TechnicalSupport)

```blade
{{-- resources/views/admin/conversations/index.blade.php --}}
@extends('layouts.admin')

@section('content')
<div class="flex flex-col h-[calc(100vh-4rem)]">
    <x-ui.page-header title="Centro de Administración — Chatwoot">
        <x-slot:actions>
            <x-ui.badge variant="info" :dot="true">Panel Global</x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex-1 mt-4 rounded-xl overflow-hidden border border-slate-200 dark:border-white/[0.07]">
        <iframe
            src="{{ $chatwootAdminUrl }}"
            class="w-full h-full border-0"
            allow="camera; microphone"
            title="Administración Chatwoot"
        ></iframe>
    </div>
</div>
@endsection
```

### 4.3 — Componente `WhatsappStatusIndicator`

Lee el estado desde caché (actualizado por el webhook `connection.update`). Si la caché no tiene dato, hace una llamada a Evolution como fallback.

```php
<?php

namespace App\Livewire\App\Conversations;

use App\Services\Communications\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\Attributes\Lazy;

#[Lazy]
class WhatsappStatusIndicator extends Component
{
    public string $state = 'unknown';

    public function mount(): void
    {
        $instance = config('communications.evolution.instance_name');

        $this->state = Cache::remember(
            "evolution_connection_state_{$instance}",
            now()->addMinutes(5),
            fn () => app(WhatsAppService::class)->getInstanceStatus()['state'] ?? 'unknown'
        );
    }

    public function render()
    {
        return view('livewire.app.conversations.whatsapp-status-indicator');
    }
}
```

```blade
{{-- resources/views/livewire/app/conversations/whatsapp-status-indicator.blade.php --}}
@php
    $connected = $state === 'open';
    $label     = $connected ? 'WhatsApp Conectado' : 'WhatsApp Desconectado';
    $variant   = $connected ? 'success' : 'error';
@endphp

<x-ui.badge :variant="$variant" :dot="true" class="text-xs">
    {{ $label }}
</x-ui.badge>
```

---

## Fase 5 — Rutas, Permisos y Configuración de Módulo
**Rama:** `feature/comms-config`

### 5.1 — Rutas

```php
// routes/app/conversations.php
use App\Http\Controllers\App\ConversationsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'can:view conversations'])
    ->prefix('app/conversations')
    ->name('app.conversations.')
    ->group(function () {
        Route::get('/', [ConversationsController::class, 'index'])->name('index');
    });
```

```php
// routes/admin/conversations.php
use App\Http\Controllers\Admin\ConversationsController as AdminConversationsController;

Route::middleware(['auth', 'global-admin', 'can:admin conversations'])
    ->prefix('admin/conversations')
    ->name('admin.conversations.')
    ->group(function () {
        Route::get('/', [AdminConversationsController::class, 'index'])->name('index');
    });
```

### 5.2 — `config/modules.php`

```php
'conversaciones' => [
    'name'        => 'Conversaciones',
    'icon'        => 'conversaciones',
    'visible'     => true,    // Activado en v0.5.0
    'moduleLinks' => [
        ['label' => 'Centro de Mensajes', 'route' => 'app.conversations.index'],
    ],
],
```

### 5.3 — Nuevos Permisos y Seeders

- [ ] Agregar grupo `communications` en `PermissionGroupSeeder` con `context = 'tenant'`.
- [ ] Agregar en `PermissionSeeder`: `view conversations`, `manage conversations`, `send whatsapp notifications`.
- [ ] Agregar `admin conversations` en `PermissionSeeder` con `context = 'global'`.
- [ ] Actualizar `RoleAcademicSeeder`: `School Principal` → todos los permisos de `communications`; `Academic Coordinator` → `view conversations`; `Teacher` → sin permisos.

---

## Checklist de Archivos — v0.5.0

### Configuración e Infraestructura

- [ ] `config/communications.php` creado con todos los keys
- [ ] `.env` actualizado con tokens de Chatwoot y Evolution (VPS)
- [ ] Ngrok/Expose configurado para desarrollo local (webhooks entrantes)

### Fase 1 — Chatwoot SSO

- [ ] `app/Services/Communications/ChatwootService.php` creado y registrado como singleton
- [ ] `app/Http/Controllers/App/ConversationsController.php` creado con generación de HMAC server-side
- [ ] `app/Http/Controllers/Admin/ConversationsController.php` creado (acceso directo sin SSO)
- [ ] `app/Actions/Tenant/CompleteOnboardingAction.php` modificado — inyectar `ChatwootService`, llamar `syncPrincipalToChatwoot()` al final
- [ ] `app/Actions/Tenant/CompleteTenantOnboardingAction.php` modificado — inyectar `ChatwootService`, llamar `syncPrincipalToChatwoot()` después de asignar rol

### Fase 2 — Migración y WhatsApp Service

- [ ] `database/migrations/xxxx_add_tutor_fields_to_students_table.php` creado y ejecutado
- [ ] `app/Models/Tenant/Student.php` — `$fillable` actualizado con `tutor_phone` (y `tutor_name` si aplica)
- [ ] `StudentService` actualizado para persistir `tutor_phone`
- [ ] Formulario de edición de estudiante con campo `tutor_phone` validado (E.164)
- [ ] `app/Services/Communications/WhatsAppService.php` creado y registrado como singleton
- [ ] `app/Services/Communications/WhatsAppTemplates.php` creado

### Fase 3 — Motor de Notificaciones

- [ ] `app/Services/Communications/AttendanceAlertEvaluator.php` creado
- [ ] `app/Jobs/Communications/SendAttendanceAlertJob.php` creado con 3 reintentos
- [ ] `app/Console/Commands/EvaluateAttendanceAlertsCommand.php` creado
- [ ] `routes/console.php` — comando programado a las 16:00 con `withoutOverlapping()`

### Fase 4 — UI

- [ ] `resources/views/app/conversations/index.blade.php` creado con Iframe SSO
- [ ] `resources/views/admin/conversations/index.blade.php` creado con Iframe admin
- [ ] `app/Livewire/App/Conversations/WhatsappStatusIndicator.php` creado
- [ ] `resources/views/livewire/app/conversations/whatsapp-status-indicator.blade.php` creado
- [ ] SVG `conversaciones.svg` añadido a `public/assets/icons/modules/`
- [ ] Tile `conversaciones` con `visible: true` en `config/modules.php`

### Fase 5 — Rutas y Permisos

- [ ] `routes/app/conversations.php` creado
- [ ] `routes/admin/conversations.php` creado
- [ ] Grupo `communications` en `PermissionGroupSeeder`
- [ ] Permisos de conversaciones en `PermissionSeeder`
- [ ] `RoleAcademicSeeder` actualizado con permisos de `communications`
- [ ] `AppServiceProvider` — singletons de `ChatwootService` y `WhatsAppService` registrados

---

## Tabla de Archivos Modificados

| Archivo | Tipo de cambio | Fase |
| :--- | :--- | :--- |
| `app/Actions/Tenant/CompleteOnboardingAction.php` | Modificación — inyectar `ChatwootService` + `syncPrincipalToChatwoot()` | 1.2 |
| `app/Actions/Tenant/CompleteTenantOnboardingAction.php` | Modificación — inyectar `ChatwootService` + `syncPrincipalToChatwoot()` | 1.2 |
| `app/Services/Communications/ChatwootService.php` | Creación | 1.1 |
| `app/Http/Controllers/App/ConversationsController.php` | Creación — SSO + Iframe | 1.3 |
| `app/Http/Controllers/Admin/ConversationsController.php` | Creación — Iframe admin directo | 1.3 |
| `database/migrations/xxxx_add_tutor_fields_to_students_table.php` | Creación — migración incremental | 2.1 |
| `app/Models/Tenant/Student.php` | Modificación — `$fillable` + `tutor_phone` | 2.1 |
| `app/Services/Communications/WhatsAppService.php` | Creación | 2.2 |
| `app/Services/Communications/WhatsAppTemplates.php` | Creación | 2.3 |
| `app/Services/Communications/AttendanceAlertEvaluator.php` | Creación | 3.1 |
| `app/Jobs/Communications/SendAttendanceAlertJob.php` | Creación | 3.2 |
| `app/Console/Commands/EvaluateAttendanceAlertsCommand.php` | Creación | 3.3 |
| `routes/console.php` | Modificación — programar comando diario | 3.3 |
| `resources/views/app/conversations/index.blade.php` | Creación — Iframe con SSO | 4.1 |
| `resources/views/admin/conversations/index.blade.php` | Creación — Iframe admin | 4.2 |
| `app/Livewire/App/Conversations/WhatsappStatusIndicator.php` | Creación | 4.3 |
| `resources/views/livewire/app/conversations/whatsapp-status-indicator.blade.php` | Creación | 4.3 |
| `config/communications.php` | Creación | Conectividad |
| `config/modules.php` | Modificación — `conversaciones` con `visible: true` | 5.2 |
| `database/seeders/PermissionGroupSeeder.php` | Modificación — grupo `communications` | 5.3 |
| `database/seeders/PermissionSeeder.php` | Modificación — permisos de conversaciones | 5.3 |
| `database/seeders/RoleAcademicSeeder.php` | Modificación — asignar permisos de communications | 5.3 |
| `routes/app/conversations.php` | Creación | 5.1 |
| `routes/admin/conversations.php` | Creación | 5.1 |
| `app/Providers/AppServiceProvider.php` | Modificación — singletons de servicios | 1.1, 2.2 |
| `.env` | Modificación — tokens de Chatwoot y Evolution | Conectividad |

---

## Decisiones de Arquitectura Registradas

**ORVIAN como emisor unidireccional de WhatsApp:** La funcionalidad de WhatsApp se limita a enviar alertas automáticas de asistencia. ORVIAN no recibe ni procesa mensajes de tutores — esa responsabilidad recae sobre Chatwoot en el VPS, que integra nativamente con Evolution API. Esta decisión reduce la complejidad de ORVIAN y mantiene el foco académico del sistema.

**Chatwoot como servicio externo vía Iframe:** Chatwoot se integra como una pestaña dentro del panel de ORVIAN mediante un Iframe con SSO. No se sincronizan conversaciones, contactos ni datos de Chatwoot hacia la BD de ORVIAN. El Director trabaja directamente en la interfaz de Chatwoot sin salir del sistema.

**Sincronización con Chatwoot en las Actions, no en Observers/Listeners:** El registro del Director como agente en Chatwoot se ejecuta directamente en `CompleteOnboardingAction` y `CompleteTenantOnboardingAction`, al final del flujo de onboarding (después de que el rol `School Principal` está garantizado). Esto elimina la indirección del Observer/Listener, simplifica la trazabilidad y evita condiciones de carrera. Si Chatwoot no está disponible, el error se registra en log sin interrumpir el onboarding.

**`WhatsAppService` no envía directamente desde el Evaluador:** El `AttendanceAlertEvaluator` despacha un `SendAttendanceAlertJob` en lugar de llamar al servicio directamente. Los envíos son asíncronos (no bloquean el cierre del día), reintentables (3 intentos con backoff) y observables en el panel de colas de Laravel.

**Caché de estado de conexión vía webhook:** El `WhatsappStatusIndicator` lee el estado desde caché en lugar de llamar a la API en cada render. El webhook `connection.update` de Evolution actualiza esa caché en tiempo real. Esto evita una llamada HTTP por cada carga de la barra de módulo.

**Infraestructura en VPS:** Chatwoot (`chat.orvian.com.do`) y Evolution API (`evolution.orvian.com.do`) corren en el VPS. ORVIAN se conecta directamente a las URLs de producción mediante variables de entorno. El costo es que el módulo de comunicaciones requiere conexión a internet en desarrollo local — lo cual es aceptable dado el alcance académico del proyecto.

**Orden de dependencias obligatorio para el desarrollo:**
1. Configurar `.env` con tokens del VPS antes de cualquier prueba de integración.
2. Fase 1 (`ChatwootService` + Actions) establece la identidad del Director en Chatwoot.
3. Fase 2 (migración `tutor_phone` + `WhatsAppService`) puede desarrollarse en paralelo con Fase 1.
4. Fase 3 (Evaluador + Jobs) depende de que `tutor_phone` exista en la BD.
5. Los webhooks (Fase 3.4) requieren ngrok activo en local para probarse de extremo a extremo.
6. Fase 4 (UI/Iframe) puede comenzarse en paralelo; el SSO no se valida sin la instancia de Chatwoot activa en el VPS.