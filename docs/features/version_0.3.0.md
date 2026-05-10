# ORVIAN v0.3.0 — Core Platform & Governance

**Rama PADRE:**
`feature/core-platform`

**Objetivo:** Consolidar la plataforma SaaS con gestión de identidad, administración global, auditoría, seguridad y configuración institucional antes de construir módulos educativos.

---

## Fase 1 — Identidad y Perfil de Usuario
**Rama:** `feature/user-identity`

### Migración y modelo

- [x] Agregar campos a `users`:
  - `avatar_path` — nullable, ruta del archivo subido
  - `avatar_color` — string, color hex generado automáticamente al crear el usuario
  - `phone` — nullable
  - `position` — nullable (cargo dentro del centro)
  - `last_login_at` — timestamp nullable
  - `status` — string: `online`, `away`, `busy`, `offline` (default: `offline`)
  - `preferences` — JSON nullable, cast a `array` en el modelo

- [x] Crear `UserAvatarService` (`app/Services/Users`):
  - `initials(string $name): string` — extrae iniciales del nombre
  - `generateColor(): string` — color hex de paleta predefinida con buen contraste
  - `avatarUrl(User $user): string` — retorna URL de foto si existe, o URL del avatar generado

- [x] Observer `UserObserver`: asignar `avatar_color` automáticamente en el evento `creating`

- [x] Agregar campo `preferences` (JSON) a la migración de `users`
  - Cast en el modelo: `'preferences' => 'array'`
  - Helper en el modelo: `preference(string $key, mixed $default = null): mixed`
    ```php
    public function preference(string $key, mixed $default = null): mixed
    {
        return data_get($this->preferences, $key, $default);
    }
    ```
  - Preferencias disponibles para todos los usurios con excepcion en una cosa:
    - `theme` — `light` / `dark` / `system`
    - `sidebar_collapsed` — `bool` (esto es solo para admin/tenant ya que sol ahi hay sidebaer)
  - Estas preferencias se configuran desde el componente de perfil y se leen en los layouts

- [x] Agregar columna `status` a la migración de `users`
  - Valores: `online`, `away`, `busy`, `offline`
  - Lógica de cambio **manual**: dropdown del avatar en el navbar y sidebar con selector de estado
  - Lógica de cambio **automático**:
    - Al hacer login exitoso → `online`
    - Al cerrar sesión → `offline`
    - Sin actividad por N (15) minutos → `away` (vía job programado que revisa `last_login_at` o `sessions`)
    - Comando o job `orvian:update-user-status` programado cada X minutos en `routes/console.php`
  - Rellenar `last_login_at` en el evento de login: listener sobre `Illuminate\Auth\Events\Login`
  - Actualizar zona horaria para que sea compatible con RD `config/app.php`

### Upload de foto

- [x] Disco `public`, ruta `storage/avatars/users/{id}` — puente con `sail artisan storage:link`
- [x] Validación: `image`, `max:2048`, formatos `jpg,jpeg,png,webp`
- [x] Eliminar foto anterior al subir una nueva

### Vista de perfil

- [x] Ruta `GET /app/profile` → `App\Livewire\Shared\Profile` (layout: `layouts.app`)
- [x] Ruta `GET /admin/profile` → `App\Livewire\Shared\Profile` (layout: `components.admin`)
- [x] Propiedad `$isAdmin` detectada en `mount()` via `request()->routeIs('admin.profile')`
- [x] Secciones: información personal, foto de perfil, seguridad
- [x] Correo editable solo en contexto admin; solo lectura en contexto app
- [x] Eliminado `ProfileController` y vistas legacy de Breeze

- [x] Agregar sección **Preferencias** al perfil (visible la preferencia de `sidebar_collapsed` solo para roles sin `school_id` ):
  - Toggle de tema: Claro / Oscuro / Sistema
  - Toggle de sidebar colapsado por defecto
  - Guardar en `user->preferences` vía método `savePreferences()` en el componente

### Layout de admin, y navbar

- [x] Adaptar el sidebar para qeu funcione mediante la preferencia del usuario, pero quitar la validacion del localshotrage.
- [x] Corregir el toogle del navbar para que actualice el estado solo en esa pestaña y que de feedback al usuario con un tooltip.

### Componente de avatar reutilizable

- [x] Crear `x-ui.avatar`:
  - Props: `user`, `size` (`sm` / `md` / `lg` / `xl`)
  - Si tiene foto: muestra `<img>` con `object-cover`
  - Si no: muestra iniciales sobre `avatar_color`
  - Usado en: navbar, sidebar, perfil, wizard paso 3

- [x] Agregar indicador de `status` al componente `x-ui.avatar`:
  - Punto de color en la esquina inferior derecha del avatar
  - Colores: `online` → verde, `away` → amarillo, `busy` → rojo, `offline` → gris
  - Prop `showStatus` (bool, default: `false`) para activarlo opcionalmente
  - Ejemplo de uso en navbar: `<x-ui.avatar :user="auth()->user()" size="md" showStatus />`

---

## Fase 2 — Sistema de Temas (Dark Mode Híbrido)
**Rama:** `feature/theme-system`

- [x] Crear <x-ui.theme-init /> — lee DB, aplica .dark síncronamente, sin localStorage
- [x] Integrar en los 4 layouts (app, admin, install, wizard) antes de @vite
- [x] Eliminar darkMode de x-data en <html> de todos los layouts
- [x] Eliminar :class="{ 'dark': darkMode }" de todos los layouts
- [x] Eliminar $watch('darkMode', ...) de todos los layouts
- [x] Eliminar el <script> anti-flash inline de admin.blade.php (reemplazado)
- [x] Eliminar botones/toggles de dark mode en navbars y sidebars
- [x] Actualizar Profile → savePreferences() para que dispatch un evento que recargue la página tras guardar (para aplicar el nuevo tema sin que el usuario recargue manualmente)
- [x] Preferencias visuales — sidebar_collapsed visible solo para admins (isAdmin):
  - [x] Condicional @if($isAdmin) en la vista de Profile para el bloque de sidebar
  - [x] Validación en savePreferences(): ignorar sidebar_collapsed si !$isAdmin
        (evita que un usuario de escuela manipule la propiedad vía Livewire directamente)

---

## Fase 3 — Navbar de Aplicación
**Rama:** `feature/app-navbar`

- [x] Extraer el navbar actual de `layouts/app.blade.php` a `resources/views/components/app/navbar.blade.php`

- [x] app.blade.php limpio usando <x-app.navbar />

- [x] Rediseñar el navbar con dos estados visuales:

  **Estado inicial (en el hub/dashboard):**
  - Fondo transparente, sin sombra
  - Altura reducida (≈ 52px)
  - Solo muestra: logo ORVIAN a la izquierda (con boton para volver a la vista anterior, si hubo una vista anterior.) + acciones de usuario a la derecha

  **Estado módulo (dentro de cualquier módulo):**
  - Creacion de layout `layouts/app-module.blade.php` para manajar el estado de links
  - Fondo sólido (color del sistema según tema) con sombra sutil
  - Sección izquierda: botón ← para volver al hub, ícono del módulo activo, nombre del módulo, links internos del módulo
  - Sección central: buscador contextual (componente `x-app.search` separado, configurable por módulo)
  - Sección derecha: ícono de conversaciones/notificaciones, separador, avatar + dropdown del usuario
  - La transición entre estados es vía Alpine detectando la ruta actual o una propiedad inyectada

- [x] Crear componente `x-app.module-toolbar` — barra secundaria debajo del navbar, solo presente en vistas de módulo:
  - Sección izquierda: botones de acción del módulo (Crear, Guardar, Exportar — se definen por módulo)
  - Sección central: buscador avanzado del módulo (slot o componente Livewire)
  - Sección derecha: acciones secundarias (filtros, columnas visibles, etc.)
  - Se activa incluyendo `<x-app.module-toolbar>` en las vistas del módulo

- [x] Dropdown del usuario en el navbar — reorganizar con:
  - Avatar + nombre + cargo/rol arriba
  - Selector de estado manual: Online / Ocupado / Ausente / Desconectado
  - Separador
  - Link a Mi Perfil (donde estan las preferencias)
  - Separador
  - Cerrar sesión

---

## Fase 4 — Gestión de Usuarios
**Rama:** `feat/user-management`

### 4.0 — Organización de rutas por módulo

- [x] Crear directorio `routes/admin/` para rutas del panel administrativo
- [x] Crear directorio `routes/app/` para rutas de los módulos de escuela
- [x] Actualizar `routes/web.php` para importar ambos directorios
- [x] Crear `routes/admin/users.php`
- [x] Crear `routes/app/users.php`

---

### 4.1 — Usuarios globales (SuperAdmin) — `/admin/users`

#### Filtros
- [x] `app/Filters/Admin/Users/SearchFilter.php`
- [x] `app/Filters/Admin/Users/RoleFilter.php`
- [x] `app/Filters/Admin/Users/StatusFilter.php`
- [x] `app/Filters/Admin/Users/AdminUserFilters.php`

#### Tabla config
- [x] `app/Tables/Admin/AdminUserTableConfig.php`

#### Componente Livewire
- [x] `app/Livewire/Admin/Users/UserIndex.php` — CRUD completo, soft delete y helpers estáticos
- [x] `->withIndexRelations()` aplicado en `render()` para eliminar N+1 de Spatie

#### Vista
- [x] `resources/views/livewire/admin/users/index.blade.php` — columnas `x-data-table.cell`, modales, `x-ui.page-header`

---

## Fase 4.2 — DataTable Component Suite
**Rama:** `feat/user-management` (continuación)

