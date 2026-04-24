# ORVIAN v0.5.0 — Módulo de Conversaciones e Integración WhatsApp

**RAMA PADRE:**
`feature/communications-module`

**Objetivo:** Implementar el **Módulo de Conversaciones** integrando Chatwoot como plataforma de soporte y comunicación institucional, Evolution API como gateway de WhatsApp, y automatizar notificaciones a tutores basadas en los datos de asistencia generados por el módulo v0.4.0.

---

## Arquitectura del Módulo

### Conceptos Clave

**Tres Capas de Comunicación:**
1. **Centro de Mensajes (Chatwoot):** Plataforma centralizada para la gestión de conversaciones con tutores, padres y personal. Embebida como Iframe en el panel de ORVIAN con SSO automático para agentes (Directores y coordinadores).
2. **Gateway WhatsApp (Evolution API):** Servicio intermediario que conecta un número de WhatsApp institucional con Chatwoot y con el sistema de notificaciones automatizadas de ORVIAN.
3. **Motor de Notificaciones Automatizadas:** Capa de lógica de negocio dentro de ORVIAN que consume los datos de asistencia (v0.4.0) y dispara alertas proactivas al tutor vía WhatsApp cuando un estudiante acumula ausencias o tardanzas.

**Separación de Responsabilidades (Infraestructura):**
- Chatwoot y Evolution API corren en un `docker-compose.yml` **independiente** del de ORVIAN.
- Se comunican entre sí por nombre de servicio dentro de la red Docker compartida `app_network`.
- ORVIAN se comunica con ambos servicios exclusivamente mediante HTTP (API REST).
- El panel de ORVIAN nunca redirige al usuario fuera del sistema; todo ocurre mediante Iframe.

**Dos Vistas del Iframe (Segmentadas por Rol):**
- **SuperAdmin (Owner / TechnicalSupport):** Acceso al panel de administración global de Chatwoot con login manual. Sin SSO, ya que el Owner gestiona la infraestructura completa.
- **Director / Agente:** Login automático (SSO) mediante HMAC `identifier_hash` de la API de Chatwoot. El usuario nunca ve una pantalla de login.

---

## Notas de Implementación — Por Qué Fallaba el Docker en Local

### El Problema

Al correr el `docker-compose.yml` original, Evolution API y Chatwoot podían exponer sus servicios al host pero **no podían comunicarse entre sí de forma confiable**. Los síntomas típicos:
- Evolution enviaba webhooks a `http://localhost:3001` (la URL pública de Chatwoot) y fallaba.
- Chatwoot intentaba contactar a Evolution en `http://localhost:8080` y también fallaba.
- Los logs mostraban `Connection refused` o `Name or service not known`.

### La Causa Raíz

Docker usa una red virtual interna. Cuando un contenedor intenta conectarse a `localhost`, se conecta **a sí mismo**, no al host ni a otro contenedor. Los puertos publicados (`ports: - "3001:3000"`) solo sirven para el tráfico que viene **desde fuera de Docker** (tu navegador, Postman).

Además existía un **conflicto de puertos**: ORVIAN corría en el puerto `8080` del host y Evolution API también mapeaba su puerto interno `8080` al `8080` del host, causando un `address already in use`.

### La Solución

**1. Comunicación interna por nombre de servicio:**
Dentro de la misma red `app_network`, cada servicio es alcanzable por su nombre de contenedor. `evolution` puede contactar `chatwoot` en `http://chatwoot:3000` (el puerto **interno**, no el mapeado). Esta es la URL que se debe configurar en las variables de entorno de Evolution API.

**2. Remap del puerto de Evolution al 8085:**
Cambiando el mapeo de `"8080:8080"` a `"8085:8080"`, Evolution sigue escuchando en el puerto `8080` internamente (la imagen no cambia), pero desde el host —y desde ORVIAN— se accede en `http://localhost:8085`. El conflicto con ORVIAN queda eliminado.

```
# ANTES (con conflicto):
ports:
  - "8080:8080"   # ← Choca con ORVIAN en el host

# DESPUÉS (corregido):
ports:
  - "8085:8080"   # ← Host:8085 → Contenedor:8080. Sin conflicto.
```

**3. Variable `CHATWOOT_URL` apuntando al nombre de servicio:**
Evolution necesita saber dónde enviar los webhooks a Chatwoot. Esta URL debe usar el nombre del servicio Docker, no `localhost`:

```dotenv
# INCORRECTO (no funciona dentro de Docker):
CHATWOOT_URL=http://localhost:3001

# CORRECTO (nombre de servicio + puerto interno):
CHATWOOT_URL=http://chatwoot:3000
```

