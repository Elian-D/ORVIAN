# Changelog

Todos los cambios importantes del proyecto **ORVIAN** se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/)
y el proyecto sigue [Semantic Versioning](https://semver.org/lang/es/).

---

## [Unreleased]

> Cambios pendientes antes de cerrar v0.3.0.

---

## [0.3.0] - 2026-03-31

### Added

#### Identidad y Perfil de Usuario
- Campos extendidos en `users`: `avatar_path`, `avatar_color`, `phone`, `position`, `last_login_at`, `status`, `preferences` (JSON).
- `UserAvatarService` con generación de iniciales, color hex automático por paleta y resolución de URL de avatar.
- `UserObserver` para asignar `avatar_color` al crear un usuario.
- Sección de Preferencias en el perfil: tema (claro/oscuro/sistema) y sidebar colapsado (solo admins).
- Componente `x-ui.avatar` con soporte para foto, iniciales, 4 tamaños y prop `showStatus` (punto de presencia).
- Sistema de presencia de usuario: estados `online`, `away`, `busy`, `offline` con cambio manual desde el dropdown del navbar/sidebar y cambio automático por login/logout/inactividad.
- Job `orvian:update-user-status` programado en `routes/console.php` para marcar usuarios inactivos como `away`.

#### Sistema de Temas
- Componente `x-ui.theme-init` — script síncrono que aplica `.dark` desde DB antes del CSS, sin `localStorage`, eliminando el flash de tema incorrecto.
- Integrado en todos los layouts (`app`, `admin`, `install`, `wizard`).

#### Navbar y Shell de Aplicación
- Componente `x-app.navbar` extraído de `layouts/app.blade.php` con dos estados: **Hub** (transparente, compacto) y **Módulo** (sólido, con ícono SVG flip-a-‹, nombre, sub-links).
- Posicionamiento `fixed` en el navbar — los layouts compensan con `pt-14` (módulo) y `pt-[52px]` (hub).
- Drawer lateral en mobile para módulos: botón "Volver al Hub" explícito, ícono, nombre y sub-links con scroll.
- Componente `x-app.module-toolbar` — barra secundaria `sticky top-14` con slots para acciones, buscador y secundarias.
- Layout `layouts/app-module.blade.php` para vistas dentro de módulos.
- Sistema de iconos SVG para módulos: 9 SVGs en `public/assets/icons/modules/` y componente `x-ui.module-icon`.
- `config/modules.php` — fuente de verdad única para nombre, ícono y sub-links de cada módulo. Uso: `->layout('layouts.app-module', config('modules.configuracion'))`.
- Dashboard del panel app (`/app/dashboard`) estilo Odoo: grid de tiles responsivo, saludo dinámico en 7 rangos horarios, sección de accesos recientes.
- Componente `x-ui.app-tile` — tile polimórfico con soporte para SVG de módulo, badge de notificaciones, estado `comingSoon` y animación de entrada `tile-animate`.

#### Gestión de Usuarios
- CRUD completo de usuarios para el panel admin (`/admin/users`) y el panel escuela (`/app/users`).
- Soft delete y restauración en el panel admin.
- Suite de componentes `x-data-table.*`: search, per-page-selector, filter-container, filter-select, filter-toggle, filter-date-range, filter-range, filter-chips, column-selector, cell.
- Sistema de paginación ORVIAN: tres vistas (`orvian-compact`, `orvian-full`, `orvian-ledger`).
- Carga asíncrona con `#[Lazy]` y skeleton polimórfico (`x-ui.skeleton`) con variantes table, card, avatar-text, stats, form.
- Scope `withIndexRelations()` en modelo `User` para eliminar N+1 de Spatie.
- Componente `x-ui.page-header` con slots de acciones y conteo.

#### Roles y Permisos
- `SchoolRoleService` — clona roles globales (`school_id = null`) como roles propios de cada escuela al completar el wizard, heredando permisos, color e `is_system`.
- Tabla `permission_groups` con campo `context` (enum `global`/`tenant`) para separar permisos del Admin Hub de los del panel escuela.
- Seeders: `PermissionGroupSeeder`, `PermissionSeeder`, actualización de `RoleOwnerSeeder` y `RoleAcademicSeeder`.
- Modelo `PermissionGroup` con scopes `tenant()`, `global()`, `ordered()`.
- Modelos extendidos `Role` y `Permission` (sobre Spatie) con soporte de `color`, `is_system`, `school_id` y relación `belongsTo(PermissionGroup)`.
- `SchoolScope` en el modelo `Role` con bypass automático para rutas `admin/*`.
- CRUD de roles en `/admin/roles` y `/app/roles` con color picker, previsualización en vivo con badge dinámico y acción `duplicate()`.
- Matriz de permisos en `/admin/roles/{role}/permissions` y `/app/roles/{role}/permissions` con tabs por grupo, acciones masivas "Marcar todos / Desmarcar todos" y protección de roles de sistema.
- Helper `trans_permission()` y archivos `lang/es/permissions.php`, `lang/es/permission_groups.php`, `lang/es/roles.php`.
- `ProfileModal` — perfil como modal Livewire embebido en los layouts app, abierto desde el dropdown del navbar.

#### Componentes UI Extendidos
- `x-ui.badge`: nuevo prop `hex` para colores arbitrarios con estilos inline (fondo + borde semitransparente).
- `x-ui.button`: prop `hex` con contraste automático YIQ, prop `href` para tag dinámico `<a>`, tipo `ghost` para toolbars, `wire:loading.class` integrado nativo, `aria-label` inferido en modo icono.

#### Observabilidad
- Laravel Pulse en `/admin/pulse` protegido por gate `viewPulse` (solo Owner).
- Log Viewer en `/admin/logs` restringido a `Owner` y `TechnicalSupport`.

#### Administración Global (Owner)
- Dashboard SaaS con métricas de MRR, escuelas activas/inactivas, usuarios totales y gráficos ApexCharts (crecimiento 30 días + distribución por plan).
- Componente `x-admin.stat-card` con micro-interacciones y variantes semánticas.
- Gestión de centros (`/admin/schools`) con filtros avanzados geográficos, estados duales (`is_active` / `is_suspended`), columna de Director y diferenciación de usuarios (Staff vs Estudiantes).
- `EnsureSchoolIsActive` middleware con redirección a vistas de aviso (`suspended.notice`, `inactive.notice`).
- Integración Google Maps en ficha de escuela con fallback visual para coordenadas nulas.
- Gestión de planes (`/admin/plans`) con grid de cards, slide-over de creación/edición, previsualización en tiempo real y unicidad de `is_featured` garantizada por Observer.
- Matriz de asignación de features por plan con toggles agrupados por módulo.
- Componentes reutilizables `x-ui.plan-card` y `x-admin.stat-card`.

#### Configuración Institucional
- Vista `/app/settings/school` con gestión de logo (upload + limpieza de disco), datos institucionales, geografía en cascada y año escolar activo en solo lectura.
- Componentes `x-ui.school-logo` (con overlay de edición hover) y `x-ui.loading` (spinner versátil).
- Campo `logo_path` y `province_id` en tabla `schools`.

#### Wizard — Mejoras
- Agrupación visual de niveles por ciclos académicos (Primer / Segundo Ciclo) con componente `x-wizard.level-card`.
- Automatización del año escolar: calculado desde el mes actual, sin selector manual.
- Validaciones server-side de rango de fechas con mensajes descriptivos.
- Integración de `x-ui.plan-card` en el paso de selección de plan.
- Botón "Volver a Escuelas" visible para Owner en el paso intro del wizard administrativo.

### Changed

- `layouts/app.blade.php` simplificado — navbar extraído a componente, fondo actualizado a `bg-slate-100 dark:bg-[#080e1a]`.
- Sidebar admin adaptado a preferencia `sidebar_collapsed` desde DB, eliminando `localStorage`.
- Dropdown del avatar unificado en ambos contextos (hub y módulo) con selector de estado y enlace a perfil como modal.
- `tailwind.config.js` actualizado con paleta de tema oscuro en tonalidades grises slate.
- Rutas organizadas por dominio en `routes/admin/` y `routes/app/` con middleware `can:` en usuarios y roles.
- `AppServiceProvider` registra observer de `Plan` y paginator por defecto.

### Fixed

- ParseError en `dashboard.blade.php` con `match` dentro de `@php` en PHP 8.5 — lógica movida al controlador.
- Flash de tema incorrecto al navegar — `x-ui.theme-init` aplicado síncronamente en `<head>`.
- Cambio de tema al navegar hub → módulo — eliminado `wire:navigate` en tiles del dashboard (carga completa entre layouts distintos).
- Selector de roles vacío en `UserIndex` — resuelto con `SchoolRoleService::seedDefaultRoles()` al completar el wizard.
- Solapamiento de 4px entre navbar y `module-toolbar` — corregido con `sticky top-14` (navbar módulo es `h-14`, no `h-[52px]`).

### Removed

- `x-app.search` eliminado del navbar de módulo — el buscador contextual se define por módulo en su propio `module-toolbar`.
- Toggle de dark mode de `install.blade.php` y `wizard.blade.php` — reemplazado por el sistema de preferencias en perfil.
- Listener `AssignInitialRoles` — la asignación de rol al Director queda garantizada por el orden de ejecución en las Actions (roles clonados antes del evento `SchoolConfigured`).

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
  - `SetupAcademicStructure` — crea secciones por nivel, grado y paralelo.
  - `CreateInitialAcademicYear` — crea el año escolar con las fechas del wizard.
  - `AssignInitialRoles` — confirma el rol `School Principal` en scope del tenant.
- Event Discovery activo; sin registros manuales en `EventServiceProvider`.

#### Onboarding y Seguridad
- Flujo de Self-Installation del Owner en `/register` con middleware `EnsureSystemIsInstalled`.
- Registro público en `/sign-up` con creación automática de escuela stub.
- Comando `orvian:cleanup-stubs` programado para eliminar stubs vencidos.
- Middlewares: `EnsureSystemIsInstalled`, `RedirectIfSystemNotInstalled`, `EnsureOnboardingIsComplete`, `EnsureGlobalAdminAccess`.

#### Wizard de Configuración de Escuela
- Arquitectura de herencia `BaseSchoolWizard` con `SchoolWizard` (Owner, 5 pasos) y `TenantSetupWizard` (Tenant, 4 pasos).
- Validación server-side por paso con regla custom para modalidades técnicas.
- Pantalla de progreso animada post-`finish()` con redirección automática.

#### Sistema UI
- Layouts: `components/admin.blade.php`, `components/install.blade.php`, `components/wizard.blade.php`, `components/app.blade.php`.
- Sidebar administrativo responsive y colapsable.
- Componentes core: `x-ui.button`, `x-ui.badge`, `x-ui.toast`, `x-ui.empty-state`.
- Suite `x-ui.forms.*`: Input, Select, Textarea, Checkbox, Radio, Toggle con dark mode completo.
- Query Filter Pipeline compatible con Livewire y componente `GeographicSelects`.

### Changed

- Modelo `School` refactorizado con nuevos campos y eliminación de `jornada`.
- Modelo `Grade` con `cycle` y `allows_technical`.
- Registro de usuario migrado de Breeze a Livewire.
- Features de Plan migradas de JSON a `belongsToMany`.

### Removed

- `FeatureSeeder` y `PlanSeeder` independientes — reemplazados por `PlanFeatureSeeder`.
- Vistas legacy `dashboard` y `auth/register` de Breeze.
- Trait `BelongsToSchool` y columna `jornada` de `schools`.

---

## [0.1.0] - 2026-01-15

Commit inicial del proyecto. Estructura base de Laravel con configuración de entorno y README.