### 4.2.0 — Mejoras al DataTable base

- [x] `public int $perPage = 14`
- [x] `updatedPerPage()` → `resetPage()`
- [x] `updatedFilters()` → `resetPage()`
- [x] `getActiveChips(): array`
- [x] `clearFilter(string $key)`
- [x] `clearAllFilters()`
- [x] `resetColumns(bool $mobile = false)` — device-aware, Alpine pasa `isMobile`
- [x] `paginationView(): string` → `'pagination.orvian-compact'`
- [x] `paginationSimpleView(): string` → `'pagination.orvian-compact'`
- [x] `use WithPagination` presente en la clase base

---

### 4.2.1 — TableConfig: contrato y trait

- [x] Interfaz `app/Tables/Contracts/TableConfig.php`
- [x] `cellClass()` en interfaz y en `HasResponsiveColumns` trait
- [x] Trait `app/Tables/Concerns/HasResponsiveColumns.php`
- [x] Implementado en `AdminUserTableConfig` con `filterLabels()`

---

### 4.2.2 — Componentes Blade: x-data-table.*

- [x] `x-data-table.search` — input con lupa y botón ×
- [x] `x-data-table.per-page-selector` — select pill con opciones configurables
- [x] `x-data-table.filter-container` — dropdown desktop + drawer mobile con conteo y "Limpiar filtros"
- [x] `x-data-table.filter-select` — select dentro de `filter-container`
- [x] `x-data-table.filter-toggle` — toggle booleano, estado 100% Alpine con `$wire.set()`
- [x] `x-data-table.filter-date-range` — dos inputs date (desde / hasta) con limpiar
- [x] `x-data-table.filter-range` — dos inputs numéricos (mín / máx) con prefix/suffix
- [x] `x-data-table.column-selector` — checkboxes reactivos via Alpine `:checked="isVisible(key)"`, drawer en mobile, Restablecer device-aware, guard de mínimo 1 columna

---

### 4.2.3 — Chips de filtros activos

- [x] `x-data-table.filter-chips` — chip por filtro con × individual + "Limpiar todo"

---

### 4.2.4 — base-table.blade.php

- [x] Toolbar dividido en 3 secciones: acciones (slot externo `x-ui.page-header`), filtros, chips
- [x] Estructura: búsqueda (`flex-1`) + per-page + `filterSlot` + column-selector en una línea
- [x] Chips debajo del toolbar — auto-oculto si no hay filtros activos
- [x] Dark mode completo — hover `dark:hover:bg-white/[0.03]`
- [x] `custom-scroll` en contenedor y en dropdowns internos
- [x] Footer con paginación `{{ $items->links() }}` + conteo "Mostrando X–Y de Z"

---

### 4.2.4 — Feedback de carga contextual en base-table

- [x] **Nivel 1 — Atenuación inmediata:** `wire:loading.class="opacity-60 pointer-events-none"` en contenedor de tabla
- [x] **Nivel 2 — Overlay con blur + badge:** `wire:loading.flex.delay.long` — aparece solo si la petición tarda > 400ms
- [x] Footer fuera del `div.relative` — no cubierto por el overlay

---

### 4.2.6 — Jerarquía visual: x-ui.page-header

- [x] `resources/views/components/ui/page-header.blade.php`
  - Props: `title`, `description`, `count`, `countLabel`
  - Slot `$actions` — acciones primarias fuera de la tabla
- [x] `UserIndex` admin usa `x-ui.page-header` con `$users->total()`
- [x] Slot `actions` eliminado de `base-table`

---

### 4.2.7 — Componente `x-data-table.cell`

- [x] `resources/views/components/data-table/cell.blade.php`
  - Props: `column`, `visible`, `class`
  - No renderiza si columna no está en `$visible`

---

### 4.2.8 — Sistema de paginación ORVIAN

- [x] `pagination/orvian-compact.blade.php` — compact con botones numéricos (default de todos los DataTable)
- [x] `pagination/orvian-full.blade.php` — con Anterior/Siguiente, elipsis, "Ir a página"
- [x] `pagination/orvian-ledger.blade.php` — pill compacto para datasets masivos / mobile
- [x] `AppServiceProvider::boot()` — `Paginator::defaultView('pagination.orvian-compact')`
- [x] `paginationView()` y `paginationSimpleView()` en `DataTable` base

---

### 4.2.9 — Carga asíncrona y skeleton

- [x] `#[Lazy]` en `DataTable` base — todos los DataTable cargan en 2 fases automáticamente
- [x] `placeholder(): View` en `DataTable` — devuelve `x-ui.skeleton` con `type=table` y `rows=$perPage`
- [x] `resources/views/components/ui/skeleton.blade.php` — componente polimórfico con variantes: `table`, `card`, `avatar-text`, `stats`, `form`
- [x] Documentar en `docs/architecture/lazy-loading.md`

---

### 4.2.10 — Eliminar N+1 (Eager Loading centralizado)

- [x] `scopeWithIndexRelations()` en modelo `User` — carga `roles` en 1 query adicional
- [x] Aplicado en `UserIndex::render()` — consulta pasa de ~107 a 3-4 queries
- [x] Documentar patrón en `docs/architecture/n-plus-one.md`
- [x] Definir `scopeWithIndexRelations()` en todos los modelos que se usen en tablas

---

### 4.2.11 — Opcionales (post-usuarios)

- [NO_AHORA] `x-data-table.export-button`
- [NO_AHORA] `x-data-table.bulk-actions`
- [NO_AHORA] `x-data-table.filter-datetime-range`

---

### 4.3 — Usuarios de escuela (Tenant) — `/app/users`

#### Filtros
- [x] `app/Filters/App/Users/SearchFilter.php`
- [x] `app/Filters/App/Users/RoleFilter.php` — scoped por `school_id` de Spatie
- [x] `app/Filters/App/Users/StatusFilter.php`
- [x] `app/Filters/App/Users/TenantUserFilters.php`

#### Tabla config
- [x] `app/Tables/App/TenantUserTableConfig.php`

#### Componente Livewire
- [x] `app/Livewire/App/Users/UserIndex.php`
  - `mount()` obtiene `$schoolId`; redirige si no hay escuela
  - `->withIndexRelations()` en query
  - Valida límite del plan antes de crear
  - `delete()` y `toggleActive()` no permiten operar sobre sí mismo
- [x] Registrar ruta en `routes/app/users.php`

#### Vista
- [x] `resources/views/livewire/app/users/index.blade.php`
  - Badge "X de Y usuarios" con `x-ui.badge` variant `warning` si supera el 80%
  - Sin tab de eliminados
  - Botón "Nuevo usuario" desactivado si se alcanzó el límite

---

### 4.4 — Soft Delete y restauración

- [x] Migración `users` con `$table->softDeletes()`
- [x] Modelo `User` con `use SoftDeletes`
- [x] Tab "Eliminados" en UserIndex admin
- [x] Acción "Restaurar" solo en tab de eliminados

---

### 4.5 — Pendientes globales de la fase

- [x] Actualizar `docs/architecture/datatables.md` (ver 4.4.1)
- [x] Crear `docs/architecture/datatable-components.md` (ver 4.4.2)
- [x] Crear `docs/architecture/lazy-loading.md` (ver 4.2.9)
- [x] Crear `docs/architecture/n-plus-one.md` (ver 4.2.10)
- [x] Agregar links a `/admin/users` y `/app/users` en sidebar y menú app
- [x] `app.css` con `custom-scroll` mejorado en dark mode
- [x] `DataTable` base funcional con paginación y `toggleColumn()`

---

## Fase 4.6 — Módulo Core: Iconos, Dashboard App y Navbar
**Rama:** `feature/app-shell-refactor`

**Objetivo:** Establecer la identidad visual del panel de centro educativo antes de seguir
construyendo módulos. Esto incluye el sistema de iconos SVG, el dashboard de acceso rápido
tipo Odoo, el layout del panel app y la navegación por módulos.

---

### 4.6.0 — Sistema de iconos de módulos

- [x] Crear directorio `public/assets/icons/modules/`
- [x] Colocar los SVG exportados de cada módulo (solo `viewBox`, sin `width`/`height` fijos, `fill` como atributo directo):
  - `administracion.svg`
  - `conversaciones.svg`
  - `asistencia.svg`
  - `academico.svg`
  - `notas.svg`
  - `classroom.svg`
  - `horarios.svg`
  - `reportes.svg`
  - `web.svg`
- [x] Crear `app/View/Components/Ui/ModuleIcon.php` + `resources/views/components/ui/module-icon.blade.php`:
  - Renderiza solo `<img>` del SVG via `asset('assets/icons/modules/{name}.svg')`
  - Sin contenedor, sin fondo — el contexto decide tamaño y envoltorio
  - `$attributes->merge(['class' => 'object-contain'])` para que Tailwind pueda controlar tamaño
  - Uso en navbar: `<x-ui.module-icon name="administracion" class="w-5 h-5" />`
  - Uso en tarjeta: `<x-ui.module-icon name="asistencia" class="w-full h-full" />`

---

### 4.6.1 — Componente `x-ui.app-tile`