---

## Fase 1 — Infraestructura y Docker
**Rama:** `feature/comms-infra`

### 1.1 — `docker-compose.yml` Corregido (Chatwoot + Evolution)

El archivo a continuación incorpora todas las correcciones descritas en las Notas de Implementación. Este es el estado final que debe estar en el repositorio de infraestructura de comunicaciones (separado del de ORVIAN).

```yaml
version: '3.8'

services:
  # --- INFRAESTRUCTURA EVOLUTION ---
  evolution-db:
    image: postgres:16.4-alpine
    container_name: evolution-postgres
    restart: unless-stopped
    environment:
      POSTGRES_USER: ${POSTGRES_USER:-postgres}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-postgres123}
      POSTGRES_DB: ${POSTGRES_DB:-evolution_db}
    volumes:
      - evolution_postgres_data:/var/lib/postgresql/data
    networks:
      - app_network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
      interval: 10s
      timeout: 5s
      retries: 5

  evolution-redis:
    image: redis:7.2-alpine
    container_name: evolution-redis
    restart: unless-stopped
    command: ["redis-server", "--appendonly", "yes", "--requirepass", "${REDIS_PASSWORD:-redis123}"]
    volumes:
      - evolution_redis_data:/data
    networks:
      - app_network
    healthcheck:
      test: ["CMD", "redis-cli", "-a", "${REDIS_PASSWORD:-redis123}", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  # --- EVOLUTION API ---
  # CORRECCIÓN: Puerto mapeado al 8085 en el host para no colisionar con ORVIAN (8080).
  # El contenedor sigue escuchando en 8080 internamente; solo cambia el acceso desde el host.
  evolution:
    image: evoapicloud/evolution-api:v2.3.6
    container_name: evolution_api
    restart: unless-stopped
    environment:
      - SERVER_TYPE=http
      - SERVER_PORT=8080
      - LOG_LEVEL=ERROR,WARN,DEBUG,INFO
      - DATABASE_PROVIDER=postgresql
      - DATABASE_CONNECTION_URI=postgresql://${POSTGRES_USER:-postgres}:${POSTGRES_PASSWORD:-postgres123}@evolution-db:5432/${EVOLUTION_DB_NAME:-evolution_db}?schema=public
      - CACHE_REDIS_ENABLED=true
      - CACHE_REDIS_URI=redis://:${REDIS_PASSWORD:-redis123}@evolution-redis:6379
      - AUTHENTICATION_API_KEY=${EVOLUTION_API_KEY}
      - CHATWOOT_ENABLED=true
      # CORRECCIÓN: URL interna por nombre de servicio Docker, no localhost.
      - CHATWOOT_URL=http://chatwoot:3000
    ports:
      - "8085:8080"   # <-- CAMBIO CLAVE: 8085 en host, 8080 en contenedor
    volumes:
      - evolution_instances:/evolution/instances
      - evolution_store:/evolution/store
    networks:
      - app_network
    depends_on:
      evolution-db:
        condition: service_healthy
      evolution-redis:
        condition: service_healthy

  # --- INFRAESTRUCTURA CHATWOOT ---
  postgres:
    image: pgvector/pgvector:pg14
    container_name: chatwoot-postgres
    restart: always
    environment:
      POSTGRES_DB: chatwoot
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
    volumes:
      - chatwoot_postgres_data:/var/lib/postgresql/data
    networks:
      - app_network

  redis:
    image: redis:7
    container_name: chatwoot-redis
    restart: always
    networks:
      - app_network

  # --- CHATWOOT APP ---
  chatwoot:
    image: chatwoot/chatwoot:latest
    container_name: chatwoot
    depends_on:
      - postgres
      - redis
    ports:
      - "3001:3000"
    command: bundle exec rails s -p 3000 -b 0.0.0.0
    environment:
      RAILS_ENV: production
      SECRET_KEY_BASE: ${CHATWOOT_SECRET_KEY_BASE}
      FRONTEND_URL: ${CHATWOOT_FRONTEND_URL:-http://localhost:3001}
      REDIS_URL: redis://redis:6379
      POSTGRES_HOST: postgres
      POSTGRES_USERNAME: postgres
      POSTGRES_PASSWORD: postgres
      POSTGRES_DATABASE: chatwoot
      SMTP_ADDRESS: host.docker.internal
      SMTP_PORT: 1025
    networks:
      - app_network

  chatwoot-worker:
    image: chatwoot/chatwoot:latest
    container_name: chatwoot-worker
    depends_on:
      - postgres
      - redis
    command: bundle exec sidekiq
    environment:
      RAILS_ENV: production
      SECRET_KEY_BASE: ${CHATWOOT_SECRET_KEY_BASE}
      REDIS_URL: redis://redis:6379
      POSTGRES_HOST: postgres
      POSTGRES_USERNAME: postgres
      POSTGRES_PASSWORD: postgres
      POSTGRES_DATABASE: chatwoot
    networks:
      - app_network

networks:
  app_network:
    driver: bridge

volumes:
  evolution_postgres_data:
  evolution_redis_data:
  evolution_instances:
  evolution_store:
  chatwoot_postgres_data:
```

