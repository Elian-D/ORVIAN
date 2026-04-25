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
| REQ-05 | 3 | UI / Toast | Eliminar la progress bar del componente `x-ui.toasts` — diseño minimalista estático | Media | Pendiente |
| REQ-06 | 3 | UI / Dashboard | Ocultar/desactivar visualmente los tiles de módulos incompletos en `app/dashboard` y `config/modules.php` | Alta | Pendiente |

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

- [ ] **Agregar en el método `boot()` de `AppServiceProvider`:**
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

- [ ] **Crear/actualizar el archivo `VERSION` en la raíz del proyecto:**
  ```
  0.4.1
  ```

- [ ] **Invalidar la caché al desplegar** — agregar al script de deploy (o `post-autoload-dump` en `composer.json`):
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

### 3.1 — Componente Toast: Eliminación de la Progress Bar

**Archivo:** `resources/views/components/ui/toasts.blade.php`

**Objetivo:** Remover la barra de progreso animada del HTML y su lógica JavaScript asociada (`percent`, `startTimer` interval, `pause`, `resume`). El toast pasa a ser estático: aparece, espera su duración y desaparece. El hover ya no pausa el timer.

- [ ] **Eliminar del HTML del toast** el bloque de la barra de progreso:
  ```blade
  {{-- ELIMINAR este bloque completo --}}
  <div class="absolute bottom-0 left-0 w-full h-1 bg-black/5 dark:bg-white/10">
      <div class="h-full transition-all ease-linear"
          :class="config.progressClass"
          :style="`width: ${percent}%`"></div>
  </div>
  ```

- [ ] **Eliminar del `toastItem` Alpine** las propiedades y métodos relacionados con el progreso:
  ```javascript
  // ELIMINAR estas propiedades del data object:
  remaining: toast.duration,
  interval: null,
  paused: false,
  percent: 100,

  // ELIMINAR estos métodos completos:
  startTimer() { ... },
  pause() { ... },
  resume() { ... },
  ```

- [ ] **Reemplazar la lógica del timer** por un simple `setTimeout`:
  ```javascript
  // REEMPLAZAR startTimer() por esto en init():
  init() {
      this.setConfig();
      setTimeout(() => { this.show = true; }, 50);
      setTimeout(() => { this.close(); }, toast.duration);
  },
  ```

- [ ] **Eliminar los handlers de hover** del elemento del toast:
  ```blade
  {{-- ELIMINAR estos dos atributos del div del toast --}}
  @mouseenter="pause()"
  @mouseleave="resume()"
  ```

- [ ] **Eliminar `progressClass` de `setConfig()`** — ya no es necesario en ningún config de variante. Los objetos de config quedan solo con `bgClass` e `iconClass`:
  ```javascript
  setConfig() {
      const configs = {
          success: { bgClass: '...', iconClass: 'text-emerald-500' },
          error:   { bgClass: '...', iconClass: 'text-red-500'     },
          warning: { bgClass: '...', iconClass: 'text-amber-500'   },
          info:    { bgClass: '...', iconClass: 'text-blue-500'    },
      };
      this.config = configs[toast.type] || configs.info;
  },
  ```

> **Impacto visual:** Los toasts quedan con un diseño más limpio y plano. Sin barra inferior, el padding del componente puede reducirse ligeramente (`pb-3` en lugar de `pb-4`) si se desea dar más aire. Esto es opcional y queda a criterio del equipo de diseño.

---

### 3.2 — Dashboard y config/modules.php: Visibilidad de Módulos

**Archivos:** `config/modules.php` y `resources/views/app/dashboard.blade.php` (o el componente Livewire equivalente).

**Objetivo:** Para la demostración, mostrar solo los módulos funcionales. Los módulos pausados o incompletos (Comunicaciones, Inventario/Facturación y cualquier otro sin completar) se ocultan visualmente o se marcan con `comingSoon: true`.

**Módulos a mostrar activos:**
- Académico (`academico`)
- Asistencia (`asistencia`)
- Configuración (`configuracion`)

**Módulos a deshabilitar visualmente:**
- Comunicaciones (`comunicaciones`)
- Inventario / Facturación (`inventario`)
- Cualquier otro tile sin implementación completa

#### 3.2.1 — Agregar flag `visible` en `config/modules.php`