- [x] `resources/views/components/ui/app-tile.blade.php` — componente anónimo `@props`, sin clase PHP
- [x] Props: `icon`, `module`, `title`, `subtitle`, `color`, `accent`, `url`, `badge`, `comingSoon`
- [x] Diseño estilo Odoo: `<a>` transparente sin card envolvente — el hover y borde viven solo en el cuadrado del ícono (`w-[72px] h-[72px] rounded-2xl`)
- [x] Cuando `module`: ícono SVG via `<x-ui.module-icon>` en `w-10 h-10`, contenedor con fondo blanco/dark y borde sutil
- [x] Cuando `icon`: heroicon centrado sobre `<div>` con clase `$color` y `box-shadow` opcional via `$accent`
- [x] `comingSoon="true"`: opacidad 50%, `pointer-events-none`, muestra `<x-ui.badge variant="slate" size="sm">Pronto</x-ui.badge>` en esquina superior derecha
- [x] `badge > 0`: muestra `<x-ui.badge variant="primary" size="sm">` con el número en esquina superior derecha
- [x] `wire:navigate` solo si `!$comingSoon && $url !== '#'`
- [x] Animación de entrada via clase `tile-animate` definida en `app-layout`
- [x] **Nota:** no se usa clase PHP — `@props` anónimo evita el problema de variables no expuestas en vista

---

### 4.6.2 — Dashboard del panel app (`/app/dashboard`)

- [x] `resources/views/app/dashboard.blade.php` — usa `x-app-layout`
- [x] Saludo dinámico por hora del día — 7 rangos con mensajes distintos:
  - 5–9h: "Buenos días / ¿Listo para comenzar el día?"
  - 9–12h: "Buenos días / ¿Qué vas a gestionar hoy?"
  - 12–14h: "Buenas tardes / Un buen momento para revisar el avance."
  - 14–17h: "Buenas tardes / ¿En qué trabajamos esta tarde?"
  - 17–19h: "Buenas tardes / Últimas horas del día — ¿algo pendiente?"
  - 19–22h: "Buenas noches / Terminando la jornada, ¿todo en orden?"
  - 22–5h: "Buenas noches / Trabajando tarde. Aquí estamos."
- [x] Fecha localizada en español: `now()->locale('es')->isoFormat('dddd, D [de] MMMM')`
- [x] Grid de módulos: `grid-cols-4 sm:grid-cols-5 lg:grid-cols-6` — compacto como Odoo, sin separador de sección
- [x] Módulo activo: **Administración** → `route('app.users.index')`
- [x] Módulos `comingSoon`: asistencia, conversaciones, académico, notas, classroom, horarios, reportes, web
- [x] Sección "Accesos recientes" como placeholder con `heroicon-o-clock` + texto
- [x] Sin separador "Módulos" — redundante cuando el grid ya es obvio
- [x] Dark mode via clases `dark:` estáticas — sin `:class="darkMode ? ..."`

---

### 4.6.3 — Layout `layouts/app.blade.php`

- [x] Revisar y corregir el layout completo del panel de escuela:
  - Agregar nombre del centro en el centro del navbar en el inicio del modulo + el icono de notificioanes junto al perfil
  - Eliminar buscador por defecto que tiene en la vista de modulos.

---

### 4.6.4 — Navbar del panel app refactorizado

- [x] Refactorizar `resources/views/components/app/navbar.blade.php`:

  **Posicionamiento: `fixed` (no `sticky`)**
  - El navbar se clava en `top-0 left-0 right-0` — el contenido pasa por debajo
  - Los layouts compensan el espacio con `pt-14` (módulo) y `pt-[52px]` (hub)
  - El `module-toolbar` usa `sticky top-14` — queda fijo justo debajo del navbar

  **Estado HUB — desktop y mobile iguales:**
  - Izquierda: botón `›` historia
  - Centro: nombre del centro (`Auth::user()->school->name`)
  - Derecha: notificaciones + avatar + dropdown
  - Navbar transparente en top, sólido al scroll (Alpine)

  **Estado MÓDULO desktop (lg+):**
  - Izquierda: ícono SVG flip-a-‹ + nombre del módulo + sub-links inline
  - Centro: vacío
  - Derecha: notificaciones + avatar + dropdown
  - Navbar siempre sólido (`h-14`)

  **Estado MÓDULO mobile (< lg) — drawer lateral:**
  - Navbar muestra: `☰` + nombre módulo + notificaciones + avatar
  - Drawer: botón "← Volver al Hub" explícito + × cerrar + ícono + nombre + sub-links con `custom-scroll`
  - Indicador de link activo: punto naranja
  - Click en link cierra el drawer automáticamente

  **`moduleIcon`:** nombre del SVG (`'administracion'`), no heroicon string.

- [x] Actualizar `layouts/app-module.blade.php` — `<main class="flex-1 pt-14">` para compensar navbar fixed de módulo (`h-14`)
- [x] `module-toolbar` usa `sticky top-14` (default `navbarHeight`) — queda debajo del navbar fixed sin solapamiento
- [x] Actualizar todos los `->layout()` que pasen `moduleIcon` para usar nombre SVG
- [x] `Profile.php` y `App/Users/UserIndex.php` usan `'moduleIcon' => 'administracion'`
- [x] Mobile drawer usa `custom-scroll` (definido en `app.css`)

---

### 4.6.5 — Módulo "Administración" conectado

- [x] Registrar módulo en dashboard y navbar:
  - Icono: `administracion.svg`
  - Ruta raíz: `app.users.index`
  - Sub-navegación: Usuarios (`app.users.index`), Roles (placeholder), Configuración (placeholder)
- [x] Tab activo en navbar detectado por `routeIs('app.users.*')`

---

### 4.6.5b — Registro centralizado de módulos (`config/modules.php`)

- [x] Crear `config/modules.php` — fuente de verdad única para nombre, ícono SVG y sub-links de cada módulo
- [x] Estructura por clave de módulo: `module`, `moduleIcon`, `moduleLinks`
- [x] Módulo `configuracion` definido con Mi Perfil + Usuarios + placeholders comentados para Roles y Permisos
- [x] Módulos futuros (`asistencia`, `academico`, `notas`, etc.) predefinidos como comentarios listos para descomentar
- [x] Uso en componentes Livewire: `->layout('layouts.app-module', config('modules.configuracion'))`
- [x] `Profile.php` render() actualizado — usa `config('modules.configuracion')` en lugar de array inline
- [x] `App/Users/UserIndex.php` render() actualizado — usa `config('modules.configuracion')` en lugar de array inline
- [x] **Regla:** cualquier componente nuevo que pertenezca a un módulo existente usa `config('modules.{clave}')` — nunca define `moduleLinks` manualmente
- [x] **Para agregar un sub-link:** solo editar `config/modules.php` en un lugar — se propaga a todos los componentes del módulo automáticamente
- [x] En producción: `php artisan config:cache` para incluir en cache. En desarrollo: `php artisan config:clear` para reflejar cambios.

---

### 4.6.6 — Pendientes de la fase

- [x] Confirmar que los SVG usan `fill` como atributo directo y no tienen `width`/`height` fijos
- [x] Documentar sistema de iconos en `docs/architecture/module-icons.md`
- [x] Laboratorio de pruebas (Demos) creado en `resources/views/examples/`:
        * `module-icons-demo.blade.php`


---

## Fase 5 — Roles y Permisos (UI)
**Rama:** `feature/roles-permissions-ui`

---

### 5.0 — Prerequisito: Roles de sistema por escuela

**Problema:** Los roles base (`School Principal`, `Teacher`, `Secretary`) se crean sin
`school_id` en el seeder. Con Spatie Teams, `UserIndex` busca `Role::where('school_id', $id)`
y no los encuentra → el selector de roles aparece vacío al crear/editar usuarios.

**Decisión de arquitectura:** Clonar roles de sistema por escuela al completar el wizard.
Cada escuela tiene sus propias instancias de los roles base. Esto permite personalizar
permisos por tenant independientemente en la matriz de permisos (Fase 5.3).

- [x] Crear `app/Services/School/SchoolRoleService.php`:
  - `seedDefaultRoles(School $school): void` — crea los roles base para una escuela clonando
    los roles globales (`school_id = null`) como plantillas: `School Principal`, `Teacher`,
    `Secretary`, `Student`, `Staff`
  - `clonePermissions(Role $source, Role $target): void` — copia los permisos del rol global
    al rol del tenant; solo se ejecuta si el rol fue recién creado (`wasRecentlyCreated`)
  - El service maneja `setPermissionsTeamId()` internamente — lo setea al `$school->id` antes
    de crear los roles y lo resetea a `null` al terminar

- [x] Actualizar `app/Actions/Tenant/CompleteOnboardingAction.php` (flujo Owner / `SchoolWizard`):
  - Inyectar `SchoolRoleService` en el constructor
  - Llamar `$this->roleService->seedDefaultRoles($school)` después de `createPrincipal->execute()`
    y antes de `event(new SchoolConfigured(...))`
  - `SchoolWizard` no cambia — la responsabilidad queda en el Action

- [x] Actualizar `app/Actions/Tenant/CompleteTenantOnboardingAction.php` (flujo Tenant / `TenantSetupWizard`):
  - Inyectar `SchoolRoleService` en el constructor
  - Llamar `$this->roleService->seedDefaultRoles($school)` antes de `assignRole('School Principal')`
    — los roles del tenant deben existir antes de asignarlos al usuario
  - Agregar `setPermissionsTeamId(null)` explícito después de `assignRole` para resetear el scope
    antes de disparar `SchoolConfigured` y sus listeners
  - `TenantSetupWizard` no cambia — la responsabilidad queda en el Action

- [x] Actualizar `RoleAcademicSeeder` (`database/seeders/AppInit/RoleAcademicSeeder.php`):
  - Agregar el rol `Staff` al array `$schoolRoles` — estaba ausente pero sí figura en `SchoolRoleService::BASE_ROLES`
  - Los roles siguen siendo globales (`school_id = null`) — son plantillas de referencia

