## 🏗️ Checklist de Infraestructura Core (TALL Stack Ready)

### 🌿 Fase 1: Infraestructura de Datos (Rama: `feat/database-core`)

*Objetivo: Definir el ADN del sistema y la geografía nacional.*

* [x] **Normalización Geográfica RD:**
    * [x] Crear migraciones de geografía (Provincias -> Municipios -> Distritos).
    * [x] Crear el comando `orvian:import-geo` consumiendo los JSON vía `Http::get()`.

* [x] **Catálogo de Planes (`plans`):**
* Migración: `id`, `name`, `slug`, `limit_students`, `limit_users`, `price`.
* Model Constants: `Plan::BASIC`, `Plan::PREMIUM`.


* [x] **Entidad Raíz (`schools`):**
* Campos: `sigerd_code` (Index/Unique), `name`, `modalidad` (Académica/Técnica/Prepara), `sector` (Público/Privado), `jornada` (Extendida/Matutina/Nocturna).
* FKs: `district_id`, `plan_id`.
* `SchoolObserver`: Asignación automática de `plan_id` (Basic) al crear.

---

### 🌿 Fase 2: El Motor Multi-tenant (Rama: `feat/multi-tenancy`)

*Objetivo: Garantizar que un director del Distrito 10-01 nunca vea datos del 10-02.*

* [x] **Aislamiento de Consultas (Scoping):**
* Crear `App\Traits\BelongsToSchool`.
* Crear `App\Models\Scopes\SchoolScope`: Añadir `where('school_id', ...)` automáticamente.


* [x] **Spatie Permissions (Tenant-Aware):**
* Instalar `spatie/laravel-permission`.
* Configurar `teams => true` para que los roles existan dentro del contexto de cada `school_id`.
* Agregar Trait `HasRoles` a `User` model.


* [x] **Identificación del Tenant:**
* Middleware `IdentifyTenant`: Captura el `school_id` del usuario autenticado y lo setea en una variable global de sesión o vía `App::instance`.


* [x] **Relación User-School:**
* Modificar `users` table: Añadir `school_id` (nullable solo para SuperAdmins).
* Implementar el registro de usuarios vinculados a una escuela existente.
* Agregar `HasFactory` al modelo shcool
* Creare seeder de desarrollo para pruebas

---

### 🌿 Fase 2.5: Dominio y Robustez SaaS (Rama: `feat/domain-logic`)

*Objetivo: Elevar la arquitectura de "Simple CRUD" a "Domain Driven Design" (DDD) simplificado.*

#### 1. Capa de Aplicación (Actions)

En lugar de poner lógica en los controladores o componentes Livewire, crearemos "Acciones" de un solo propósito.

* [ x ] Crear directorio `app/Actions`.
* [ x ] **Action `CreateSchool**`: No solo hace `School::create`, sino que dispara el primer año escolar y asigna los roles iniciales.
* [ x ] **Action `RegisterUser**`: Valida invitaciones y asigna roles dentro del equipo (team) correcto.

#### 2. Feature Flags en Planes (Plan Capabilities)

Un plan no es solo un límite de números, es un conjunto de permisos.

* [x] **Migración:** Crear tabla `features` (catálogo) y `feature_plan` (pivote).
* [x] **Modelo `Feature`:** Definición del modelo y su relación con `Plan`.
* [x] **Refactorización `Plan`:** Cambiar el campo JSON por una relación `belongsToMany`.
* [x] **Caché de Permisos:** Implementar un método para que la consulta de features no golpee la base de datos en cada carga de página.
* [x] **Helper de Acceso:** Crear el método `hasFeature()` en el modelo `School` (o vía `Plan`).
* [x] **Helper `canAccessFeature($feature)**`: Para que en Blade puedas hacer `@if($school->plan->canAccess('attendance_qr'))`.

#### 3. Eventos de Dominio (Desacoplamiento)

Para que el sistema sea reactivo.