### 1.2 — `.env` del Módulo de Comunicaciones (Corregido)

```dotenv
# --- GENERAL DB CONFIG ---
POSTGRES_USER=postgres
POSTGRES_PASSWORD=postgres123
POSTGRES_DB=evolution_db
REDIS_PASSWORD=redis123

# --- EVOLUTION CONFIG ---
EVOLUTION_DB_NAME=evolution_db
EVOLUTION_API_KEY=evolution_api_key_12345
# URL pública que ORVIAN usará para llamar a Evolution desde el host:
EVOLUTION_API_URL=http://localhost:8085

# --- CHATWOOT CONFIG ---
CHATWOOT_SECRET_KEY_BASE=tu_clave_secreta_de_32_caracteres_minimo
CHATWOOT_FRONTEND_URL=http://localhost:3001
CHATWOOT_ENABLED=true
CHATWOOT_MESSAGE_READ=true
CHATWOOT_MESSAGE_DELETE=true
```

### 1.3 — Variables de Entorno en `.env` de ORVIAN

Agregar al `.env` principal del proyecto ORVIAN para que los servicios PHP puedan consumir las APIs:

```dotenv
# --- INTEGRACIÓN CHATWOOT ---
CHATWOOT_BASE_URL=http://localhost:3001
CHATWOOT_API_ACCESS_TOKEN=          # Token de la cuenta de Superadmin en Chatwoot
CHATWOOT_ACCOUNT_ID=1               # ID de la cuenta en Chatwoot (normalmente 1)
CHATWOOT_HMAC_TOKEN=                # Token HMAC para SSO (Identity Verification)

# --- INTEGRACIÓN EVOLUTION API (WhatsApp) ---
EVOLUTION_API_URL=http://localhost:8085
EVOLUTION_API_KEY=evolution_api_key_12345
EVOLUTION_INSTANCE_NAME=orvian_school  # Nombre de la instancia de WhatsApp
```

### 1.4 — Configuración `config/communications.php`

```php
<?php

return [

    'chatwoot' => [
        'base_url'     => env('CHATWOOT_BASE_URL', 'http://localhost:3001'),
        'api_token'    => env('CHATWOOT_API_ACCESS_TOKEN'),
        'account_id'   => env('CHATWOOT_ACCOUNT_ID', 1),
        'hmac_token'   => env('CHATWOOT_HMAC_TOKEN'),
        'iframe_url'   => env('CHATWOOT_BASE_URL', 'http://localhost:3001'),
    ],

    'evolution' => [
        'base_url'      => env('EVOLUTION_API_URL', 'http://localhost:8085'),
        'api_key'       => env('EVOLUTION_API_KEY'),
        'instance_name' => env('EVOLUTION_INSTANCE_NAME', 'orvian_school'),
    ],

    'notifications' => [
        // Umbral de ausencias en el mes para disparar alerta al tutor
        'absence_threshold'   => 3,
        // Umbral de tardanzas en el mes para disparar alerta al tutor
        'tardiness_threshold' => 3,
    ],

];
```

---

## Fase 2 — Identidad, Migración y SSO
**Rama:** `feature/comms-identity`

### 2.1 — Migración: `tutor_phone` en Tabla `students`

El campo `tutor_phone` es la pieza central de la integración con WhatsApp. Se añade a la tabla `students` existente (creada en v0.4.0) mediante una migración incremental, siguiendo el principio de no tocar migraciones anteriores.

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
            // Número en formato E.164 (ej. +18091234567).
            // Se añade después de los datos del tutor existentes para mantener coherencia semántica.
            $table->string('tutor_phone', 20)
                  ->nullable()
                  ->after('tutor_name')
                  ->comment('Número de WhatsApp del tutor en formato E.164. Ej: +18091234567');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('tutor_phone');
        });
    }
};
```

- [ ] **Actualizar `$fillable` del modelo `Student`** para incluir `'tutor_phone'`.
- [ ] **Actualizar `StudentService`** para permitir guardar/actualizar este campo.
- [ ] **Actualizar formulario de edición de estudiante** con campo de teléfono del tutor, con validación de formato E.164 en el request.

### 2.2 — `ChatwootService` — Cliente HTTP

Servicio que encapsula toda comunicación con la API REST de Chatwoot. Es la única clase autorizada a hacer llamadas HTTP hacia Chatwoot.

```php
<?php