- [x] Actualizar `App/Users/UserIndex::render()` — la query de roles ya usa
  `Role::where('school_id', $this->schoolId)` correctamente. Una vez ejecutado
  `seedDefaultRoles()` en el wizard, el selector funciona.

- [x] Agregar a `ModuleRegistry` (`app/Support/ModuleRegistry.php`) el link de Roles
  en la entrada `administracion`:
```php
  'administracion' => [
      'name' => 'Configuración',
      'icon' => 'administracion',
      'links' => [
          ['label' => 'Mi Perfil', 'route' => 'app.profile'],
          ['label' => 'Usuarios',  'route' => 'app.users.index'],
          ['label' => 'Roles',     'route' => 'app.roles.index'],   // ← agregar
      ],
  ],
```

---

### 5.1 — Traducciones Estructuradas (Refactor)

- [x] **Traducciones de Grupos:** Implementar `lang/es/permission_groups.php` usando arrays `['name', 'description']`.
- [x] **Diccionario de Acciones:** Implementar `lang/es/permissions.php` siguiendo la jerarquía técnica `categoría.acción`.
- [x] **Helper `trans_permission`:** - Ubicación: `app/Helpers/PermissionHelper.php`.
  - Lógica: Búsqueda dinámica con fallback de limpieza de strings (`str_replace`).
- [x] **Actualización de Vistas:**
```blade
{{-- Para el nombre del grupo --}}
{{ __("permission_groups.{$group->slug}.name") }}

{{-- Para la descripción del grupo --}}
{{ __("permission_groups.{$group->slug}.description") }}

{{-- Para el permiso (usa el helper) --}}
{{ trans_permission($permission->name) }} {{-- Retorna label por defecto --}}
{{ trans_permission($permission->name, 'description') }}
```



**Archivo:** `app/Livewire/Admin/Users/UserIndex.php` (Lógica) y `resources/views/livewire/admin/users/index.blade.php` (Vista)
**Archivo:** `app/Livewire/App/Users/UserIndex.php` y `resources/views/livewire/app/users/index.blade.php`

---

### 5.2 — Organización de permisos por grupo

**Decisión:** `guard_name` es un campo técnico de Spatie — no sirve para agrupar
semánticamente. Se mantiene la tabla `permission_groups` separada con `slug` que
sirve como clave de traducción en `lang/es/permissions.php`.

**Decisión de contextos:** Agregar campo `context` (enum: `global`, `tenant`) para separar
los permisos del panel admin de los permisos del panel escuela. Esto evita filtros adicionales
en queries y mantiene la UI consistente — la matriz del admin solo muestra grupos globales,
la del director solo muestra grupos de tenant.

- [x] Crear migración `permission_groups`:
```php
  id, 
  name (string), 
  slug (string unique), 
  context (enum: 'global', 'tenant', default 'tenant'),
  order (int default 0), 
  timestamps
```
  > El `slug` debe coincidir exactamente con las claves de `lang/es/permissions.php groups`

- [x] Agregar columna `group_id` nullable + FK a `permissions` (tabla de Spatie):
```php
  $table->foreignId('group_id')->nullable()->constrained('permission_groups')->nullOnDelete();
```

- [x] `seeders/AppInit/PermissionGroupSeeder` — grupos base en orden lógico:
  
  **Context `tenant` (panel escuela):**
  | order | name | slug | context |
  |---|---|---|---|
  | 1 | Usuarios | usuarios | tenant |
  | 2 | Roles | roles | tenant |
  | 3 | Configuración | configuracion | tenant |
  | 4 | Académico | academico | tenant |
  | 5 | Asistencia | asistencia | tenant |
  | 6 | Reportes | reportes | tenant |
  
  **Context `global` (panel admin):**
  | order | name | slug | context |
  |---|---|---|---|
  | 1 | Gestión de Escuelas | escuelas | global |
  | 2 | Planes y Facturación | planes | global |
  | 3 | Usuarios del Sistema | usuarios_globales | global |
  | 4 | Sistema y Acceso | sistema | global |
  | 5 | Logs y Observabilidad | logs | global |

- [x] `seeders/AppInit/PermissionSeeder` — permisos base con `group_id` asignado:
  
  **Context `tenant`:**
  - Grupo `usuarios`: `users.view`, `users.manage`
  - Grupo `roles`: `roles.view`, `roles.manage`
  - Grupo `configuracion`: `settings.view`, `settings.update`
  
  **Context `global`:**
  - Grupo `escuelas`: `manage schools`, `view schools`
  - Grupo `planes`: `manage plans`
  - Grupo `usuarios_globales`: `manage global users`
  - Grupo `sistema`: `access admin hub`, `impersonate users`
  - Grupo `logs`: `view activity logs`, `view auth logs`

- [x] Actualizar `RoleOwnerSeeder` (`database/seeders/AppInit/RoleOwnerSeeder.php`):
  - Expandir permisos del rol `Owner` para incluir todos los permisos globales
  - Asignar permisos correctos a `TechnicalSupport` (lectura + acceso hub)
  - Asignar permisos correctos a `Administrative` (facturación y escuelas, sin sistema)
```php
  // Owner — todos los permisos globales
  $ownerRole->givePermissionTo([
      'manage schools',
      'view schools',
      'manage plans',
      'manage global users',
      'access admin hub',
      'impersonate users',
      'view activity logs',
      'view auth logs',
  ]);
  
  // TechnicalSupport — lectura + acceso hub + logs
  $supportRole->givePermissionTo([
      'access admin hub',
      'view schools',
      'view activity logs',
      'view auth logs',
  ]);
  
  // Administrative — facturación y escuelas, sin sistema ni suplantación
  $adminRole->givePermissionTo([
      'access admin hub',
      'manage plans',
      'manage schools',
      'manage global users',
  ]);
```

- Registrar seeders en `DatabaseSeeder`

- [x] `RoleAcademicSeeder` (`database/seeders/AppInit/RoleAcademicSeeder.php`) Paara que use los permisos definidos anteriormente.

- [x] Modelo `PermissionGroup` (`app/Models/PermissionGroup.php`):
  - `hasMany` a `Permission` de Spatie
  - Scope `ordered()` → `orderBy('order')`
  - Scope `tenant()` → `where('context', 'tenant')`
  - Scope `global()` → `where('context', 'global')`
  - Helper `label(): string` → `__('permissions.groups.' . $this->slug)`
  - Cast `context` como enum si usas PHP 8.1+

- [x] Crear modelo `app/Models/Permissions.php` que extienda de `SpatiePermission` para colcoar una relacion inversa BelongsTo hacia PermissionGroup. Por consecuencia se modifico `config/permissions.php` para que el modelo de permiso anterior apunte hacia el nuevo.

---

### 5.2.1 — Refactorización del Componente Badge (`x-ui.badge`)

**Problema:** El componente actual `x-ui.badge` es rígido; depende exclusivamente de clases de Tailwind pre-compiladas mediante el prop `variant` (ej. `primary`, `success`). En la Fase 5.3, los usuarios administradores podrán crear roles personalizados y asignarles un color arbitrario (Hexadecimal) para distinguirlos visualmente. Si no modificamos el badge, estos roles personalizados no podrán tener un color propio o romperán el diseño.

**Decisión de arquitectura:** Hacer el componente "híbrido". Mantendrá su comportamiento actual basado en `variant` para los colores del sistema (aprovechando las clases de Tailwind), pero aceptará un nuevo prop `hex` para aplicar estilos en línea (inline styles) calculados dinámicamente. Esto preserva la estética del sistema de diseño (fondo semitransparente + borde/texto sólido) mientras otorga flexibilidad total.

- [x] Modificar la clase del componente `app/View/Components/Ui/Badge.php`:
  - Agregar la propiedad pública `public ?string $hex;`.
  - Actualizar el constructor para recibir `$hex` (por defecto `null`).
  - Crear un método `getCustomStyles(): string` que genere la regla CSS en línea si `$hex` está presente:
    - Fondo: Color hexadecimal + `1a` (10% de opacidad).
    - Texto: Color hexadecimal base.
    - Borde: Color hexadecimal + `33` (20% de opacidad).

```php
public function getCustomStyles(): string
{
    if (!$this->hex) return '';
    return "background-color: {$this->hex}1a; color: {$this->hex}; border-color: {$this->hex}33;";
}
```

- [x] Modificar la plantilla del componente `resources/views/components/ui/badge.blade.php`:
  - Aplicar el método `getCustomStyles()` al atributo `style` del contenedor principal.
  - Actualizar la lógica del punto indicador (`dot`) para que también reciba el color hexadecimal dinámico si existe.

```blade
<div {{ $attributes->merge(['class' => $classes, 'style' => $getCustomStyles()]) }}>
    @if($dot)
        <span class="w-2 h-2 rounded-full" 
              style="{{ $hex ? "background-color: $hex" : "" }}"></span>
    @endif
    {{ $slot }}
</div>
```

- [x] Actualizar la documentación del componente (`docs/components/badge.md` o similar) para reflejar el uso del nuevo prop `hex`.
  - Ejemplo de uso a documentar: `<x-ui.badge hex="#FF5733">Coordinador</x-ui.badge>`
  - Laboratorio de pruebas (Demos) actualizar ejemplos de  `resources/views/examples/badge-components-demo.blade.php`

- [x] Modificar la migración de la tabla `roles` (preparación para 5.3):
  - Agregar la columna `color` (string, nullable) para almacenar el valor hexadecimal que elegirá el usuario al crear un rol.
  - Asegurar que `SchoolRoleService::seedDefaultRoles()` asigne colores por defecto a los roles del sistema (ej. `#4F46E5` para `School Principal`).
  - Actualizar `RoleOwnerSeeder` y `RoleAcademicSeeder` para que lo roles por defecto se creen con colores