* [x] **Evento `TenantCreated**`: Disparado cuando una escuela termina el Wizard.
* [x] **Listener `SetupAcademicStructure**`: Escucha el evento anterior y crea automáticamente los grados/niveles básicos (Primero, Segundo...) para ahorrarle trabajo al director.

#### 4. Identificación de Tenant Robusta (Refuerzo del Middleware)

* [x] **Prioridad de Identificación:** Modificar `IdentifyTenant` para que busque en este orden:
1. ¿Hay sesión activa?
2. Fallback a selección manual (solo SuperAdmin).


---

### 🌿 Fase 3: Arquitectura de Componentes y Filtros (Rama: `feat/ui-infrastructure`)

*Objetivo: Preparar el terreno para Livewire antes de hacer el Wizard.*

* [x] **Pipelines de Filtrado (Agnostic Pipeline):**
    * [x] Implementación de `FilterInterface` y `QueryFilter` base.
    * [x] Desacoplamiento del objeto `Request` para compatibilidad total con el estado de Livewire.
    * [x] Documentación técnica en `docs/architecture/filtering.md`.


* [x] **Configuración Core TALL Stack:**
    * [x] Instalación de Livewire 3.
    * [x] Refactorización de `app.js` para unificación de Alpine.js.


* [x] **Componentes Base Livewire:**
    * [x] Crear componente `GeographicSelects` (Provincia -> Municipio -> Distrito).
    * [x] Crear componente `DataTable` genérico.
    * [x] Documentación técnica en `docs/architecture/datatables.md`.

* [-] **Layouts de Aplicación:**
    * [x] **1. Configuración Base de Diseño:**
        * [x] Instalar Heroicons (`blade-ui-kit/blade-heroicons`).
        * [x] Instalar Laravel debugbar.
        * [x] Configurar variables de color (`#04275f`, `#f78904`) y modo oscuro en `tailwind.config.js`.
        * [x] Importar fuente `Inter` y definirla como familia base.


    * [x] **2. Master Layout (Estructura Flexbox):**
        * [x] Crear `resources/views/layouts/superadmin.blade.php`.
        * [x] Implementar el contenedor flex principal (sin márgenes forzados).
        * [x] Implementar lógica global de Alpine.js para estado del sidebar y modo oscuro.


    * [x] **3. Componentes del Sidebar:**
        * Crear `x-sidebar.layout` con responsividad dual (Flex en desktop, Fixed+Overlay en móvil).
        * Crear `x-sidebar.item`, `group` y `title` con animaciones de opacidad (para cuando el sidebar se colapsa a 80px).
        * Crear `x-sidebar.dropdown` y `subitem` con lógica de estado activo según la URL actual.
        * Vista de sidebar usando los compomentes para incluir en la vista de admin.


    * [x] **4. Navbar y Breadcrumbs (SuperAdmin):**
        * Crear `x-navbar.layout` que contenga el botón hamburguesa (visible en móvil o cuando el sidebar está cerrado).
        * Implementar breadcrumbs automáticos conectados al enrutador.
        * Añadir toggle de Modo Claro / Oscuro.


    * [x] **5. Componentes generales:**
        * Crear login con estilo split y maenjo de temas
        * Crear toast alert con diseño adecuado y con temas claros y oscuros
        * Crear catalogo de botones con variantes documentados en `docs/ui/buttons.md`
        * Crear componetne footer para todas las vistas.
        * Crear componente Empty State para datatables u otra componente.
        * Crear componente de Badges System para usarse en otros componentes (datatables, listas, formualrios, modales, etc)

    * [x] **6. Traducir Laravel:**
        * Instalar el paquete `sail composer require laravel-lang/common --dev` 
        * Configurar por defecto el idioma espanol `sail artisan lang:add es` y en `.env` APP_LOCALE=es APP_FALLBACK_LOCALE=en

    * [x] **7. Menú de usuarios normales:**
        * Menú de modulos para usuarios de ORVIAN (USO: Usuario del sistema)


---

