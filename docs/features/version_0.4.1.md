# ORVIAN v0.4.1 — Core Polish & Access Refactor

**RAMA DE TRABAJO:**
`refactor/core-polish`

**Objetivo:** Estabilizar el sistema de cara a la demostración. Esta versión no introduce nuevos módulos funcionales. Se enfoca exclusivamente en tres áreas: refactorizar el flujo de identidad y autenticación, establecer una utilidad global de versión y simplificar la interfaz de usuario eliminando ruido visual en componentes clave. El módulo de Comunicaciones queda pausado indefinidamente hasta nueva decisión del equipo.

---

## Tabla de Requerimientos

| ID | Fase | Área | Descripción | Prioridad | Estado |
| :-- | :-- | :-- | :-- | :-- | :-- |
| REQ-01 | 1 | Auth / Observer | Generar `User` automático al crear/importar un `Student` desde `StudentObserver@created` | Alta | Pendiente |
| REQ-02 | 1 | Auth / Routing | Redirección post-login diferenciada según `school_id` del usuario autenticado | Alta | Pendiente |
| REQ-03 | 1 | Auth / UI | Nueva interfaz de Login (estructura Blade preparada para recibir el HTML definitivo) | Alta | Pendiente |
| REQ-04 | 2 | Config / Global | Lectura del archivo `VERSION` desde `base_path()` con caché y `View::share` global | Media | Pendiente |
| REQ-05 | 3 | UI / Dashboard | Ocultar/desactivar visualmente los tiles de módulos incompletos en `app/dashboard` y `config/modules.php` | Alta | Pendiente |

---

## Fase 1 — Refactor de Identidad y Autenticación
**Rama:** `refactor/auth-identity`

### 1.1 — StudentObserver: Generación Automática de Usuario

**Archivo:** `app/Observers/Tenant/StudentObserver.php`

**Contexto:** Actualmente el Observer genera el `qr_code` en el evento `creating`. Se extiende el evento `created` para crear el `User` asociado usando el `rnc` del estudiante como base del email.

**Regla de negocio:**
- Email generado: `{rnc_limpio}@orvian.com.do` donde `rnc_limpio` es el RNC sin guiones.
- Ejemplo: RNC `402-1234567-8` → email `40212345678@orvian.com.do`
- Solo se crea el usuario si el estudiante tiene `rnc` no nulo y no existe ya un `User` con ese email.
- El usuario se crea desactivado (`status = 'inactive'`) hasta que el Director lo habilite manualmente.
- La contraseña temporal es el propio `rnc_limpio`. El estudiante deberá cambiarla en el primer login.
- Se asigna automáticamente el rol `Student` en el scope del tenant (`school_id` del estudiante).

- [x] **Modificar `StudentObserver@created`:**
  ```php
  public function created(Student $student): void
  {
      // Generar QR ya se hace en `creating`, aquí solo el User
      if (blank($student->rnc) || User::where('email', $this->buildEmail($student->rnc))->exists()) {
          return;
      }

      $email = $this->buildEmail($student->rnc);
      $passwordRaw = $this->cleanRnc($student->rnc);

      $user = User::create([
          'name'       => $student->full_name,
          'email'      => $email,
          'password'   => Hash::make($passwordRaw),
          'school_id'  => $student->school_id,
          'status'     => 'inactive',
      ]);

      // Asignar rol Student en scope del tenant
      setPermissionsTeamId($student->school_id);
      $user->assignRole('Student');

      // Vincular el user_id al estudiante
      $student->updateQuietly(['user_id' => $user->id]);
  }

  private function buildEmail(string $rnc): string
  {
      return $this->cleanRnc($rnc) . '@orvian.com.do';
  }

  private function cleanRnc(string $rnc): string
  {
      return str_replace('-', '', $rnc);
  }
  ```


#### 1.1.1 — Ajustes en el Formulario de Estudiante (`StudentForm`)

Como consecuencia de delegar la creación del `User` al Observer, el componente Livewire `StudentForm` y sus vistas fueron actualizados:

- **Creación manual eliminada:** Se eliminó el bloque `User::create(...)` + `assignRole()` del método `save()`. El Observer lo maneja automáticamente al persistir el `Student`.
- **`user_id` removido del payload de `createStudent()`:** El Observer vincula el `user_id` vía `updateQuietly()` después del `created`.
- **Email derivado reactivo:** Se agregó `updatedRnc()` en el componente; al tipear el documento el campo email se actualiza en tiempo real con el patrón `{rnc_limpio}@orvian.com.do`. El RNC usa `wire:model.live`.
- **Email en readonly:** El campo email es `readonly` tanto en creación como en edición. En creación muestra el email calculado; en edición muestra el email real del usuario.
- **Email eliminado de validación:** La regla `email` fue removida de `rules()` — no es un dato que ingrese el operador.
- **Solo nombre se actualiza en edición:** El `user->update()` del branch edit solo actualiza `name`; el email es inmutable una vez generado por el Observer.

#### 1.1.2 — Ajustes en la Vista de Perfil del Estudiante (`StudentShow`)

- **Email readonly en credenciales:** El campo "Correo Electrónico de Acceso" en la sección "Seguridad y Acceso" es ahora `readonly`. El operador solo puede cambiar la contraseña.
- **`updateCredentials()` simplificado:** Validación y persistencia de email eliminadas del método; solo actualiza la contraseña si se provee una nueva.

> **Nota de rendimiento:** En importaciones de 500+ estudiantes, la creación individual de `User` por Observer puede generar latencia. Si esto se convierte en un problema medible, extraer la lógica a un Job encadentable (`CreateStudentUserJob`) que se dispatch en el Observer con `dispatch()->afterCommit()`, manteniendo el Observer limpio.

---

### 1.2 — Redirección Inteligente Post-Login

**Archivo:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

**Regla de negocio:**
- `school_id === null` → usuario global (Owner, TechnicalSupport, Administrative) → redirigir a `admin.hub`
- `school_id !== null` → usuario de escuela (Director, Teacher, Student, etc.) → redirigir a `app.dashboard`

- [x] **Modificar `AuthenticatedSessionController@store`** para retornar la redirección diferenciada:
  ```php
  public function store(LoginRequest $request): RedirectResponse
  {
      $request->authenticate();
      $request->session()->regenerate();

      return redirect()->intended(
          is_null(Auth::user()->school_id)
              ? route('admin.hub')
              : route('app.dashboard')
      );
  }
  ```


---

### 1.3 — Nueva Interfaz de Login (Estructura Blade)

**Archivo:** `resources/views/auth/login.blade.php`

- [x] **Preparar la estructura del layout de login** para recibir el HTML definitivo. El diseño de referencia (captura `screen.png`) muestra una composición de dos columnas: panel oscuro con identidad de marca a la izquierda, formulario sobre fondo claro a la derecha.

- [x] **Crear/reemplazar `resources/views/auth/login.blade.php`** con la siguiente estructura base lista para recibir el HTML que se entregará en la siguiente iteración:
  ```blade
  {{-- resources/views/auth/login.blade.php --}}
  <x-guest-layout>
      {{--
        | Estructura ORVIAN Login — v0.4.1
        | Diseño: dos columnas (brand | form)
        | El HTML definitivo se aplica en la siguiente iteración.
        | Variables disponibles: $appVersion (compartida globalmente por AppServiceProvider)
      --}}
      <div class="min-h-screen flex">

          {{-- Columna Izquierda: Identidad de Marca --}}
          <div class="hidden lg:flex lg:w-[55%] bg-[#111111] flex-col justify-between p-12 relative overflow-hidden">
              {{-- Contenido de marca: logo, tagline, status bar --}}
              {{-- Slot para el HTML de marca que se entregará --}}
          </div>

          {{-- Columna Derecha: Formulario --}}
          <div class="w-full lg:w-[45%] flex items-center justify-center bg-white dark:bg-gray-900 p-8">
              <div class="w-full max-w-sm">
                  {{-- Slot para el formulario que se entregará --}}
                  <x-auth-session-status class="mb-4" :status="session('status')" />

                  <form method="POST" action="{{ route('login') }}">
                      @csrf
                      {{-- Campos del formulario --}}
                  </form>
              </div>
          </div>
      </div>
  </x-guest-layout>
  ```