### 5.2.2 — Refactorización del Componente Button (`x-ui.button`)

**Motivación:** Con la llegada de roles con colores personalizados (5.3) y la necesidad de botones-enlace
semánticamente correctos, el componente necesita las mismas capacidades que el Badge (hex + contraste)
más polimorfismo de tag y soporte de carga nativo.

- [x] **Clase PHP `app/View/Components/Ui/Button.php`:**
  - Nuevo prop `hex: ?string` — color hexadecimal arbitrario
  - Nuevo prop `href: ?string` — si presente, tag dinámico `<a>` en lugar de `<button>`
  - Nuevo tipo `ghost` — sin fondo ni borde base, hover semitransparente (toolbar/acciones discretas)
  - Método `tag(): string` — devuelve `'a'` si hay href, `'button'` si no
  - Método `isLightHex(string $hex): bool` — luminancia YIQ (W3C) para contraste automático
  - Método `hexStyles(): string` — genera `style` inline para fondo/texto/borde según `type`:
    - `solid`: fondo sólido + texto calculado por `isLightHex()` (`#ffffff` vs `#1e293b`)
    - `outline`: fondo `hex + 1a` (10% opacidad), texto y borde `hex + 33` (20% opacidad)
  - Variantes del mapa `$variants` expandidas con clave `'ghost'` para todas las variantes
  - Cuando `$hex` está presente, las clases de variante se omiten — los estilos van 100% inline

- [x] **Vista `resources/views/components/ui/button.blade.php`:**
  - Tag dinámico: `<{{ $tag }}>` / `</{{ $tag }}>` en lugar de `<button>` hardcodeado
  - `href="{{ $href }}"` aplicado si tag es `'a'`
  - `wire:loading.class="opacity-60 pointer-events-none"` integrado via `$attributes->merge()` — aplica a todos los botones sin configuración adicional
  - `aria-label` inferido automáticamente en modo icono (stripping del prefijo `heroicon-s-`/`heroicon-o-`) — sobreescribible con el atributo explícito
  - `aria-hidden="true"` en todos los iconos internos
  - `$hexStyle` aplicado al atributo `style` via merge

- [x] **Demo actualizada `resources/views/examples/button-components-demo.blade.php`:**
  - Sección `03 · Ghost` — botones de toolbar sin fondo
  - Sección `06 · Hex + Contraste` — solid y outline con colores arbitrarios
  - Sección `07 · Tag Dinámico` — botones renderizados como `<a>`
  - Sección `08 · wire:loading` — patrón de carga con snippet de código

- [x] **Documentación `docs/ui/button.md`:**
  - Tabla de props actualizada con `hex` y `href`
  - Sección "Tipo ghost" con casos de uso
  - Sección "Contraste automático" con tabla YIQ de ejemplos
  - Sección "Tag dinámico" con comparación `<button>` vs `<a>`
  - Sección "Estado de carga" con patrón recomendado para Livewire

**Compatibilidad:** Todos los usos existentes del componente son compatibles — los nuevos props son
opcionales con defaults que reproducen el comportamiento anterior exacto.

---


### 5.3 — Gestión de Identidad de Roles (Híbrida)

**Objetivo:** Permitir la creación y edición de la identidad visual (nombre y color) de los roles, diferenciando entre plantillas globales y roles de escuela.

- [x] **Infraestructura de Modelo y Datos:**
    - [x] Crear modelo `App\Models\Role` extendiendo de Spatie.
    - [x] Implementar `SchoolScope` en el modelo con bypass automático para rutas `admin/*`.
    - [x] Actualizar `config/permission.php` para registrar el nuevo modelo.
    - [x] Crear migración para columnas `color` (hex) e `is_system` (boolean).
- [x] **Rutas y Seguridad:**
    - [x] Definir rutas en `routes/admin/roles.php` protegidas por `can:roles.manage` y `can:roles.inspect`.
    - [x] Definir rutas en `routes/app/roles.php` protegidas por `can:roles.view` y `can:roles.manage`.
    
- [x] **Filtros**
  - [x] `app/Filters/App/Roles/SearchFilter.php` — busca en `name`
  - [x] `app/Filters/App/Roles/RoleFilters.php`
  - [x] `app/Filters/App/Roles/TenantRoleFilters.php` - orquestador

  - [x] `app/Filters/Admin/Roles/SearchFilter.php` — busca en `name`
  - [x] `app/Filters/Admin/Roles/RoleFilters.php`
  - [x] `app/Filters/Admin/Roles/AdminRoleFilters.php` - orquestador

- [x] **Tabla config**
  - [x] `app/Tables/App/RoleTableConfig.php`:
    - `allColumns`: name, users_count (columna calculada, no exite en la tabla), is_system, created_at
    - `defaultDesktop`: name, users_count, is_system
    - `defaultMobile`: name, users_count

  - [x] `app/Tables/Admin/RoleTableConfig.php`:
    - `allColumns`: name, users_count (columna calculada, no exite en la tabla), is_system, created_at
    - `defaultDesktop`: name, users_count, is_system
    - `defaultMobile`: name, users_count

- [x] **Componentes de Lista (Index):**
    - [x] Crear `App\Livewire\Admin\Roles\RoleIndex` (Filtra `school_id IS NULL`).
    - [x] Crear `App\Livewire\App\Roles\RoleIndex` (Filtrado por `SchoolScope`).
    - [x] Implementar acción `duplicate()`: clona identidad y color, pero no permisos (obliga a pasar por 5.4).
    - [x] Bloquear acciones de edición/borrado en la vista si `is_system` es true.
- [x] **Componente de Formulario (RoleForm):**
    - [x] Crear componente híbrido `App\Livewire\Shared\Roles\RoleForm`.
    - [x] Implementar detección de contexto (`isGlobal`) vía URL.
    - [x] **UI Premium:** Input de nombre + Color Picker con previsualización en vivo mediante badge dinámico.
    - [x] Lógica de guardado: Asignar `school_id` según el contexto actual.
- **Vistas**
  - [x] `resources/views/livewire/admin/roles/roles-index.blade.php` — columnas `x-data-table.cell`, modales, `x-ui.page-header`
  - [x] `resources/views/livewire/app/roles/roles-index.blade.php` — columnas `x-data-table.cell`, modales, `x-ui.page-header`

---

### 5.4 — Matriz de Permisos Granular

**Objetivo:** Gestionar qué puede hacer cada rol mediante una interfaz de checks agrupados, protegiendo la integridad de los roles base del sistema.

- [x] **Rutas de Acceso:**
    - [x] Crear ruta `/admin/roles/{role}/permissions`.
    - [x] Crear ruta `/app/roles/{role}/permissions`.
- [x] **Componente Livewire (`RolePermissions`):**
    - [x] Cargar permisos del rol y agrupar por `PermissionGroup`.
    - [x] **Filtro de Contexto:** Mostrar solo grupos `global` en admin y `tenant` en app.
    - [x] Implementar método `toggle()` con protección `is_system`.
    - [x] Asegurar uso de `setPermissionsTeamId($schoolId)` antes de sincronizar con la DB.
- [x] **Vista de Matriz:**
    - [x] Mostrar Header con nombre del rol y su badge de color real.
    - [x] Renderizar grupos de permisos con títulos y descripciones traducidas (`trans_permission`).
    - [x] Implementar banner de advertencia "Solo Lectura" si el rol es de sistema.
- [x] **Sistema de Traducciones:**
    - [x] Poblar `lang/es/permissions.php` con etiquetas y descripciones para cada permiso.
    - [x] Poblar `lang/es/permission_groups.php` para los nombres de los contenedores.

- [x] Corregit vista de `resources/views/components/ui/forms/toggle.blade.php` para que se maneje mejor con livewire

---


### +5.0 — Prerequisito: Roles de sistema por escuela

**Problema:** Los roles base (`School Principal`, `Teacher`, `Secretary`) se crean sin
`school_id` en el seeder. Con Spatie Teams, `UserIndex` busca `Role::where('school_id', $id)`
y no los encuentra. Además, el Service fallaba al intentar buscar las plantillas globales si ya existía un contexto de equipo activo.

**Decisión de arquitectura:** Clonar roles de sistema por escuela al completar el wizard.
Cada escuela tiene sus propias instancias de los roles base. Esto permite personalizar
permisos por tenant independientemente en la matriz de permisos (Fase 5.3).

- [x] **Refactor de Especificidad en `app/Services/School/SchoolRoleService.php`:**
  - `seedDefaultRoles(School $school): void` — Implementación de "Fase de Lectura Limpia": Se fuerza `setPermissionsTeamId(null)` y se usa `withoutGlobalScope(SchoolScope::class)` para garantizar que las plantillas globales sean visibles independientemente del estado de la sesión.
  - Optimización de memoria: Se cargan los roles globales y sus permisos en una sola consulta (`whereIn`) antes de iniciar el bucle de clonación.
  - `clonePermissions(Role $source, Role $target): void` — Copia exacta de la colección de permisos pre-cargados al nuevo rol del tenant.

- [x] **Actualización de flujo en `app/Actions/Tenant/CompleteOnboardingAction.php`:**
  - **Orden de ejecución crítico:** 1. `School::create(...)` 
    2. `$this->roleService->seedDefaultRoles($school)` (Crea los roles en la tabla `roles` con el `school_id`).
    3. `$this->createPrincipal->execute(...)` (Ahora `assignRole` encuentra el rol porque ya existe para esa escuela).
  - Se agrega `setPermissionsTeamId(null)` al final del closure de la transacción para limpiar el estado global del proceso.