# 🌿 Fase 4: Onboarding & Global Setup
**Rama:** `feat/onboarding-wizard`  
**Objetivo:** Implementar el flujo de instalación "First-Run" y el Wizard de configuración de escuelas — soportando dos orígenes: Owner del sistema y usuario registrado público.

---

## 1. Estructura de Vistas y Layouts

- [x] `resources/views/layouts/superadmin.blade.php` → `resources/views/components/admin.blade.php`
- [x] `resources/views/admin/` — App Hub global, gestión de escuelas, soporte.
- [x] `resources/views/app/` — Dashboard del Tenant y sus módulos.
- [x] Refactorización de Breadcrumbs a los nuevos nombres.

---

## 2. Preparación del Entorno

- [x] `orvian.test` en `hosts` y `APP_URL` en `.env`.
- [x] Método `redirectPath()` centralizado en el modelo `User`.
- [x] Spatie: tablas pivot `model_has_roles` con `school_id` nullable + `UNIQUE INDEX`.
- [x] `UserFactory` sin roles ni escuelas por defecto.

---

## 3. Self-Installation del Owner (Primer Arranque)

> Solo disponible cuando no existe ningún usuario en el sistema.

- [x] **Middleware `EnsureSystemIsInstalled`** — bloquea `/register` si ya hay usuarios; `redirect()->back()` + `session('info')`.
- [x] **Middleware `RedirectIfSystemNotInstalled`** — fuerza ir a `/register` si la DB está vacía.
- [x] **Spatie Global Roles Seeder** (`AppInit/`) — `Owner`, `TechnicalSupport`, `Administrative` con `school_id` nulo.
- [x] **Componente `RegisterInstall`** — wizard de 3 pasos (Bienvenida, Datos, Contraseña).
  - Ruta: `GET /register` → `RegisterInstall::class` (sin ruta POST).
  - Layout: `components/install.blade.php` vía `#[Layout('components.install')]`.
  - `session()->flash('success')` antes de `$this->redirect()`.
- [x] Favicon en `components/app.blade.php`.

---

## 4. Registro Público de Usuarios + Escuela Stub

> **Nuevo flujo independiente:** Cualquier usuario puede registrarse en `/sign-up`. Al registrarse, se genera automáticamente una **escuela stub** (`is_configured = false`) con un TTL de limpieza. El middleware obliga a configurarla antes de acceder al dashboard.

- [x] **Ruta y componente `RegisterUser`:**
  - Ruta: `GET /sign-up` → `RegisterUser::class` (Livewire, sin pasos/wizard).
  - Vista: `resources/views/livewire/auth/register-user.blade.php`.
  - Layout: `components/install.blade.php` (reutilizado — mismo diseño).
  - Formulario de un solo paso: `name`, `email`, `password`, `password_confirmation`.
  - El middleware `EnsureSystemIsInstalled` **no aplica** aquí — siempre pública.

- [x] **Lógica de registro al enviar:**
  1. Crear usuario.
  2. Crear escuela stub:
     ```php
     School::create([
         'name'            => 'Escuela de ' . $user->name, // placeholder editable en wizard
         'is_configured'   => false,
         'stub_expires_at' => now()->addDay(),              // TTL: 24 horas
     ]);
     ```
  3. Asignar `user->school_id = $school->id`.
  4. **No asignar rol** — se asigna al completar el wizard.
  5. Login automático + `redirect(route('wizard'))`.
  6. Hacer el campo de sigerd_code, regimen_gestion, regional_education_id, educational_district_id, municipality_id nulleable en la migracion para la escuela stub se pueda crear sin problemas

- [x] **Campo `stub_expires_at`** en migración `schools` — TIMESTAMP nullable.
  - Nulo cuando: escuela creada por Owner desde `/setup`, o cuando `is_configured = true`.
  - Lleno solo cuando: escuela creada por registro público y aún no configurada.