- [x] **Verificar que `x-guest-layout` no tiene `overflow: hidden` o `max-width` que rompa la composición de pantalla completa.**
- [x] **Importar fuente + actualizar tailwind.config.js**

---

## Fase 2 — Utilidad de Versión Global
**Rama:** `refactor/version-utility`

**Archivo:** `app/Providers/AppServiceProvider.php`

**Contexto:** El archivo `VERSION` en la raíz del proyecto contiene únicamente el número de versión en texto plano (ej. `0.4.1`). Esta fase lo expone a todas las vistas Blade sin necesidad de repetir la lectura en cada controlador.

### 2.1 — Implementación en AppServiceProvider

- [x] **Agregar en el método `boot()` de `AppServiceProvider`:**
  ```php
  use Illuminate\Support\Facades\Cache;
  use Illuminate\Support\Facades\View;

  public function boot(): void
  {
      // ... registros existentes (Observer de Plan, paginator, etc.)

      // Versión global de la aplicación
      $version = Cache::rememberForever('orvian.app_version', function () {
          $path = base_path('VERSION');
          return file_exists($path) ? trim(file_get_contents($path)) : 'dev';
      });

      View::share('appVersion', $version);
  }
  ```

- [x] **Actualizar el archivo `VERSION` en la raíz del proyecto:**
  ```
  0.4.1
  ```

- [x] **Invalidar la caché al desplegar** — agregar al script de deploy (o `post-autoload-dump` en `composer.json`):
  ```bash
  php artisan cache:forget orvian.app_version
  ```
  > Alternativamente, usar `Cache::forget('orvian.app_version')` en un comando Artisan `orvian:version-refresh` para mayor control.

### 2.2 — Uso en Vistas y Layouts

Una vez implementado, la variable `$appVersion` está disponible en cualquier Blade:

```blade
{{-- Ejemplo de uso en el layout del login (panel de marca) --}}
<span class="badge">V{{ $appVersion }}</span>

{{-- Ejemplo en el sidebar admin --}}
<span class="text-xs text-gray-400">ORVIAN v{{ $appVersion }}</span>
```

> **Nota:** `View::share()` comparte la variable con **todas** las vistas renderizadas, incluidos componentes anónimos y Livewire (cuando el componente renderiza su Blade). No es necesario pasarla explícitamente por prop.

---

## Fase 3 — Simplificación de UI
**Rama:** `refactor/ui-simplification`

### 3.1 — Dashboard y config/modules.php: Visibilidad de Módulos