- [x] **Actualización de flujo en `app/Actions/Tenant/CompleteTenantOnboardingAction.php`:**
  - Reorganización de lógica: Se inyecta la semilla de roles antes de cualquier interacción con el usuario principal.
  - Reseteo de contexto post-asignación: Se asegura que `setPermissionsTeamId(null)` ocurra inmediatamente después de `assignRole('School Principal')` para evitar que los listeners del evento `SchoolConfigured` hereden un scope de equipo bloqueado.

- [x] **Implementación de `app/Models/Scopes/SchoolScope.php`:**
  - Filtro automático por `school_id` o `session('impersonated_school_id')` para soporte técnico.
  - Aplicación selectiva en el modelo `Role`: El scope se activa para el panel de escuela pero se auto-desactiva si la ruta comienza por `admin/*`, permitiendo la gestión global desde el panel de control central.

- [x] **Actualización de `RoleAcademicSeeder`:**
  - Registro del rol `Staff` como plantilla global. Los roles plantilla permanecen con `school_id = null` para servir de base inmutable.

- [x] **Registro en `ModuleRegistry` (`app/Support/ModuleRegistry.php`):**
  - Se habilita el acceso al módulo de Roles en el panel de configuración, vinculándolo a la política de acceso de la escuela.

---

### 5.5 — Refactor de Servicios y Seeders

- [x] **Sincronización de Identidad Visual:**
    - `SchoolRoleService` ahora no solo clona permisos, sino también el atributo `color` y el flag `is_system` desde la plantilla global para asegurar consistencia visual en badges y gráficos desde el momento de la creación.
- [x] **Robustez en Modelos:**
    - El modelo `Role` propio extiende la funcionalidad de Spatie para soportar `fillable` personalizado (`color`, `is_system`, `school_id`) y el `booted()` con bypass de scope para administración.
---

### 5.6 — Perfil como modal

- [x] Crear `app/Livewire/Shared/ProfileModal.php` — misma lógica de `Profile` pero como modal
- [x] `resources/views/livewire/shared/profile-modal.blade.php` — modal `max-w-2xl` con tabs
- [x] Embeber `@livewire('shared.profile-modal')` en `layouts/app.blade.php` y `layouts/app-module.blade.php`
- [x] Actualizar dropdown del navbar — botón "Mi Perfil" despacha `open-profile-modal`
- [x] Mantener rutas `/app/profile` y `/admin/profile` como fallback

---

### 5.7 — Pendientes globales de la fase

- [x] Ejecutar `PermissionGroupSeeder` y `PermissionSeeder` en `DatabaseSeeder`
- [x] Verificar que el wizard llama `SchoolRoleService::seedDefaultRoles()` al completarse
- [x] Toggle de cambio de tema obsoleto quitado de `install.blade.php` y `wizard.blade.php`
- [x] Actualizar `ModuleRegistry` — agregar link "Roles" a la entrada `administracion`
- [x] Documentar `SchoolRoleService` en `docs/architecture/roles-permissions.md`
- [x] Documentar el patrón de traducciones en `docs/architecture/roles-permissions.md`
- [x] Documentar la separación de contextos (`global` vs `tenant`) en `docs/architecture/roles-permissions.md`:
  - Explicar que la matriz del admin (Fase 9) filtra por `context = 'global'`
  - Explicar que la matriz del director (5.4) filtra por `context = 'tenant'`
  - Justificar por qué este enfoque evita filtros adicionales y mantiene la coherencia en la UI
- [x] Actualizar `tailwind.config.js` para tener un tema osucro más moderno y usando toalidades grises
- [x] Actualizar `ui/toast.blade.php` `layouts/app-module`, `module-toolbar.blade.php`, `navbar.blade.php`, `app.blade.php`, `modal.blade.php` para adaptar al nuevo tema oscuro.
- [x] Usar el middleware de con can para las rutas de `admin/user.php` y `app/user.php`

---

## Fase 6 — Auditoría con Spatie Activity Log **HACER PARA FUTURO**
**Rama:** `feature/activity-log`

### Instalación y configuración

- [ ] Instalar `spatie/laravel-activitylog`
- [ ] Publicar y ejecutar migración de `activity_log`
- [ ] Configurar `log_name` por contexto: `auth`, `users`, `school`, `roles`, `settings`

### Eventos auditados

- [ ] Login / Logout / Failed Login (via observer o listener)
- [ ] Creación y actualización de usuario
- [ ] Asignación y remoción de roles
- [ ] Actualización de datos del centro
- [ ] Cambio de plan
- [ ] Invitación enviada y aceptada

### Vista de auditoría

- [ ] Ruta: `GET /app/audit-log` → componente Livewire `app/AuditLog`
- [ ] Columnas: fecha, usuario, evento, modelo afectado, descripción
- [ ] Filtros: usuario, rango de fechas, tipo de evento
- [ ] Solo visible para `School Principal` y roles con permiso `audit.view`

### Limpieza

- [ ] Comando `orvian:cleanup-activity`:
  ```php
  Activity::where('created_at', '<', now()->subDays(90))->delete();
  ```
- [ ] Programar en `routes/console.php` con `Schedule::command(...)->weekly()`

---

## Fase 7 — Seguridad y Authentication Logs **HACER PARA FUTURO**
**Rama:** `feature/auth-security`

### Authentication Log

- [ ] Instalar `rappasoft/laravel-authentication-log`
- [ ] Publicar migración y ejecutar
- [ ] Configurar notificación de nuevo dispositivo (email) — opcional pero recomendado
- [ ] Registra: IP, user agent, ciudad (si se configura GeoIP), fecha, resultado (éxito/fallo)

### Vista de sesiones y accesos

- [ ] Ruta: `GET /app/profile/security` (tab dentro del perfil)
- [ ] Tabla: últimos 20 accesos con IP, dispositivo, fecha, resultado
- [ ] Vista admin global: `GET /admin/security/logins` con filtros por usuario y escuela

### Limpieza

- [ ] Comando `orvian:cleanup-authlogs`:
  ```php
  AuthenticationLog::where('login_at', '<', now()->subDays(60))->delete();
  ```
- [ ] Programar en `routes/console.php` con `Schedule::command(...)->weekly()`

### Throttling

- [ ] Verificar configuración de `throttle:login` en rutas de autenticación
- [ ] Confirmar que Livewire maneja correctamente el rate limiting en `RegisterInstall` y `RegisterUser`

---

## Fase 8 — Observabilidad
**Rama:** `feature/observability`

### Laravel Pulse

- [x] Instalar `laravel/pulse`
- [x] Publicar migración y ejecutar
- [x] Ruta: `GET /admin/pulse` — protegida con middleware `admin.global`
- [x] Configurar recorders activos: `Servers`, `SlowQueries`, `SlowRequests`, `Exceptions`, `Queues`, `Cache`
- [x] Agregar tarjeta de usuarios activos

### Log Viewer

- [x] Instalar `opcodesio/log-viewer`
- [x] Ruta: `GET /admin/logs` — protegida con middleware `admin.global`
- [x] Configurar acceso solo para `Owner` y `TechnicalSupport`

- [x] Actualizar componete `item.blade.php` para que meneje enlaces externos (con `target="_blank"`)
- [x] Agregar enlace en sidebar para Logs del Sistema

---


## Fase 9 — Administración Global (Owner)
**Rama:** `feature/global-admin`

## 9.1 — Dashboard SaaS (Métricas Operativas)
**Objetivo:** Visualización del estado de salud del negocio mediante gráficos interactivos y métricas en tiempo real.

- [x] **Infraestructura de Gráficos:**
  - [x] Instalación de `ApexCharts` vía npm.
  - [x] Creación de `resources/js/charts-helper.js` para estandarizar el look & feel (Dark Mode compatible).
  - [x] Registro global en `app.js` y configuración de `wire:ignore` para persistencia de charts en navegación Livewire.

- [x] **Componente de UI: `x-admin.stat-card`**
  - [x] Diseño con micro-interacciones (hover elevation y scale-up de iconos).
  - [x] Sistema de gradientes "Ghost" para profundidad visual.
  - [x] Soporte dinámico para iconos de Heroicons y variantes de color semánticas.

- [x] **Lógica de Negocio (Backend):**
  - [x] Implementación de `StatsOverview` Livewire component.
  - [x] **Cálculo de Ingreso Mensual (MRR):** Join dinámico entre `schools` y `plans` filtrando por centros activos.
  - [x] **Eager Loading optimizado:** Carga de relación `users` en la tabla de registros para evitar el problema N+1.
  - [x] **Modelo `School`:** Definición de relación `hasMany(User)` para trazabilidad de directores y responsables.

- [x] **Visualización de Datos (Frontend):**
  - [x] **Métricas rápidas:** Escuelas (Activas/Inactivas), Usuarios Totales e Ingresos.
  - [x] **Gráfico de Área:** Crecimiento de registros en los últimos 30 días con gradiente de marca (`orvian-orange`).
  - [x] **Gráfico de Donut:** Distribución de mercado por tipo de plan (Básico, Pro, Premium).
  - [x] **Tabla de Actividad Reciente:** Refactorización del listado de "Stubs" por un log de "Últimos Registros" con detección de Director y estado de configuración vía `x-ui.badge`.

- [x] **Rutas y Acceso:**
  - [x] Actualización de `routes/admin/core.php` para definir el Dashboard como el punto de entrada principal del Hub Administrativo.