- [x] **Comando `orvian:cleanup-stubs`** (`app/Console/Commands/CleanupStubSchools.php`):
  ```php
  School::where('is_configured', false)
      ->whereNotNull('stub_expires_at')
      ->where('stub_expires_at', '<', now())
      ->each(function ($school) {
          $school->users()->update(['school_id' => null]);
          $school->delete();
      });
  ```
  - Registrar en `routes/console.php`:
    ```php
    Schedule::command('orvian:cleanup-stubs')->daily();
    ```
  - Stubs del Owner (`stub_expires_at = null`) **nunca** son afectados.

---

## 5. Datos Maestros (Pre-Wizard)

- [x] **Geografía Educativa:** Modelos `RegionalEducation` + `EducationalDistrict`, comando `orvian:import-educational-geo`, `EducationalGeoSeeder` en `AppInit/`.
- [x] **Modelo `School` refactorizado:**
  - Campos: `regimen_gestion`, `regional_education_id`, `educational_district_id`, `phone`, `address_detail`.
  - Campo nuevo: `stub_expires_at` TIMESTAMP nullable (ver §4).
  - Constantes `REGIMEN_*` y `MODALITY_*` actualizadas.
  - Relaciones: `levels()` BelongsToMany, `shifts()` HasMany, `academicYears()` HasMany.
  - Eliminado trait `BelongsToSchool` (una escuela no puede pertenecer a sí misma).
  - Eliminada columna `jornada` — las tandas viven en `school_shifts`.
- [x] **Infraestructura Académica:** `Level`, `Grade`, `SchoolSection`, `SchoolShift`, `AcademicYear` — migraciones, modelos y seeders (solo el level tiene seeder).
- [x] **Catálogo MINERD:** `TechnicalFamily` + `TechnicalTitle` + pivote `school_technical_titles`, seeder `TechnicalCatalogSeeder`.
- [x] **Roles académicos Seeder** (`AppInit/RoleAcademicSeeder`): `School Principal`, `Teacher`, `Secretary`, `Student`.

---

## 6. Acciones y Eventos del Dominio

- [x] **`CompleteOnboardingAction`** — crea escuela nueva + Director + Plan + dispara `SchoolConfigured`. Usado por `SchoolWizard` (Owner).
- [x] **`CreateSchoolPrincipalAction`** — crea usuario Director, lo vincula a la escuela, asigna rol con `setPermissionsTeamId`.
- [x] **`CompleteTenantOnboardingAction`** — actualiza escuela stub existente (no crea).
  - Recibe: `$schoolId`, `array $wizardData`, `User $principalUser`.
  - `sync()` para niveles y títulos; `foreach create()` para turnos.
  - Limpia `stub_expires_at → null` al completar.
  - Asigna rol `School Principal` al `$principalUser`.
  - Dispara `SchoolConfigured`.
- [x] **Evento `SchoolConfigured`** (`app/Events/Tenant/`).
- [x] **Listener `SetupAcademicStructure`** — crea Grades por niveles, asocia TechnicalTitles.
- [x] **Listener `CreateInitialAcademicYear`** — crea `AcademicYear` con fechas del wizard.
- [x] **Listener `AssignInitialRoles`** — confirma rol `School Principal` en scope del tenant.
- [x] **Event Discovery activo** — sin registros manuales.

---

## 7. Wizard de Configuración — Arquitectura de Herencia

```
BaseSchoolWizard (abstracta)
├── SchoolWizard        → GET /setup   (Owner, 5 pasos, crea Director)
└── TenantSetupWizard   → GET /wizard  (Usuario, 4 pasos, se autoconfigura) RUTA REGISTRADA en web.php
```

Ambas clases comparten la misma vista `livewire/tenant/school-wizard.blade.php`.

### 7.1 Clase Base — `BaseSchoolWizard`