- [ ] **Agregar la propiedad `visible` a cada módulo** en el array de configuración:
  ```php
  // config/modules.php

  return [
      'academico' => [
          'name'        => 'Académico',
          'icon'        => 'academico',
          'visible'     => true,   // Activo para la demo
          'moduleLinks' => [ ... ],
      ],

      'asistencia' => [
          'name'        => 'Asistencia',
          'icon'        => 'asistencia',
          'visible'     => true,   // Activo para la demo
          'moduleLinks' => [ ... ],
      ],

      'configuracion' => [
          'name'        => 'Configuración',
          'icon'        => 'configuracion',
          'visible'     => true,   // Activo para la demo
          'moduleLinks' => [ ... ],
      ],

      'comunicaciones' => [
          'name'        => 'Comunicaciones',
          'icon'        => 'comunicaciones',
          'visible'     => false,  // Pausado — no mostrar en demo
          'moduleLinks' => [],
      ],

      'inventario' => [
          'name'        => 'Inventario',
          'icon'        => 'inventario',
          'visible'     => false,  // Sin implementar
          'moduleLinks' => [],
      ],
      // ... resto de módulos con visible: false si corresponde
  ];
  ```

#### 3.2.2 — Filtrar tiles en el Dashboard

- [ ] **En el controlador o Livewire del dashboard**, filtrar el array de módulos antes de pasarlo a la vista:
  ```php
  // En el método render() del componente Livewire del dashboard,
  // o en el método del controlador:

  $modules = collect(config('modules'))
      ->filter(fn ($module) => $module['visible'] ?? false)
      ->all();
  ```

- [ ] **En la vista `app/dashboard.blade.php`**, iterar solo sobre `$modules` filtrados:
  ```blade
  @foreach ($modules as $key => $module)
      <x-ui.app-tile
          :module="$key"
          :title="$module['name']"
          :url="route('app.' . $key . '.dashboard')"
      />
  @endforeach
  ```

  > Si el dashboard actualmente tiene los tiles hardcodeados en Blade (no dinámicos), reemplazar los tiles de módulos pausados por nada, o comentarlos con una nota clara:
  > ```blade
  > {{-- COMUNICACIONES: pausado en v0.4.1, reactivar cuando retome desarrollo --}}
  > {{-- <x-ui.app-tile module="comunicaciones" title="Comunicaciones" comingSoon /> --}}
  > ```

#### 3.2.3 — Proteger las rutas de módulos pausados

- [ ] **Agregar middleware o verificación en los controladores** de los módulos pausados para evitar acceso directo por URL:
  ```php
  // Opción simple: en el constructor del controlador del módulo
  public function __construct()
  {
      $this->middleware(function ($request, $next) {
          if (! (config('modules.comunicaciones.visible') ?? false)) {
              abort(404);
          }
          return $next($request);
      });
  }
  ```
  > Si las rutas aún no están definidas, este punto no aplica. Solo es necesario si las rutas existen y se quiere evitar el acceso durante la demo.

---

## Checklist de Completitud v0.4.1

### Fase 1 — Autenticación e Identidad
- [ ] `StudentObserver@created` genera `User` a partir del `rnc`
- [ ] Email generado con patrón `{rnc_limpio}@orvian.com.do`
- [ ] Usuario creado con `status = 'inactive'` y rol `Student` asignado en scope del tenant
- [ ] `student.user_id` actualizado con `updateQuietly()` tras crear el User
- [ ] Caso de RNC nulo manejado (guard al inicio del método)
- [ ] Caso de email duplicado manejado (guard con `User::where('email', ...)->exists()`)
- [ ] Compatibilidad verificada con el Job de importación masiva de v0.4.0
- [ ] `AuthenticatedSessionController@store` redirige según `school_id`
- [ ] Ruta `admin.hub` verificada y existente
- [ ] Prueba manual de redirección con ambos tipos de usuario
- [ ] Vista `auth/login.blade.php` estructurada con dos columnas y lista para recibir HTML

### Fase 2 — Versión Global
- [ ] `Cache::rememberForever('orvian.app_version', ...)` implementado en `AppServiceProvider@boot`
- [ ] `View::share('appVersion', $version)` activo
- [ ] Archivo `VERSION` en raíz del proyecto actualizado a `0.4.1`
- [ ] Fallback `'dev'` implementado si el archivo no existe
- [ ] Estrategia de invalidación de caché en deploy documentada/implementada
- [ ] Variable `$appVersion` usada al menos en el layout del login (panel de marca)