### 9.2 — Gestión de Centros (Tenants) — *Refactorización y Extensiones*
**Objetivo:** Refinar el control operativo vs. administrativo y mejorar la granularidad de los datos.

- [x] **Evolución del Modelo de Estados (Split Logic):**
    - [x] **Migración Unificada:** Creación de `2026_03_30_173750_add_status_and_location_to_schools_table.php` para incluir `is_suspended`, `latitude` y `longitude`.
    - [x] **Modelo `School`:** Implementación del campo `is_suspended` (Estado de Pago) independiente de `is_active` (Estado Operativo).
    - [x] **Relación Principal:** Definición de la relación en el modelo para traer al Director/a directamente.
- [x] **Infraestructura de Filtrado Avanzado:**
    - [x] **Filtros Geográficos:** Crear `RegionalEducationFilter.php` y `EducationalDistrictFilter.php` con lógica dependiente (Dinámica).
    - [x] **Filtro de Suspensión:** Crear `SuspendedFilter.php` utilizando el componente Toggle.
    - [x] **Traducción de Chips:** Actualizar `getActiveChips` en `Base/DataTable` y añadir método `formatFilterValue` para traducir IDs a nombres (ej: "Regional Metropolitana" en lugar de "1").
- [x] **Componentes de UI para DataTables:**
    - [x] **`filter-group.blade.php`:** Nuevo componente para agrupar filtros relacionados con funcionalidad colapsable.
    - [x] **`filter-toggle.blade.php`:** Actualización para soporte nativo de Livewire.
    - [x] **Documentación:** Actualizar `datatable-components.md` con las nuevas capacidades de grupos, toggles y formateo de valores.
- [x] **Refinamiento de la Tabla (`SchoolTableConfig`):**
    - [x] **Columna Director:** Inclusión del campo `principal` mapeado a la relación del modelo.
    - [x] **Diferenciación de Usuarios:** Implementar doble badge en la columna de usuarios: `Staff` (Total - Students) vs `Estudiantes` (Rol Student).
- [x] **Rutas y Navegación:**
    - [x] Crear `routes/admin/school.php` para encapsular `index` y `show`.
    - [x] Registro en sidebar condicionado al permiso `schools.view`.

### 9.2.5 — Seguridad Institucional y Geolocalización
**Objetivo:** Garantizar la continuidad del negocio y la precisión del catálogo geográfico.

- [x] **Capa de Seguridad (Middleware):**
    - [x] Crear middleware `EnsureSchoolIsActive`.
    - [x] **Lógica Dual:** Validar `is_active` e `is_suspended` de forma independiente.
    - [x] **Redirección Contextual:** Enrutar a `inactive.notice` o `suspended.notice` con mensajes personalizados según el fallo de estado.
    - [x] Registro global en `bootstrap/app.php`.
- [x] **Integración Geográfica (Google Maps):**
    - [x] **Configuración:** Registro de `google.maps_key` en `config/services.php`.
    - [x] **Entorno:** Actualización de `.env.example` y `.env` con la variable `Maps_API_KEY`.
    - [x] **Componente `x-ui.school-location-map`:** Componente Blade para renderizar mapas dinámicos basados en coordenadas.
- [x] **Correcciones de Datos (Core Fixes):**
    - [x] **`GradeSeeder.php`:** Ajuste de slugs por ciclo para resolver la colisión de grados en el Nivel Primario.

---

### 9.3 — Ecosistema Comercial (Planes & Módulos)
**Objetivo:** Controlar la oferta comercial mediante la definición de planes dinámicos y la asignación granular de funcionalidades (Features) sin necesidad de CRUDS innecesarios para el código interno.

#### 1. Infraestructura de Datos & Modelado
- [x] **Persistencia de Módulos (Features):**
    - [x] Ejecutar migración para tablas `features` y `feature_plan` (pivot).
    - [x] **Seeder de Sistema:** Registrar los módulos base (`attendance_qr`, `attendance_facial`, `academic_grades`, `academic_excel_import`, `classroom_internal`, `reports_advanced`).
- [x] **Evolución del Modelo `Plan`:**
    - [x] Agregar campos `bg_color`, `text_color`, `is_active`, `is_featured` y `const_name`.
    - [x] Implementar el Attribute `badgeStyle` para generar el CSS inline de los badges dinámicos.
    - [x] **Lógica de Unicidad:** Crear un Observer o método en el modelo para asegurar que solo exista **un** plan con `is_featured = true` mediante transacciones DB. Registrado en `AppServiceProvider`.

#### 2. Interfaz de Gestión (Admin Experience)
- [x] **Vista Principal: `Admin\Plans\PlanIndex`**
    - [x] Diseño de **Grid de Cards** (sustituyendo la tabla tradicional) que renderice los planes con sus colores institucionales.
    - [x] Implementar estados visuales "Featured" (marcos destacados o escalas sutiles).
    - [x] Badge de "Escuelas Activas" dentro de cada card para ver el impacto de cada plan.
- [x] **Panel de Acciones (Slide-over):**
    - [x] Componente Livewire para creación/edición rápida de planes desde la derecha.
    - [x] **Generación Automática:** Lógica para crear `slug` y `const_name` en tiempo real mientras se escribe el nombre.
    - [x] Previsualización en tiempo real del badge usando el componente `x-ui.badge` con los colores seleccionados.
- [x] **Matriz de Asignación de Features:**
    - [x] Nueva vista dedicada: `Admin\Plans\PlanFeatures`.
    - [x] **Interfaz de Toggles:** Organizar los features por `module` (Asistencia, Académico, etc.) con sus respectivos iconos estáticos.
    - [x] Implementar sincronización `sync()` en la tabla pivote al activar/desactivar módulos.
- [x] Preview en Tiempo Real:
    - [x] Integrar x-ui.plan-card en la vista de asignación para ver cambios visuales instantáneos (Livewire .live).

#### 3. Componentes Blade & UI Reutilizables
- [x] **`x-ui.plan-card`:** Componente polivalente para mostrar el plan. Debe aceptar un objeto `Plan` y adaptarse a grids de 3 o 4 columnas. Definir metodo `getIcon` en el modelo `Feature` para retornar el icono adecuado según el módulo.
- [x] Agergar is_featured al fillable del modelo Plan para facilitar su manejo en formularios.
- [x] **Lógica de Contraste:** Asegurar que si el `bg_color` es muy claro, el componente mantenga legibilidad (o forzar el `text_color` definido en el plan).

---

### 9.4 — Experiencia del Usuario (Hub de Aplicaciones)
**Objetivo:** Implementar un lanzador de aplicaciones dinámico que reaccione en tiempo real al plan contratado por la institución y al estado de desarrollo de los módulos.

- [x] **Componente Atómico: `x-ui.app-tile`**
    - [x] **Lógica de Estados Tri-estatales:**
        - **Activo:** Renderizado full color con micro-interacciones de elevación.
        - **Bloqueado (Plan):** Overlay de candado (`heroicon-s-lock-closed`) y desaturación visual (grayscale).
        - **Próximamente:** Badge superior "PRONTO" y opacidad reducida para módulos en roadmap.
    - [x] **Abstracción de Iconos:** Soporte para `x-ui.module-icon` (basado en nombre de módulo) o iconos dinámicos de Heroicons con sombras proyectadas (`box-shadow`) personalizadas.
    - [x] **Accesibilidad:** Implementación de `cursor-not-allowed` y anulación de puntero en estados inactivos.

- [x] **Dashboard de Usuario (The Hub):**
    - [x] **Motor de Saludo Dinámico:** Implementación de lógica PHP para variar el saludo y subtítulo según 7 franjas horarias (Mañana, Tarde, Noche, Madrugada).
    - [x] **Integración con Sistema de Planes:**
        - [x] Extracción de `features` activas del Tenant mediante `pluck('slug')`.
        - [x] Mapeo de Tiles a Slugs de la base de datos (ej: `attendance_qr`, `academic_grades`, `reports_advanced`).
    - [x] **Arquitectura de Módulos Mixtos:**
        - **Módulos Core:** (Administración, Horarios, Web) configurados como siempre activos o independientes del plan comercial.
        - **Módulos Premium:** Validación estricta contra la tabla pivot `feature_plan`.
    - [x] **Sección de Accesos Recientes:** Placeholder diseñado con estética *Premium Dark* para futura implementación de historial de navegación del usuario.

- [x] **Optimización Visual:**
    - [x] Uso de `tile-animate` con delays escalonados (`animation-delay`) para entrada fluida de los componentes.
    - [x] Fondo con `dot-pattern` de baja opacidad para añadir textura sin comprometer la legibilidad "Corporate".


### Variables de Diseño para Gráficos (Paleta ORVIAN)
Para que los gráficos no desentonen con tu `tailwind.config.js`, usaremos estos valores hexadecimales en los componentes de Livewire:

| Concepto | Color Tailwind | Hexadecimal |
| :--- | :--- | :--- |
| **Primario** | `orvian-blue` | `#13294c` |
| **Acción** | `orvian-orange` | `#f78904` |
| **Éxito** | `state-success` | `#0ac083` |
| **Fondo Card (Dark)** | `dark-card` | `#111113` |

---

### [Pendientes Técnicos de Integración - Actualizado]
- [x] **Registrar Ruta**: En el archivo `layouts/sidebaer.blade.php`, agregar la ruta de planes.
- [x] **Middleware `CheckSchoolStatus`:** (Marcado como completado bajo el nombre `EnsureSchoolIsActive`).
- [x] **Vista de Avisos:** Diseñar las vistas `notices/suspended.blade.php` y `notices/inactive.blade.php` que consume el middleware.
- [x] **Validación de Mapas:** Implementar fallback visual en `school-location-map` para cuando las coordenadas sean `null`.
- [x] **Uso de `ui.card-plan` en wizard**: Acutalizar paso `resources/views/livewire/tenant/wizard/steps/_step-4-plan.blade.php` para mostrar los planes con su diseño actualizado.