- [x] **`app/Livewire/Tenant/BaseSchoolWizard.php`** — abstracta, sin ruta propia.
  - Todas las propiedades públicas (pasos 1–4).
  - Propiedades `#[Computed]`: `regionalEducations`, `educationalDistricts`, `provinces`, `municipalities`, `levels`, `shifts`, `families`, `availableTitles`, `displayTitles`, `plans`, `selectedPlan`.
  - Helpers: `regimenesLabels()`, `modalidadesDescripcion()`, `modalidadLabel()`, `modalityNeedsTechnical()`.
  - Hooks: `updatedRegionalEducationId`, `updatedProvinceId`, `updatedModalidad`, `updatedTempFamilyId`.
  - Navegación: `nextStep()`, `prevStep()`, `goToStep()`.
  - Gestión de títulos: `addTitle()`, `removeTitle()`.
  - Validaciones pasos 1–4 en `validateStep()`.
  - `sigerdUniqueRule()` sobreescribible (por defecto: `Rule::unique('schools', 'sigerd_code')`).
  - Métodos `schoolPayload()` y `academicPayload()` para ensamblar datos.
  - Método abstracto `finish()`.

### 7.2 Wizard del Owner — `SchoolWizard`

- [x] **`app/Livewire/Tenant/SchoolWizard.php`** — extiende `BaseSchoolWizard`.
  - `$totalSteps = 5`.
  - Propiedades exclusivas: `principal_name`, `principal_email`, `password`, `password_confirmation`.
  - Sobreescribe `validateStep()` para el paso 5; delega los demás a `parent::validateStep()`.
  - `finish(CompleteOnboardingAction $action)` — crea escuela y Director desde cero.
  - `#[Layout('components.wizard')]`, `#[Title('Configuración de Escuela')]`.
  - Ruta: `GET /setup` — middleware `['auth', 'role:Owner']`.

### 7.3 Wizard del Usuario Registrado — `TenantSetupWizard`

- [x] **`app/Livewire/Tenant/TenantSetupWizard.php`** — extiende `BaseSchoolWizard`.
  - `$totalSteps = 4`. El paso 5 en el blade nunca se activa.
  - `mount()`:
    - Obtiene escuela stub via `auth()->user()->school`.
    - Si no hay escuela o ya está configurada → `redirect(route('app.dashboard'))`.
    - Pre-llena `$this->name`, `$this->sigerd_code`, `$this->modalidad` desde el stub.
    - Almacena `$this->schoolId`.
  - `sigerdUniqueRule()` → `Rule::unique('schools', 'sigerd_code')->ignore($this->schoolId)`.
  - `finish(CompleteTenantOnboardingAction $action)` — actualiza el stub, no crea escuela nueva.
  - `#[Layout('components.wizard')]`, `#[Title('Configura tu Escuela')]`.
  - Ruta: `GET /wizard` — middleware `['auth']` (sin `onboarding.complete`).

### 7.4 Pasos del Wizard

> Los pasos 1–4 son compartidos por ambos flujos. El paso 5 es exclusivo del Owner.  
> La columna **Usuario** indica si el paso aplica a `TenantSetupWizard` (4 pasos).

| # | Nombre | Owner | Usuario | Estado |
|---|--------|:-----:|:-------:|:------:|
| 1 | Identidad Institucional | ✓ | ✓ | [x] |
| 2 | Ubicación | ✓ | ✓ | [x] |
| 3 | Configuración Académica | ✓ | ✓ | [x] |
| 4 | Plan | ✓ | ✓ | [x] |
| 5 | Director de la Escuela | ✓ | — | [x] |

---

#### Paso 1 — Identidad Institucional
*Ambos flujos.*

- [x] Campo `sigerd_code` — único en `schools`; en `TenantSetupWizard` la regla ignora el ID del stub propio.
- [x] Campo `name` — mínimo 5 caracteres; en `TenantSetupWizard` se pre-llena desde el stub (editable).
- [x] Selector `regimen_gestion` — botones tipo pill: Público / Privado / Semioficial.
- [x] Selector `modalidad` — grid de 6 cards con descripción inline al seleccionar:
  - Liceo (Académica), Politécnico (Técnico-Profesional), Bachiller Técnico, Modalidad en Artes, Prepara, Mixto.