### Fase 3 — UI Simplificada
- [ ] Bloque HTML de la progress bar eliminado de `toasts.blade.php`
- [ ] Propiedades Alpine `remaining`, `interval`, `paused`, `percent` eliminadas de `toastItem`
- [ ] Métodos `startTimer()`, `pause()`, `resume()` eliminados
- [ ] Timer reemplazado por `setTimeout(() => this.close(), toast.duration)` en `init()`
- [ ] Handlers `@mouseenter` y `@mouseleave` eliminados del div del toast
- [ ] `progressClass` eliminado de todos los objetos en `setConfig()`
- [ ] Flag `visible` agregado a todos los módulos en `config/modules.php`
- [ ] Dashboard filtra y renderiza solo módulos con `visible: true`
- [ ] Módulos `comunicaciones` e `inventario` con `visible: false`
- [ ] Rutas de módulos pausados protegidas contra acceso directo (si aplica)

---

## Notas de Implementación

**Orden de ejecución recomendado:**

1. Fase 2 primero (sin dependencias, cambio aislado en `AppServiceProvider`)
2. Fase 3.1 (simplificación del Toast, cambio en un solo archivo)
3. Fase 3.2 (módulos del dashboard, requiere editar `config/modules.php` antes que las vistas)
4. Fase 1.2 (redirección post-login, cambio en el controlador)
5. Fase 1.1 (Observer de estudiantes, el más crítico — probar con un estudiante individual antes de importaciones masivas)
6. Fase 1.3 (estructura del login, preparar el slot, esperar entrega del HTML definitivo)

---

## Consideraciones de Rendimiento

| Componente | Observación | Recomendación |
| :--- | :--- | :--- |
| **Fase 2 — Cache de versión** | `Cache::rememberForever` elimina lecturas repetidas de disco. Sin embargo, en entornos con `CACHE_DRIVER=file`, todos los workers comparten el mismo archivo de caché. | No es un problema en desarrollo. En producción, si se usa Redis, el invalidado de caché con `cache:forget` es instantáneo para todos los workers. |
| **Fase 1.1 — Creación de User en Observer** | Un Observer sícrono crea un User por cada Student en el mismo request. En importaciones masivas esto puede generar N inserciones adicionales por cada fila del Excel, multiplicando el tiempo total. | Si se detecta latencia real en importaciones de +200 registros, mover la creación del User a `CreateStudentUserJob::dispatch($student)->afterCommit()`. La importación en sí usa chunks, así que el impacto puede ser menor de lo estimado. |
| **Fase 3.2 — Filtro de módulos** | `collect(config('modules'))->filter(...)` es una operación O(n) sobre un array pequeño (≤ 15 módulos). No representa costo computacional significativo. | Si el dashboard usa Livewire con `#[Lazy]`, el filtro ocurre en el primer render diferido, sin bloquear la carga inicial de la página. Sin acción adicional necesaria. |
| **Fase 3.1 — Toast sin timer de interval** | Eliminar el `setInterval` de 10ms reduce la carga de JavaScript de forma perceptible cuando hay múltiples toasts activos simultáneamente. El `setTimeout` es una sola operación del event loop. | Mejora directa sin contraparte negativa. |

---

## Archivos Modificados en esta Versión

| Archivo | Tipo de cambio | Fase |
| :--- | :--- | :--- |
| `app/Observers/Tenant/StudentObserver.php` | Modificación — evento `created` | 1.1 |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Modificación — método `store` | 1.2 |
| `resources/views/auth/login.blade.php` | Reemplazo — estructura nueva | 1.3 |
| `app/Providers/AppServiceProvider.php` | Modificación — método `boot` | 2.1 |
| `VERSION` | Actualización — `0.4.1` | 2.1 |
| `resources/views/components/ui/toasts.blade.php` | Modificación — remoción de progress bar | 3.1 |
| `config/modules.php` | Modificación — flag `visible` por módulo | 3.2 |
| `resources/views/app/dashboard.blade.php` | Modificación — filtro de tiles visibles | 3.2 |

---

## Decisiones de Arquitectura Registradas

**Módulo de Comunicaciones — Pausa indefinida:** El módulo de Comunicaciones queda excluido de la hoja de ruta activa hasta nueva decisión del equipo. Su tile en el dashboard se oculta mediante el flag `visible: false` en `config/modules.php`. Las rutas definidas (si existen) deben retornar 404 en entorno de demostración. Esta decisión se revisará post-demo según prioridades del cliente.

**Redirección post-login en el controlador vs. en el modelo:** Se optó por mantener la lógica de redirección en `AuthenticatedSessionController` y no en el modelo `User`. El modelo no debe conocer rutas de la aplicación. El controlador es el lugar semánticamente correcto para este tipo de decisión de flujo.

**Toast sin pausa al hover:** Eliminar la pausa al hover es una simplificación deliberada para la demo. Si en versiones futuras se decide reinstaurar la interactividad del toast, el diseño original está documentado en `toast.md` y puede restaurarse de forma incremental.