# Changelog

Todos los cambios importantes del proyecto **ORVIAN** se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/)
y el proyecto sigue [Semantic Versioning](https://semver.org/lang/es/).

---

## [Unreleased]

> Cambios en desarrollo activo sobre `feat/onboarding-wizard`.

---

## [0.2.0] - 2026-03-12

### Added

#### Geografía y Datos Maestros
- Jerarquía geográfica completa de República Dominicana: Provincias → Municipios → Distritos Municipales.
- Comando `orvian:import-geo` para importación masiva desde fuentes externas vía `Http::get()`.
- Geografía educativa MINERD: modelos `RegionalEducation` y `EducationalDistrict`.
- Comando `orvian:import-educational-geo` y seeder `EducationalGeoSeeder`.

#### Catálogos Académicos
- Infraestructura educativa completa: `Level`, `Grade`, `SchoolSection`, `SchoolShift`, `AcademicYear`.
- `Grade` con soporte de ciclos (`cycle`) y flag `allows_technical` para grados del Segundo Ciclo de Secundaria.
- `SchoolSection` refactorizado: campo `label` (letra del paralelo) y FK nullable `technical_title_id`.
- Catálogo técnico MINERD: `TechnicalFamily`, `TechnicalTitle`, tabla pivote `school_technical_titles`.
- Seeders: `LevelSeeder`, `GradeSeeder` (con ciclos), `TechnicalCatalogSeeder`, `RoleAcademicSeeder`.

#### Planes y Features
- Catálogo de planes (`plans`) con límites de estudiantes, usuarios, precio y flag `is_featured`.
- Sistema de Feature Flags basado en catálogo: tabla `features` + pivote `feature_plan`.
- Relación `Plan belongsToMany Feature` reemplazando el campo JSON anterior.
- Método `hasFeature()` en `School` y helper `canAccess()` en `Plan`.
- `PlanFeatureSeeder` unificado que reemplaza `PlanSeeder` y `FeatureSeeder` independientes.

#### Multi-Tenancy
- Aislamiento automático de consultas por `school_id` mediante `GlobalScope`.
- Middleware `IdentifyTenant` para resolución del tenant activo por sesión.
- Integración de `spatie/laravel-permission` con `teams = true` por `school_id`.
- Campo `users.school_id` nullable (null para roles globales: Owner, TechnicalSupport, Administrative).
- Índice único en `model_has_roles` con soporte de `school_id` nullable.

#### Arquitectura de Dominio
- Capa de aplicación con Actions de propósito único:
  - `CompleteOnboardingAction` — crea escuela + Director desde cero (Owner).
  - `CompleteTenantOnboardingAction` — actualiza escuela stub existente (Usuario registrado).
  - `CreateSchoolPrincipalAction` — crea Director y asigna rol en scope del tenant.
- Evento `SchoolConfigured` (`app/Events/Tenant/`).
- Listeners disparados por `SchoolConfigured`:
  - `SetupAcademicStructure` — crea secciones por nivel, grado y paralelo; distingue secciones técnicas en grados `allows_technical`.
  - `CreateInitialAcademicYear` — crea el año escolar con las fechas del wizard.
  - `AssignInitialRoles` — confirma el rol `School Principal` en scope del tenant.
- Event Discovery activo; sin registros manuales en `EventServiceProvider`.

#### Onboarding y Seguridad
- Flujo de Self-Installation del Owner en `/register` con middleware `EnsureSystemIsInstalled` y `RedirectIfSystemNotInstalled`.
- Registro público en `/sign-up` (`RegisterUser`) con creación automática de escuela stub (`is_configured = false`, `stub_expires_at = now()->addDay()`).
- Comando `orvian:cleanup-stubs` para eliminación diaria de stubs vencidos, programado en `routes/console.php`.
- Middlewares de seguridad:
  - `EnsureSystemIsInstalled` — bloquea `/register` si ya existe un Owner.
  - `RedirectIfSystemNotInstalled` — fuerza `/register` si la base de datos está vacía.
  - `EnsureOnboardingIsComplete` — redirige a `/wizard` si la escuela no está configurada.
  - `EnsureGlobalAdminAccess` — restringe `/admin/*` a usuarios sin `school_id`.

#### Wizard de Configuración de Escuela
- Arquitectura de herencia `BaseSchoolWizard` con dos implementaciones:
  - `SchoolWizard` — Owner, 5 pasos, crea Director. Ruta: `GET /setup`.
  - `TenantSetupWizard` — Usuario registrado, 4 pasos, actualiza stub. Ruta: `GET /wizard`.
- Pasos implementados en vista compartida `livewire/tenant/school-wizard.blade.php`:
  1. Identidad institucional — SIGERD, nombre, régimen, modalidad.
  2. Ubicación — Regional MINERD, Distrito, Provincia, Municipio, dirección física.
  3. Configuración académica — Niveles, paralelos (secciones), tandas, año escolar, títulos técnicos.
  4. Selección de plan — Toggle mensual/anual, grid dinámico desde base de datos.
  5. Director de la escuela — exclusivo del Owner.
- Validación server-side en `validateStep()` por paso, incluyendo regla custom que exige Secundaria cuando la modalidad requiere títulos técnicos.
- Pantalla de progreso animada post-`finish()` con barra, mensajes rotativos y redirección automática.
- Vista modularizada en parciales bajo `wizard/`:
  - `_sidebar.blade.php` (sticky, stepper + resumen en tiempo real)
  - `_intro.blade.php`
  - `_progress-screen.blade.php`
  - `_footer-nav.blade.php`
  - `steps/_step-{1..5}.blade.php`

#### Sistema UI
- Layouts: `components/admin.blade.php`, `components/install.blade.php`, `components/wizard.blade.php`, `components/app.blade.php`.
- Layout administrativo con Sidebar (responsive, colapsable), Navbar y Breadcrumbs automáticos.
- Componentes core:
  - `x-ui.button` — variantes, tamaños, soporte de iconos Heroicons, efecto hover.
  - `x-ui.badge` — 6 variantes semánticas, dot opcional, tamaños `sm` y `md`.
  - `x-ui.toast` — unificado, responde a `session()` de Laravel y eventos `dispatch` de Livewire/Alpine.
  - `x-ui.empty-state`.
- Suite de formularios Line UI (`x-ui.forms.*`):
  - `Input`, `Select`, `Textarea`, `Checkbox`, `Radio`, `Toggle`.
  - Estados: default, focus, error, disabled con transiciones CSS.
  - Detección automática de errores de validación por `name`.
  - Soporte Dark Mode completo.
- Dark Mode con clase `.dark` en Tailwind.
- Fuente base Inter.
- Heroicons vía `blade-ui-kit/blade-heroicons`.

#### Filtrado y Datatables
- Query Filter Pipeline desacoplado del objeto `Request`, compatible con estado reactivo de Livewire.
- Componente `DataTable` genérico con Livewire.
- Componente `GeographicSelects` (Provincia → Municipio → Distrito).

#### Documentación
- Guías técnicas: filtros, datatables, `ui-forms`, `ui-badges`, `ui-buttons`.
- Laboratorio visual de componentes en `resources/views/examples/`.

### Changed

- Modelo `School` refactorizado: campos `regimen_gestion`, `regional_education_id`, `educational_district_id`, `phone`, `address_detail`, `stub_expires_at`; eliminada columna `jornada`.
- Modelo `Grade`: agregados `cycle` y `allows_technical` en `$fillable`; scope `allowsTechnical()`.
- Modelo `SchoolSection`: `$fillable` actualizado a `['school_id', 'grade_id', 'label', 'technical_title_id']`; eliminado campo `name`; relaciones `belongsTo(Grade)` y `belongsTo(TechnicalTitle)`.
- Registro de usuario migrado de Breeze a componentes Livewire dedicados.
- Features de Plan migradas de campo JSON a relación `belongsToMany`.
- Breadcrumbs y estructura del Sidebar actualizados a los nuevos layouts.
- `redirectPath()` centralizado en el modelo `User`.

### Removed

- `FeatureSeeder` y `PlanSeeder` independientes — reemplazados por `PlanFeatureSeeder`.
- Vista legacy `dashboard`.
- Vista legacy `auth/register` de Breeze.
- Trait `BelongsToSchool` del modelo `School` (una escuela no puede pertenecer a sí misma).
- Columna `jornada` de `schools` — las tandas viven en `school_shifts`.

### Internal

- Reorganización de layouts en `resources/views/components/`.
- Unificación y simplificación del sistema de notificaciones Toast.
- Event Discovery activado; eliminados registros manuales de listeners.
- Configuración de idioma español: `laravel-lang/common`, `APP_LOCALE=es`.

---

## [0.1.0] - 2026-01-15

Commit inicial del proyecto. Estructura base de Laravel con configuración de entorno y README.