- [x] Validación: todos requeridos, `sigerd_code` unique.
- [x] Cambiar `modalidad` limpia los títulos técnicos seleccionados (hook `updatedModalidad`).

---

#### Paso 2 — Ubicación
*Ambos flujos.*

- [x] **Geografía MINERD (selects encadenados, obligatorios):**
  - Select 1: Regional Educativa (`regional_educations`).
  - Select 2 dependiente: Distrito Educativo (`educational_districts` filtrados por regional). Se resetea al cambiar regional.
- [x] **Geografía política (selects encadenados, obligatorios):**
  - Select 3: Provincia (`provinces`).
  - Select 4 dependiente: Municipio (`municipalities` filtrados por provincia). Se resetea al cambiar provincia.
- [x] **Dirección física** (marcada como "Recomendado" con badge `x-ui.badge` variant `info`):
  - `address` — Calle / Avenida.
  - `address_number` — Número.
  - `neighborhood` — Barrio / Sector.
  - `address_reference` — Referencias.
  - `phone` — Teléfono del centro.
  - Los 5 campos se combinan en `address_detail` dentro de `schoolPayload()` al guardar.
- [x] Validación: Regional, Distrito, Provincia y Municipio requeridos. Dirección opcional.

---

#### Paso 3 — Configuración Académica
*Ambos flujos.*

- [x] **Multi-select de niveles educativos** (al menos uno requerido):
  - Primaria, Secundaria Primer Ciclo, Secundaria Segundo Ciclo.
  - Cards checkbox con borde naranja al seleccionar.
- [x] **Multi-select de tandas** (al menos una requerida):
  - Pills tipo toggle: Matutina, Vespertina, Jornada Extendida, Nocturna.
- [x] **Año escolar:**
  - `year_name` — texto libre, ej. "2025-2026".
  - `start_date` — date picker.
  - `end_date` — date picker; validación `after:start_date`.
- [x] **Selector de Títulos Técnicos** — visible y requerido solo si la modalidad aplica:
  - Visible para: `MODALITY_TECHNICAL`, `MODALITY_TECHNICAL_BACHILLER`, `MODALITY_MIXED`, `MODALITY_ARTS`.
  - Oculto para: `MODALITY_ACADEMIC`, `MODALITY_PREPARA`.
  - Select 1: Familia técnica (filtrada por el grupo de la modalidad: "Técnico Profesional" o "Modalidad en Artes").
  - Select 2 dependiente: Títulos de esa familia. Se resetea al cambiar familia.
  - Botón "+ Agregar" — añade el título a una lista acumulativa; no se permiten duplicados.
  - Lista de títulos seleccionados — muestra nombre + código + familia. Botón "×" para eliminar cada uno.
  - Para Artes: las 7 menciones de la familia `ART` aparecen como títulos (`ART_VIS`, `ART_MUS`, etc.).

---

#### Paso 4 — Plan
*Ambos flujos.*

- [x] **Header centrado** con título y descripción (texto estático).
- [x] **Toggle Mensual / Anual:**
  - Propiedad `$billingAnnual` en el componente.
  - Al activar modo Anual: precio se recalcula como `price * 12 * 0.80`. Badge "AHORRA 20%".
  - Solo visual en esta fase — el pago real es un placeholder.
- [x] **Grid de planes** desde `Plan::with('features')->where('is_active', true)->get()`:
  - Card por plan: nombre, descripción, precio (con prefijo RD$), límite de estudiantes, límite de usuarios, lista de features con ícono ✓.
  - Badge "Recomendado" centrado en el borde superior si `is_featured = true`.Agregar campo a la migracion de plans.
  - Plan seleccionado: borde naranja + ícono ✓ en el botón.
- [x] **Nota informativa** (badge azul) indicando que el pago es placeholder.
- [x] Validación: `plan_id` requerido y existente en `plans`.