namespace App\Services\Communications;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class ChatwootService
{
    protected string $baseUrl;
    protected string $apiToken;
    protected int $accountId;

    public function __construct()
    {
        $this->baseUrl   = config('communications.chatwoot.base_url');
        $this->apiToken  = config('communications.chatwoot.api_token');
        $this->accountId = config('communications.chatwoot.account_id');
    }

    /**
     * Genera el identifier_hash para SSO/Identity Verification de Chatwoot.
     * El hash se construye con HMAC-SHA256 usando el email del usuario como mensaje
     * y el HMAC Token de Chatwoot como clave secreta.
     */
    public function generateIdentifierHash(string $email): string
    {
        return hash_hmac('sha256', $email, config('communications.chatwoot.hmac_token'));
    }

    /**
     * Crea un agente en Chatwoot. Llamado por el UserObserver al registrar un Director.
     */
    public function createAgent(string $name, string $email, string $role = 'agent'): Response
    {
        return Http::withHeaders(['api_access_token' => $this->apiToken])
            ->post("{$this->baseUrl}/auth/sign_in", []) // Primero verificar conectividad
            ->throw();

        // Endpoint real de creación de agente:
        return Http::withHeaders(['api_access_token' => $this->apiToken])
            ->post("{$this->baseUrl}/api/v1/accounts/{$this->accountId}/agents", [
                'name'              => $name,
                'email'             => $email,
                'role'              => $role,
                'availability_status' => 'online',
            ]);
    }

    /**
     * Busca un agente por email. Útil para evitar duplicados en el Observer.
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
}
```

### 2.3 — `UserObserver` — Creación Automática de Agente en Chatwoot

Cuando un Director se registra en ORVIAN (o se le asigna el rol `School Principal`), debe existir automáticamente como Agente en Chatwoot para poder recibir conversaciones. El Observer garantiza esta sincronización.

```php
<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Communications\ChatwootService;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserObserver
{
    public function __construct(protected ChatwootService $chatwootService) {}

    /**
     * Se dispara cuando se asigna un rol al usuario (via Spatie).
     * Interceptamos el evento `saved` del modelo User tras la asignación de rol.
     * Para mayor precisión, escucha el evento en SchoolConfigured Listener.
     */
    public function created(User $user): void
    {
        // La sincronización a Chatwoot se hace en el Listener SchoolConfigured
        // para garantizar que el rol ya fue asignado. Ver Fase 2.4.
    }