**Archivo :** ``resources/views/app/dashboard.blade.php`.

**Objetivo:** Para la demostración, mostrar solo los módulos funcionales. Los módulos pausados o incompletos (Comunicaciones, Inventario/Facturación y cualquier otro sin completar) se ocultan visualmente o se marcan con `comingSoon: true`.

- [x] **Comentar modulos:** `classrom`, `horarios` y `web` de `app/dashboard.blade.php` para que no se rendericen.

---

## Checklist de Completitud v0.4.1

### Fase 1 — Autenticación e Identidad
- [x] `StudentObserver@created` genera `User` a partir del `rnc`
- [x] Email generado con patrón `{rnc_limpio}@orvian.com.do`
- [x] Usuario creado con `status = 'inactive'` y rol `Student` asignado en scope del tenant
- [x] `student.user_id` actualizado con `updateQuietly()` tras crear el User
- [x] Caso de RNC nulo manejado (guard al inicio del método)
- [x] Caso de email duplicado manejado (guard con `User::where('email', ...)->exists()`)
- [x] Compatibilidad verificada con el Job de importación masiva de v0.4.0
- [x] `AuthenticatedSessionController@store` redirige según `school_id`
- [x] Ruta `admin.hub` verificada y existente
- [x] Prueba manual de redirección con ambos tipos de usuario
- [x] Vista `auth/login.blade.php` estructurada con dos columnas y lista para recibir HTML

### Fase 2 — Versión Global
- [x] `Cache::rememberForever('orvian.app_version', ...)` implementado en `AppServiceProvider@boot`
- [x] `View::share('appVersion', $version)` activo
- [x] Archivo `VERSION` en raíz del proyecto actualizado a `0.4.1`
- [x] Fallback `'dev'` implementado si el archivo no existe
- [x] Estrategia de invalidación de caché en deploy documentada/implementada
- [x] Variable `$appVersion` usada al menos en el layout del login (panel de marca)

### Fase 3 — UI Simplificada
- [x] Tiles de módulos incompletos (Comunicaciones, Inventario/Facturación) comentados en `app/dashboard.blade.php`

---

## Notas de Implementación

**Orden de ejecución recomendado:**

1. Fase 2 primero (sin dependencias, cambio aislado en `AppServiceProvider`)
2. Fase 3.1 (módulos del dashboard, requiere editar `config/modules.php` antes que las vistas)
3. Fase 1.2 (redirección post-login, cambio en el controlador)
4. Fase 1.1 (Observer de estudiantes, el más crítico — probar con un estudiante individual antes de importaciones masivas)
5. Fase 1.3 (estructura del login, preparar el slot, esperar entrega del HTML definitivo)

---

## Consideraciones de Rendimiento

| Componente | Observación | Recomendación |
| :--- | :--- | :--- |
| **Fase 2 — Cache de versión** | `Cache::rememberForever` elimina lecturas repetidas de disco. Sin embargo, en entornos con `CACHE_DRIVER=file`, todos los workers comparten el mismo archivo de caché. | No es un problema en desarrollo. En producción, si se usa Redis, el invalidado de caché con `cache:forget` es instantáneo para todos los workers. |
| **Fase 1.1 — Creación de User en Observer** | Un Observer sícrono crea un User por cada Student en el mismo request. En importaciones masivas esto puede generar N inserciones adicionales por cada fila del Excel, multiplicando el tiempo total. | Si se detecta latencia real en importaciones de +200 registros, mover la creación del User a `CreateStudentUserJob::dispatch($student)->afterCommit()`. La importación en sí usa chunks, así que el impacto puede ser menor de lo estimado. |
| **Fase 3.2 — Filtro de módulos** | `collect(config('modules'))->filter(...)` es una operación O(n) sobre un array pequeño (≤ 15 módulos). No representa costo computacional significativo. | Si el dashboard usa Livewire con `#[Lazy]`, el filtro ocurre en el primer render diferido, sin bloquear la carga inicial de la página. Sin acción adicional necesaria. |
---

## Archivos Modificados en esta Versión

| Archivo | Tipo de cambio | Fase |
| :--- | :--- | :--- |
| `app/Observers/Tenant/StudentObserver.php` | Modificación — evento `created` | 1.1 |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Modificación — método `store` | 1.2 |
| `resources/views/auth/login.blade.php` | Reemplazo — estructura nueva | 1.3 |
| `app/Providers/AppServiceProvider.php` | Modificación — método `boot` | 2.1 |
| `VERSION` | Actualización — `0.4.1` | 2.1 |
| `config/modules.php` | Modificación — flag `visible` por módulo | 3.2 |
| `resources/views/app/dashboard.blade.php` | Modificación — filtro de tiles visibles | 3.2 |

---

## Decisiones de Arquitectura Registradas

**Módulo de Comunicaciones — Pausa indefinida:** El módulo de Comunicaciones queda excluido de la hoja de ruta activa hasta nueva decisión del equipo. Su tile en el dashboard se oculta mediante el flag `visible: false` en `config/modules.php`. Las rutas definidas (si existen) deben retornar 404 en entorno de demostración. Esta decisión se revisará post-demo según prioridades del cliente.

**Redirección post-login en el controlador vs. en el modelo:** Se optó por mantener la lógica de redirección en `AuthenticatedSessionController` y no en el modelo `User`. El modelo no debe conocer rutas de la aplicación. El controlador es el lugar semánticamente correcto para este tipo de decisión de flujo.