---

#### Paso 5 — Director de la Escuela
*Exclusivo del Owner (`SchoolWizard`). `TenantSetupWizard` finaliza en el paso 4.*

- [x] **Resumen compacto** en la parte superior del paso — muestra: nombre del centro, modalidad, cantidad de niveles seleccionados, plan elegido. Permite revisar sin retroceder.
- [x] Formulario: `principal_name`, `principal_email`, `password`, `password_confirmation`.
- [x] Toggle mostrar/ocultar contraseña en ambos campos (Alpine `x-data="{ show: false }"`).
- [x] Validación: `principal_email` unique:users, `password` confirmed + mínimo 8 caracteres.
- [x] **Aviso de sesión** (badge ámbar): el Owner mantiene su sesión activa; el Director creado accede con sus propias credenciales.
- [x] Al completar: llama a `CompleteOnboardingAction` → dispara `SchoolConfigured` → listeners procesan en background.
- [x] **Pantalla de progreso animada** (~90 segundos artificial) — "Configurando tu escuela...":
  - Se muestra tras llamar a `finish()`, antes del redirect.
  - Barra de progreso animada con CSS (no requiere polling real).
  - Mensaje de estado cambia cada ~15 segundos: "Creando estructura académica...", "Asignando roles...", "Generando año escolar...", etc.
  - Al terminar: `session()->flash('success')` + `redirect(route('app.dashboard'))`.

---

### 7.5 Vista compartida

- [x] **`resources/views/livewire/tenant/school-wizard.blade.php`**
  - Sidebar: pasos numerados + resumen en tiempo real (aparece a partir del paso 2).
  - `x-show="$wire.step === N"` por cada paso — el paso 5 nunca se activa si `totalSteps = 4`.
  - Footer: botón "Continuar al [nombre del siguiente paso]" o "Finalizar Configuración" según corresponda.
  - Botón "Atrás" invisible (opacity-0) en el paso 1 para mantener el layout estable.

### 7.6 Layout del Wizard

- [x] **`resources/views/components/wizard.blade.php`** — glassmorphism, orbs decorativos, stepper lateral, toggle dark/light, barra de progreso de pasos.

---

## 8. Seguridad y Acceso

- [x] **Middleware `EnsureOnboardingIsComplete`** (`app/Http/Middleware/`):
  - Roles globales (`Owner`, `TechnicalSupport`, `Administrative`) → pasan siempre.
  - `user->school_id` null → `redirect(route('wizard'))` + `session('info')`.
  - `school->is_configured === false` → `redirect(route('wizard'))` + `session('info')` con el nombre de la escuela.
  - Alias `onboarding.complete` en `bootstrap/app.php`.
  - Aplicado al grupo completo `app/*`:
    ```php
    Route::middleware(['auth', 'onboarding.complete'])
        ->prefix('app')->name('app.')->group(...);
    ```
  - La ruta `GET /wizard` queda **fuera** de este grupo (es el destino de redirección).

* [x] **Middleware `EnsureGlobalAdminAccess**` (`app/Http/Middleware/`):
    * **Lógica:** Solo permite el paso si `user->school_id === null`.
    * **Propósito:** Englobar de forma escalable a `Owner`, `TechnicalSupport` y futuros roles administrativos sin atarlos a una escuela.
    * **Acción:** Si un usuario de escuela intenta entrar a `/admin/*`, lo redirige a `/app/dashboard`.
    * **Alias:** `admin.global` registrado en `bootstrap/app.php`.


    * [x] **Estructura de Rutas (`routes/web.php`)**:
    * **Grupo App:** Protegido por `['auth', 'verified', 'onboarding.complete']`.
    * **Grupo Admin:** Protegido por `['auth', 'verified', 'admin.global']`.
    * **Ruta Wizard:** Fuera del middleware de bloqueo para permitir la configuración.

---

## 9. Pendientes Globales

* [x] Menú de módulos para usuarios con rol de escuela (Teacher, Secretary, Student) — de Fase 3.