    /**
     * Hook principal: detecta si el usuario tiene rol de Director/Principal
     * y lo registra como agente en Chatwoot si aún no existe.
     */
    public function syncToChatwoot(User $user): void
    {
        // Solo sincronizar si el usuario tiene rol de School Principal
        if (! $user->hasRole('School Principal')) {
            return;
        }

        // Evitar duplicados: verificar si ya existe en Chatwoot
        $existingAgent = $this->chatwootService->findAgentByEmail($user->email);

        if ($existingAgent) {
            Log::info("UserObserver: Agente ya existe en Chatwoot para {$user->email}");
            return;
        }

        try {
            $response = $this->chatwootService->createAgent(
                name:  $user->name,
                email: $user->email,
                role:  'agent',
            );

            if ($response->successful()) {
                Log::info("UserObserver: Agente creado en Chatwoot para {$user->email}");
                // Guardar el ID de Chatwoot en el usuario para referencias futuras
                $user->updateQuietly([
                    'preferences' => array_merge($user->preferences ?? [], [
                        'chatwoot_agent_id' => $response->json('id'),
                    ]),
                ]);
            }
        } catch (\Throwable $e) {
            // No romper el flujo de ORVIAN si Chatwoot no está disponible
            Log::error("UserObserver: Fallo al crear agente en Chatwoot", [
                'user'  => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

> **Nota de integración:** El método `syncToChatwoot` debe ser invocado explícitamente desde el Listener `AssignInitialRoles` (creado en v0.2.0), después de confirmar el rol de Director. Esto garantiza que el rol ya esté asignado antes de llamar a `hasRole()`, evitando condiciones de carrera.

### 2.4 — Actualización del Listener `AssignInitialRoles`

```php
<?php

namespace App\Listeners\Tenant;

use App\Events\Tenant\SchoolConfigured;
use App\Observers\UserObserver;

class AssignInitialRoles
{
    public function __construct(protected UserObserver $userObserver) {}

    public function handle(SchoolConfigured $event): void
    {
        $school    = $event->school;
        $principal = $event->principal;

        // Asegurar que el rol School Principal está asignado en el scope del tenant
        $principal->assignRole('School Principal');

        // Sincronizar el Director recién configurado a Chatwoot como Agente
        $this->userObserver->syncToChatwoot($principal);
    }
}
```

- [ ] **Registrar `UserObserver`** en `App\Providers\AppServiceProvider`:
  ```php
  User::observe(UserObserver::class);
  ```

---

## Fase 3 — WhatsApp Service (Evolution API)
**Rama:** `feature/whatsapp-service`

### 3.1 — `WhatsAppService`

Encapsula toda comunicación con Evolution API. Diseñado para ser stateless y fácilmente testeable mediante mocks.

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
     * Envía un mensaje de texto a un número en formato E.164.
     *
     * @param  string  $phone  Número destino. Ej: +18091234567
     * @param  string  $message  Texto del mensaje.
     */
    public function sendTextMessage(string $phone, string $message): bool
    {
        // Evolution API espera el número sin el símbolo +
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
                    'status' => $response->json('key.id'),
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
     * Verifica si la instancia de WhatsApp está conectada (útil para UI de estado).
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

### 3.2 — Plantillas de Mensajes — `app/Services/Communications/WhatsAppTemplates.php`

Centraliza los mensajes para facilitar traducción y mantenimiento. Ningún texto de notificación debe estar hardcodeado fuera de esta clase.

```php
<?php

namespace App\Services\Communications;

use App\Models\Tenant\Student;

class WhatsAppTemplates
{
    /**
     * Alerta por ausencias acumuladas en el mes.
     */
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

    /**
     * Alerta por tardanzas acumuladas en el mes.
     */
    public static function tardinessAlert(Student $student, int $count, string $month): string
    {
        return <<<MSG
        ⏰ *ORVIAN — Aviso de Puntualidad*

        Estimado/a tutor(a) de *{$student->full_name}*,

        Le notificamos que su representado/a ha registrado *{$count} llegada(s) tarde* durante el mes de {$month}.

        La puntualidad es fundamental para el aprovechamiento académico. Le agradecemos su atención a este comunicado.

        _Este mensaje es generado automáticamente por el Sistema de Gestión ORVIAN._
        MSG;
    }
}
```

---

## Fase 4 — Integración con el Módulo de Asistencia
**Rama:** `feature/attendance-notifications`

Esta fase conecta la lógica de asistencia de v0.4.0 con el nuevo motor de notificaciones. No modifica el core del módulo de asistencia; lo extiende mediante un Job desacoplado.

### 4.1 — Job `SendAttendanceAlertJob`

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

    public int $tries = 3;
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
            Log::warning('SendAttendanceAlertJob: Estudiante sin tutor_phone', [
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
        Log::error('SendAttendanceAlertJob: Falló definitivamente', [
            'student_id' => $this->student->id,
            'type'       => $this->type,
            'error'      => $exception->getMessage(),
        ]);
    }
}
```

### 4.2 — Servicio de Evaluación de Umbrales `AttendanceAlertEvaluator`

Contiene la lógica de negocio pura: evalúa si un estudiante ha superado el umbral y, si es así, despacha el Job. **No envía mensajes directamente**; delega al Job para mantener la asincronía.

```php
<?php

namespace App\Services\Communications;

use App\Jobs\Communications\SendAttendanceAlertJob;
use App\Models\Tenant\Student;
use App\Models\Tenant\PlantelAttendanceRecord;
use Illuminate\Support\Carbon;

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
     * Evalúa a un estudiante al finalizar el registro de asistencia del día.
     * Llamado desde PlantelAttendanceService::closeDay() o desde un comando programado.
     */
    public function evaluate(Student $student): void
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();
        $monthName    = Carbon::now()->translatedFormat('F Y');

        $records = PlantelAttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $absences   = $records->where('status', 'absent')->count();
        $tardiness  = $records->where('status', 'late')->count();

        // Despachar alerta de ausencias si supera el umbral
        if ($absences >= $this->absenceThreshold) {
            // Verificar que no se haya enviado ya esta semana para no saturar al tutor
            $cacheKey = "alert_absence_{$student->id}_" . Carbon::now()->weekOfYear;
            if (! cache()->has($cacheKey)) {
                SendAttendanceAlertJob::dispatch($student, 'absence', $absences, $monthName);
                cache()->put($cacheKey, true, now()->addDays(7));
            }
        }

        // Despachar alerta de tardanzas si supera el umbral
        if ($tardiness >= $this->tardinessThreshold) {
            $cacheKey = "alert_tardiness_{$student->id}_" . Carbon::now()->weekOfYear;
            if (! cache()->has($cacheKey)) {
                SendAttendanceAlertJob::dispatch($student, 'tardiness', $tardiness, $monthName);
                cache()->put($cacheKey, true, now()->addDays(7));
            }
        }
    }
}
```

### 4.3 — Comando Programado `orvian:evaluate-attendance-alerts`

Permite ejecutar la evaluación de forma masiva, desacoplada del flujo de registro del día. Registrar en `routes/console.php`.

```php
<?php

namespace App\Console\Commands;

use App\Models\Tenant\Student;
use App\Services\Communications\AttendanceAlertEvaluator;
use Illuminate\Console\Command;

class EvaluateAttendanceAlertsCommand extends Command
{
    protected $signature   = 'orvian:evaluate-attendance-alerts {--school=}';
    protected $description = 'Evalúa umbrales de asistencia y despacha notificaciones WhatsApp a tutores.';

    public function handle(AttendanceAlertEvaluator $evaluator): int
    {
        $query = Student::query()->active()->with('section');

        if ($schoolId = $this->option('school')) {
            $query->where('school_id', $schoolId);
        }

        $students = $query->get();

        $this->withProgressBar($students, fn (Student $student) => $evaluator->evaluate($student));

        $this->newLine();
        $this->info("Evaluación completada para {$students->count()} estudiante(s).");

        return Command::SUCCESS;
    }
}
```

```php
// routes/console.php — Programar ejecución diaria a las 4:00 PM
Schedule::command('orvian:evaluate-attendance-alerts')
    ->dailyAt('16:00')
    ->description('Evalúa alertas de asistencia y notifica tutores por WhatsApp');
```

---

## Fase 5 — UI: Centro de Mensajes e Iframe
**Rama:** `feature/comms-ui`

### 5.1 — Actualización de `x-ui.app-tile` — Badge Dinámico de Mensajes

El componente ya soporta un prop `badge` estático. La actualización consiste en alimentar ese badge desde el controlador con el conteo de conversaciones pendientes de Chatwoot. No se modifica la lógica interna del componente; solo cambia cómo se le pasa el dato.

```php
// En el controlador del Dashboard (app/Livewire/App/Dashboard.php)
// Añadir computed property para contar conversaciones pendientes en Chatwoot

use App\Services\Communications\ChatwootService;

public function getPendingConversationsCountProperty(): int
{
    try {
        $chatwoot  = app(ChatwootService::class);
        $agentId   = auth()->user()->preferences['chatwoot_agent_id'] ?? null;

        if (! $agentId) return 0;

        $response = Http::withHeaders(['api_access_token' => config('communications.chatwoot.api_token')])
            ->get(config('communications.chatwoot.base_url') . "/api/v1/accounts/" 
                . config('communications.chatwoot.account_id') 
                . "/conversations", [
                    'status'    => 'open',
                    'assignee_type' => 'assigned',
                ]);

        return $response->successful() 
            ? ($response->json('data.meta.all_count') ?? 0) 
            : 0;

    } catch (\Throwable) {
        return 0;
    }
}
```

```blade
{{-- En resources/views/app/dashboard.blade.php --}}
{{-- Tile de Conversaciones con badge dinámico --}}
<x-ui.app-tile
    module="conversaciones"
    title="Conversaciones"
    url="{{ route('app.conversations.index') }}"
    :badge="$this->pendingConversationsCount > 0 ? $this->pendingConversationsCount : null"
/>
```

### 5.2 — Vista de Centro de Mensajes con Iframe y SSO

El Iframe de Chatwoot requiere una URL especial con parámetros de autenticación embebidos para el SSO. La vista blade construye esa URL server-side para que el token HMAC nunca quede expuesto en el frontend.

```blade
{{-- resources/views/app/conversations/index.blade.php --}}
@extends('layouts.app-module', config('modules.conversaciones'))

@section('content')
<div class="flex flex-col h-[calc(100vh-3.5rem-3rem)] -mx-4 -mb-4">

    {{-- Barra superior del módulo --}}
    <x-app.module-toolbar>
        <x-slot:actions>
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">
                Centro de Mensajes
            </span>
        </x-slot:actions>
        <x-slot:secondary>
            {{-- Indicador de estado de WhatsApp --}}
            <livewire:app.conversations.whatsapp-status-indicator />
        </x-slot:secondary>
    </x-app.module-toolbar>

    {{-- Iframe de Chatwoot (SSO ya resuelto server-side) --}}
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

```php
<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Communications\ChatwootService;

class ConversationsController extends Controller
{
    public function index(ChatwootService $chatwoot)
    {
        $user = auth()->user();

        // Construir la URL de Chatwoot con SSO para usuarios normales (Directores/Agentes)
        // El identifier_hash es el HMAC del email del usuario, firmado con el token secreto de Chatwoot
        $identifierHash = $chatwoot->generateIdentifierHash($user->email);

        $chatwootBase = config('communications.chatwoot.iframe_url');

        // URL de Chatwoot con parámetros de Identity Verification (SSO automático)
        $chatwootUrl = "{$chatwootBase}?email=" . urlencode($user->email)
            . "&identifier_hash={$identifierHash}"
            . "&name=" . urlencode($user->name);

        // Superadmin: accede sin SSO al panel de administración global
        if ($user->hasRole('Owner') || $user->hasRole('TechnicalSupport')) {
            $chatwootUrl = $chatwootBase; // Login manual; sin parámetros de SSO
        }

        return view('app.conversations.index', compact('chatwootUrl'));
    }
}
```

### 5.3 — Vista de Administración de Chatwoot para SuperAdmin (Admin Hub)

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

    <div class="flex-1 mt-4 rounded-xl overflow-hidden border border-slate-200 dark:border-white/[0.07] shadow-sm">
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

### 5.4 — Componente Livewire `WhatsappStatusIndicator`

Muestra el estado de conexión del número de WhatsApp institucional en la barra superior del módulo.

```php
<?php

namespace App\Livewire\App\Conversations;

use App\Services\Communications\WhatsAppService;
use Livewire\Component;
use Livewire\Attributes\Lazy;

#[Lazy]
class WhatsappStatusIndicator extends Component
{
    public array $status = [];

    public function mount(WhatsAppService $whatsApp): void
    {
        $this->status = $whatsApp->getInstanceStatus();
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
    $connected = ($status['state'] ?? '') === 'open';
    $label     = $connected ? 'WhatsApp Conectado' : 'WhatsApp Desconectado';
    $variant   = $connected ? 'success' : 'error';
@endphp

<x-ui.badge :variant="$variant" :dot="true" class="text-xs">
    {{ $label }}
</x-ui.badge>
```

---

## Fase 6 — Rutas, Permisos y Configuración de Módulo
**Rama:** `feature/comms-config`

### 6.1 — Rutas en `routes/app/conversations.php`

```php
<?php

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

### 6.2 — Actualización de `config/modules.php`

```php
'conversaciones' => [
    'name'   => 'Conversaciones',
    'icon'   => 'conversaciones',   // SVG en public/assets/icons/modules/conversaciones.svg
    'moduleLinks' => [
        ['label' => 'Centro de Mensajes', 'route' => 'app.conversations.index'],
    ],
],
```

### 6.3 — Nuevos Permisos y Seeders

- [ ] **Agregar grupo `communications` en `PermissionGroupSeeder`** con `context = 'tenant'`.
- [ ] **Agregar permisos en `PermissionSeeder`:**
  - `view conversations`
  - `manage conversations`
  - `send whatsapp notifications`
- [ ] **Actualizar `RoleAcademicSeeder`:**
  - `School Principal` → todos los permisos del grupo `communications`.
  - `Academic Coordinator` → solo `view conversations`.
  - `Teacher` → sin permisos de communications.
- [ ] **Agregar permisos de admin en `PermissionSeeder` (context = 'global'):**
  - `admin conversations`

---

## Checklist de Completitud v0.5.0

### Infraestructura
- [ ] `docker-compose.yml` corregido con puerto `8085` para Evolution
- [ ] Variables `CHATWOOT_URL=http://chatwoot:3000` configuradas en Evolution
- [ ] `.env` de ORVIAN actualizado con tokens de Chatwoot y Evolution
- [ ] `config/communications.php` creado

### Base de Datos
- [ ] Migración `add_tutor_phone_to_students_table` ejecutada
- [ ] Modelo `Student` con `tutor_phone` en `$fillable`

### Integración Chatwoot
- [ ] `ChatwootService` implementado y bindeado en `AppServiceProvider`
- [ ] `UserObserver::syncToChatwoot()` implementado
- [ ] Listener `AssignInitialRoles` actualizado para llamar a `syncToChatwoot()`
- [ ] SSO con `identifier_hash` generando correctamente en `ConversationsController`

### WhatsApp (Evolution API)
- [ ] `WhatsAppService` implementado
- [ ] `WhatsAppTemplates` con plantillas de ausencia y tardanza
- [ ] `AttendanceAlertEvaluator` con lógica de umbrales y caché anti-spam
- [ ] `SendAttendanceAlertJob` con reintentos configurados
- [ ] Comando `orvian:evaluate-attendance-alerts` registrado y programado

### Frontend
- [ ] Dashboard alimenta badge de `conversaciones` con conversaciones pendientes
- [ ] Vista `app/conversations/index.blade.php` con Iframe SSO
- [ ] Vista `admin/conversations/index.blade.php` con Iframe admin
- [ ] Componente `WhatsappStatusIndicator` implementado
- [ ] Tile `conversaciones` activado en `app/dashboard.blade.php`
- [ ] SVG del módulo `conversaciones.svg` añadido a `public/assets/icons/modules/`

### Rutas y Permisos
- [ ] Rutas en `routes/app/conversations.php` y `routes/admin/conversations.php`
- [ ] `config/modules.php` actualizado con módulo `conversaciones`
- [ ] Permisos y grupos añadidos en Seeders
- [ ] Roles actualizados para incluir permisos de `communications`

### Documentación
- [ ] `docs/architecture/communications.md`
- [ ] `docs/modules/conversations.md`

---

## Notas de Implementación

**Orden de dependencias obligatorio:**
1. Fase 1 (Docker + .env) antes que cualquier otra. Sin la infraestructura corriendo, ninguna prueba de integración es posible.
2. Fase 2 (Migración de BD y `ChatwootService`) antes de tocar el Observer o el SSO.
3. Fase 3 (`WhatsAppService`) puede desarrollarse en paralelo con la Fase 2; son independientes.
4. Fase 4 (Job y Evaluador) requiere que la Fase 3 esté completa y que la instancia de WhatsApp esté conectada.
5. Fase 5 (UI/Iframe) puede comenzarse en paralelo con las Fases 2 y 3, pero el SSO no se puede validar sin Chatwoot corriendo.

**Anti-spam en notificaciones:**
El `AttendanceAlertEvaluator` usa una clave de caché con granularidad semanal (`weekOfYear`) para garantizar que un tutor no reciba más de una alerta del mismo tipo por semana, incluso si el comando se ejecuta varias veces al día. El TTL de la caché es de 7 días.

**Seguridad del SSO:**
El `identifier_hash` (HMAC-SHA256) nunca debe generarse en el cliente. Siempre se computa en el servidor en `ConversationsController` y se inyecta directamente en el atributo `src` del Iframe. El `CHATWOOT_HMAC_TOKEN` debe tratarse con el mismo nivel de confidencialidad que `APP_KEY`.

**Consideraciones de privacidad:**
El número `tutor_phone` de un estudiante solo debe ser visible para usuarios con el permiso `manage conversations` o superior. No debe aparecer en listados de estudiantes accesibles a roles de `Teacher`. Aplicar scope o política de autorización en el modelo.

**Tiempo estimado:** 3–4 semanas de desarrollo (con equipo de 2–3 desarrolladores)

### Análisis Técnico y Oportunidades de Mejora

| Componente / Fase | Observación Técnica | Recomendación |
| :--- | :--- | :--- |
| **AttendanceAlertEvaluator (Caché)** | La clave de caché usa `weekOfYear` para anti-spam. Si el driver de caché es `file` en desarrollo, los reinicios del servidor limpian la caché y pueden re-enviar alertas. | Configurar `CACHE_DRIVER=redis` en producción. El mismo Redis de ORVIAN puede usarse; no requiere infraestructura adicional. |
| **ChatwootService (createAgent)** | El snippet de `createAgent` tiene un bloque `throw()` de depuración antes del endpoint real. | Eliminar el bloque de sign_in antes de hacer PR. Es un artefacto de desarrollo. |
| **UserObserver (Acoplamiento)** | Si Chatwoot no está disponible en el momento del onboarding, el Director no se crea como agente y el SSO fallará silenciosamente. | Considerar un Job de reintento `SyncUserToChatwootJob` como fallback, con lógica de backoff exponencial. |
| **Iframe (Content-Security-Policy)** | Si ORVIAN tiene un CSP estricto en sus headers, el Iframe de Chatwoot puede ser bloqueado por el navegador. | Añadir `frame-src http://localhost:3001` (o la URL de producción de Chatwoot) a la directiva CSP en el middleware de ORVIAN. |
| **WhatsAppService (E.164)** | La normalización `ltrim($phone, '+')` es simple pero no valida que el número sea realmente E.164 antes de enviar. | Agregar validación con `libphonenumber` (vía `giggsey/libphonenumber-for-php`) al guardar `tutor_phone` en la migración o en un Request de validación. |