---

## Fase 9.5 — Hardening & UX Refinement
**Rama:** `feature/hardening-refinement`

**Objetivo:** Refinar la experiencia de usuario (UX) en procesos críticos como el Wizard de configuración y la gestión de años escolares, además de estandarizar la visualización de datos en las tablas administrativas del Owner.

### 9.5.1 — Correcciones en Wizard (Onboarding)
**Objetivo:** Ajustar la visualización de la estructura académica para reflejar fielmente el sistema dominicano (2 ciclos de 3 años).

- [x] **Refactorización de la Vista de Ciclos:** `_step-3-academic.blade.php`
    - [x] Crear componente `x-wizard.level-card` para representar cada nivel (1ro, 2do, 3ro) con su respectivo ciclo.
    - [x] Modificar el grid del Wizard para agrupar los cursos en bloques de tres (**1ro, 2do, 3ro**).
    - [x] Implementar separadores visuales por Ciclo (Primer Ciclo / Segundo Ciclo) para evitar la lista lineal de 6 cursos.
    - [x] Asegurar que el estado de selección de Livewire se mantenga vinculado correctamente a pesar del cambio visual en la Blade.

### 9.5.3 — Gestión de Años Escolares (Reglas de Negocio)
**Objetivo:** Blindar la creación de periodos académicos mediante la automatización del ciclo y validaciones de rango de calendario.

- [x] **Automatización del Período:**
    - [x] Sustituir el select manual por un componente `x-ui.forms.input` en estado `readonly` y `disabled`.
    - [x] Implementar lógica de cálculo dinámico: Si el mes actual es $\ge$ Agosto, el sistema asigna el año actual como inicio (Ej: 2026-2027); si es menor, toma el año anterior.
    - [x] Mostrar el año escolar como un dato informativo para evitar que el usuario intente crear escuelas en períodos que no corresponden.

- [x] **Validación y Restricción de Fechas:**
    - [x] **Restricción en UI (HTML5):** Aplicar atributos `min` y `max` en los inputs de fecha para que el calendario del navegador solo permita seleccionar días dentro del año escolar calculado (Agosto año-inicio a Julio año-fin).
    - [x] **Validación de Backend (Server-side):** - [x] Implementar *Closures* de validación en el `BaseSchoolWizard` para verificar que `start_date` y `end_date` no hayan sido manipuladas fuera del rango permitido.
        - [x] Forzar el `year_name` en el `academicPayload` desde el backend para ignorar cualquier valor enviado por el cliente.
    - [x] **Feedback de Error:** Configurar mensajes específicos: *"La fecha de inicio debe estar dentro del año escolar (YYYY-MM-DD a YYYY-MM-DD)"*.


### 9.5.4 — Navegación de Administración (Owner Experience)
**Objetivo:** Garantizar que el administrador global nunca quede atrapado en una vista profunda.

- [x] **Botones de Retorno:**
    - [x] En la vista `admin.setup` en el partial `_intro.blade.php` (Wizard de creacion), integrar un botón de "Volver" en el header que apunte a `route('admin.schools.index')`. Pero solo visible para usuarios con school_id = null (Owner).

- [x] **UI ROLES**
    - [x] Implementar en el paso 5 `{{ auth()->user()->getRoleNames()->first() ?? 'Usuario' }}` para mostrar el rol del usuario en la interfaz, reforzando la conciencia de su nivel de acceso.

---
## Fase 10 — Configuración del Centro (School Identity)
**Rama:** `feature/school-settings`

**Objetivo:** Implementar el centro de mando institucional donde el director (o el owner) gestione la identidad, ubicación geográfica y parámetros operativos del plantel.

### 10.1 — Infraestructura de Datos (Geografía y Multimedia)
**Objetivo:** Preparar la base de datos para la localización precisa y almacenamiento de marca.

- [x] **Migración de Tabla `schools`:**
    - [x] Agregar `logo_path` (string, nullable) para el almacenamiento en disco.
    - [x] Agregar `province_id` (FK a `provinces`) para completar la geografía política.
- [x] **Actualización de Modelo `School`:**
    - [x] Agregar campos al `$fillable` (incluyendo `province_id`).
    - [x] Definir relación `belongsTo` con `Province`.
    - [x] Implementar helper `activeYear()` que retorne la relación con el año escolar activo o el último creado por defecto.
- [x] **Actualizar wirzard**
    - [x] Incluir `province_id` en `CompleteOnboardingAction.php`, `CompleteTenantOnboardingAction.php` y `BaseSchoolWizard.php` .

---

### 10.2 — Componentes de Identidad: `x-ui.school-logo` y `x-ui.loading`
**Objetivo:** Crear un sistema de branding institucional dinámico con feedback visual en tiempo real.

- [x] **Infraestructura de Feedback (`x-ui.loading`):**
    - [x] Crear componente versátil basado en `border-current` para compatibilidad con temas.
    - [x] Implementar variantes de tamaño (`xs` a `xl`) mediante CSS de Tailwind.
- [x] **Componente de Identidad (`x-ui.school-logo`):**
    - [x] **Lógica de Visualización:** Adaptar `UserAvatarService` para iniciales y colores institucionales.
    - [x] **Soporte de Tamaños:** Escalas desde `xs` (tablas) hasta `2xl` (headers de configuración).
    - [x] **Modo Edición (Livewire Integration):**
        - [x] Implementar propiedad `uploadModel` para activar overlay de edición.
        - [x] Añadir efecto *hover* con desenfoque (`backdrop-blur`) e icono de cámara.
        - [x] Integrar `x-ui.loading` centrado mediante `absolute inset-0` para procesos de carga.
- [x] **Refactorización y Gestión en `SchoolShow`:**
    - [x] Implementar trait `WithFileUploads` en el componente Livewire.
    - [x] Crear método `updatedNewLogo()` para validación, limpieza de disco (Storage) y actualización de DB.
    - [x] Actualizar header en `school-show.blade.php` con el nuevo logo editable en tamaño `xl`.
- [x] **Consistencia en el Ecosistema:**
    - [x] Sustituir placeholders en `admin.schools.index` (listado global) usando el tamaño `sm` (solo lectura).

---

### 10.3 — Configuración Institucional (Backend Livewire)
**Objetivo:** Crear el motor de edición `app/Livewire/App/Settings/SchoolSettings`.

- [x] **Gestión de Archivos:**
    - [x] Implementar `WithFileUploads` para el manejo del logo.
    - [x] Lógica de limpieza: Eliminar el logo anterior del storage al subir uno nuevo para evitar basura en el disco `public`.
- [x] **Validación de Reglas Dominicanas:**
    - [x] SIGERD: Único y requerido (formato 8 dígitos).
    - [x] Teléfono: Validación de formato DR.
    - [x] Geografía en cascada: Al cambiar Regional → Filtrar Distritos; al cambiar Provincia → Filtrar Municipios.
- [x] **Persistencia:**
    - [x] Método `save()` con feedback mediante `Notify` o `Toast`.

### 10.4 — Interfaz de Configuración (Frontend)
**Objetivo:** Diseñar una vista unitaria de alto nivel con estética **Ultra-Deep Dark**.

- [x] **Layout de Secciones:**
    - [x] **Branding:** Área de Dropzone para logo con preview circular y botón de eliminar.
    - [x] **Información Base:** Grid de inputs (`x-ui.forms.input`) para Nombre, SIGERD y Teléfono.
    - [x] **Gobernanza:** Selects para Régimen (Público, etc.) y Modalidad.
    - [x] **Geografía Educativa:** Bloque para Regional y Distrito Educativo.
    - [x] **Geografía Territorial:** Bloque para Provincia, Municipio y Texto de dirección.
- [x] **Seguridad de Acción:**
    - [x] Botón de guardado con `wire:confirm` usando el modal de Breeze/Orvian: *"¿Estás seguro de actualizar la información institucional? Estos cambios afectarán los reportes oficiales."*

### 10.5 — Control de Operaciones
**Objetivo:** Visualización de parámetros de ciclo sin riesgo de edición accidental.

- [x] **Año Escolar:** Mostrar como `readonly` el año activo actual, informando al usuario que para cambiar de ciclo debe realizar el proceso de "Cierre de Año" (Fase futura).

### Extra
- [x] Registrar ruta `/app/settings/school` en `routes/app/school.php`.
- [x] Actualizar documentación de toast para que los ejemplos con dispath sean adecuados a cómo realmetne se usan.
- [x] Agregar ruta a `config/modules.php` y `app/dashboard.blade.php` para acceso desde el dashboard. 

---

## Pendientes Globales de v0.3.0

- [x] Corregir layout `layouts/app.blade.php` para que los módulos y sus vistas se adapten al nuevo navbar con y sin `x-app.module-toolbar`
- [x] Actualizar `x-ui.avatar` para incluir el indicador de `status`
- [x] Registrar listener del evento `Illuminate\Auth\Events\Login` para rellenar `last_login_at` y setear `status = online`
- [x] Registrar listener del evento `Illuminate\Auth\Events\Logout` para setear `status = offline`
- [x] Agregar `<x-ui.theme-init />` al `<head>` de todos los layouts antes del CSS
- [x] Documentar `x-app.module-toolbar` y `x-app.navbar` en `docs/`
- [x] Actualizar `CHANGELOG.md` al cerrar la rama