* [x] **Sistema de Componentes UI (`x-ui.*`)**:
    * [x] **Core UI**: `x-ui.button` y `x-ui.badge` con variantes, tamaños y soporte para iconos. Usar nuevos badge en `footer.blade.php`.
    * [x] **Suite de Formularios (`x-ui.forms.*`)**:
        * [x] Arquitectura de 6 componentes: `Input`, `Select`, `Textarea`, `Checkbox`, `Radio` y `Toggle`.
        * [x] Estándar visual **Line UI** (borde inferior) con soporte completo para **Dark Mode**.
        * [x] Lógica de estados dinámica (Focus, Error, Success, Disabled) integrada.
        * [x] Detección automática de validación de Laravel mediante el prop `name`.


    * [x] **Refactorización de Notificaciones (Toasts)**:
        * [x] **Componente `x-ui.toast` unificado**: Lógica dinámica que responde tanto a `session()` de Laravel como a eventos `dispatch` de Livewire/Alpine.
        * [x] **Simplificación de Arquitectura**: Eliminación de componentes obsoletos (`ToastItem` y vistas redundantes).
        * [x] **Integración Limpia**: Implementado directamente en los layouts raíz (`admin`, `install`, `wizard`, `app`, `guest`) sin contenedores adicionales.
        * [x] **Documentación y Demo**: Guía técnica redactada y laboratorio de pruebas funcional.

* [x] **Documentación y Testing Visual**:
    * [x] Guías técnicas detalladas en Markdown para `ui-forms` y `ui-badges`.
    * [x] Laboratorio de pruebas (Demos) creado en `resources/views/examples/`:
        * `form-components-demo.blade.php`
        * `button-components-demo.blade.php`
        * `badge-components-demo.blade.php`
        * `toast-components-demo.blade.php`

* [x] Eliminar seeders `PlanSeeder.php` y `FeatureSeeder.php` para que sean unificado en el seeder de `PlanFeatureSeeder` donde se crean los planes, features y se registran las features de los planes.

## REFACTORIZAR Niveles y Grados
### Migraciones a crear/modificar
- [x] Modificar migración `grades` → agregar campos `cycle` y `allows_technical`
- [x] Modificar migración `school_sections` → agregar `label` (string) y `technical_title_id` nullable FK
- [x] Eliminar columna `name` de `school_sections` (reemplazada por `label` + relación con grade)

### Seeders a crear/modificar
- [x] Crear `GradeSeeder` (con ciclos y `allows_technical`) — reemplaza el anterior
- [x] Registrar `GradeSeeder` en `DatabaseSeeder` después de `LevelSeeder`

### Componente `BaseSchoolWizard`
- [x] Agregar propiedad `$selectedSectionLabels = []`
- [x] Agregar método `availableSectionLabels(): array`
- [x] Actualizar `validateStep(3)` con regla para `selectedSectionLabels`
- [x] Actualizar `academicPayload()` para incluir `section_labels`

### Vista `school-wizard.blade.php` — Paso 3
- [x] Agregar UI de selección de secciones (paralelos) entre Niveles y Tandas
- [x] Agregar preview dinámico de cantidad de secciones a generar

### Listener `SetupAcademicStructure`
- [x] Refactorizar para usar `section_labels` del payload
- [x] Implementar lógica de secciones técnicas en grados `allows_technical`
- [x] Asegurar que `Level::with('grades')` está cargado antes del loop

### Modelo `Grade`
- [x] Agregar `$fillable` con `cycle` y `allows_technical`
- [x] Agregar scope `allowsTechnical()` para consultas del listener

### Modelo `SchoolSection`
- [x] Actualizar `$fillable` → `['school_id', 'grade_id', 'label', 'technical_title_id']`
- [x] Agregar relación `belongsTo(TechnicalTitle::class)`
- [x] Agregar relación `belongsTo(Grade::class)`
- [x] Remover relación/campo `name` si existía
