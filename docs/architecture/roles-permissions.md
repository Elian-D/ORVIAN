# Roles y Permisos — Arquitectura

**Fase:** 5 — Roles y Permisos (UI)
**Rama:** `feature/roles-permissions-ui`

---

## Índice

1. [Modelo mental](#1-modelo-mental)
2. [SchoolRoleService](#2-schoolroleservice)
3. [Separación de contextos: `global` vs `tenant`](#3-separación-de-contextos-global-vs-tenant)
4. [Patrón de traducciones](#4-patrón-de-traducciones)
5. [Flujo completo de una escuela nueva](#5-flujo-completo-de-una-escuela-nueva)

---

## 1. Modelo mental

ORVIAN opera con dos tipos de roles:

**Roles globales** (`school_id = null`) — viven en el Admin Hub. Solo los tiene el equipo de ORVIAN: `Owner`, `TechnicalSupport`, `Administrative`. No pertenecen a ninguna escuela.

**Roles de escuela** (`school_id = {id}`) — cada centro educativo tiene sus propias instancias: `School Principal`, `Teacher`, `Secretary`, `Student`, `Staff`. Son independientes entre escuelas; modificar los permisos del Director de la Escuela A no afecta al Director de la Escuela B.

Spatie Permission tiene `teams = true` activado usando `school_id` como `team_id`. Eso significa que cuando se asigna un rol a un usuario, Spatie lo hace en el contexto del `school_id` activo — por eso siempre hay que llamar `setPermissionsTeamId($schoolId)` antes de operar con roles de escuela.

---

## 2. SchoolRoleService

**Ubicación:** `app/Services/School/SchoolRoleService.php`

### Por qué clonar roles en lugar de reutilizar los globales

Con Spatie Teams, las queries de roles filtran por `school_id`. Un rol con `school_id = null` no aparece cuando el contexto de equipo está activo. Si intentáramos asignar directamente los roles globales a usuarios de una escuela, Spatie no los encontraría en las búsquedas de autorización.

Clonar tiene además una ventaja de diseño a largo plazo: cada escuela puede personalizar los permisos de sus roles sin afectar al resto del sistema. El Director de la Escuela A puede tener acceso a Reportes; el de la Escuela B, no.

### Método `seedDefaultRoles(School $school)`

Se llama una sola vez al completar el wizard de configuración, desde las Actions:

- `CompleteOnboardingAction` (flujo Owner → `SchoolWizard`)
- `CompleteTenantOnboardingAction` (flujo Tenant → `TenantSetupWizard`)

**Debe ejecutarse antes de `event(SchoolConfigured)`**, porque el listener `AssignInitialRoles` (Borrado) intenta asignar el rol `School Principal` al director recién creado — y ese rol debe existir con el `school_id` correcto antes de que el listener lo busque.

**Estrategia de optimización:** el método hace una sola query para cargar todos los roles globales de referencia con sus permisos eager-loaded (`->with('permissions')`), y luego itera en memoria. Evita N+1 al crear los roles del tenant.

```
// Flujo interno de seedDefaultRoles()

1. setPermissionsTeamId(null)
   → Permite leer roles globales (school_id = null)

2. Role::whereNull('school_id')->whereIn('name', BASE_ROLES)->with('permissions')->get()
   → Pre-carga todos los roles de referencia y sus permisos en una query

3. setPermissionsTeamId($school->id)
   → Activa el scope del tenant para la fase de escritura

4. foreach BASE_ROLES:
   → Role::firstOrCreate(['name', 'guard_name', 'school_id'], ['color', 'is_system' => true])
   → Si wasRecentlyCreated: clonePermissions($globalRole, $tenantRole)

5. setPermissionsTeamId(null)
   → Resetea el scope para no contaminar operaciones posteriores
```

**`is_system = true`** marca los roles creados por este servicio como inmutables desde la UI. Los roles de sistema no pueden eliminarse ni renombrarse desde el panel de la escuela; solo se pueden editar sus permisos (ver sección de matriz en Fase 5.4).

**`color`** se hereda del rol global de referencia, garantizando consistencia visual entre escuelas para los roles base. Si el rol global no existe (escenario de datos corruptos), se aplica el fallback `#64748B` (slate-500).

### Método `clonePermissions(Role $source, Role $target)`

Copia los permisos del rol origen al destino usando `syncPermissions`. Es idempotente: llamarlo varias veces sobre el mismo par produce el mismo resultado. Se puede usar también para **resetear** los permisos de un rol de escuela a sus valores de referencia si un administrador quiere volver a los defaults.

```php
// Resetear permisos de un rol a sus valores de sistema
$globalRole = Role::whereNull('school_id')->where('name', 'Teacher')->first();
$tenantRole = Role::where('school_id', $school->id)->where('name', 'Teacher')->first();

$roleService->clonePermissions($globalRole, $tenantRole);
```

---

## 3. Separación de contextos: `global` vs `tenant`

### El problema que resuelve

Los permisos de ORVIAN operan en dos universos distintos:

- `global` — permisos del Admin Hub (`manage schools`, `access admin hub`, `view activity logs`, etc.). Solo relevantes para el equipo de ORVIAN.
- `tenant` — permisos del panel escuela (`users.view`, `roles.manage`, `settings.update`, etc.). Solo relevantes para los usuarios de un centro educativo.

Sin separación de contextos, la matriz de permisos de un Director incluiría ruido innecesario como `impersonate users` o `manage global users`, y la matriz del admin incluiría `mark attendance` o `upload grades`. Eso no es solo un problema de UX — también genera confusión sobre qué permisos son realmente aplicables en cada contexto.

### Implementación

La tabla `permission_groups` tiene un campo `context` (enum: `'global'`, `'tenant'`, default `'tenant'`). El modelo `PermissionGroup` expone dos scopes:

```php
PermissionGroup::tenant()->ordered()->get();  // Matriz del Director (Fase 5.4)
PermissionGroup::global()->ordered()->get();  // Matriz del Admin Hub (Fase 9)
```

Esto evita cualquier filtro adicional en las vistas — la query ya viene segmentada desde el modelo. No hay condiciones `if ($isAdmin)` en las vistas ni en los componentes Livewire para decidir qué grupos mostrar.

### Por qué un campo en la tabla y no una convención de nombres

Alternativa considerada: usar el prefijo del nombre del permiso (`global.` vs sin prefijo) para inferir el contexto. Se descartó porque:

1. Rompe los nombres técnicos de Spatie que ya existen en el sistema (`manage schools`, `access admin hub`).
2. Hace frágil el código: un permiso mal nombrado rompe la separación silenciosamente.
3. No permite reordenar grupos dentro de cada contexto de forma independiente.

Con `context` en la tabla, agregar un grupo nuevo con decenas de permisos es una línea en el seeder. El contexto es explícito, no inferido.

### Referencia cruzada por fase

| Fase | Contexto | Filtro aplicado |
|---|---|---|
| 5.4 — Matriz del Director | `tenant` | `PermissionGroup::tenant()` |
| 9.x — Matriz del Admin Hub | `global` | `PermissionGroup::global()` |

---

## 4. Patrón de traducciones

### Por qué las traducciones NO viven en la base de datos

Alternativa considerada: guardar `label` y `description` directamente en la tabla `permissions` o `permission_groups`. Se descartó porque:

- Los seeders se vuelven migraciones de contenido: cada corrección ortográfica requiere un nuevo seeder o una migración de datos.
- Los JOINs se complican para construir la matriz de permisos.
- Laravel ya tiene un sistema maduro para esto: los archivos `lang/`.

**Decisión:** los nombres técnicos de Spatie (`users.view`, `roles.manage`) son la fuente de verdad en BD. La capa de presentación traduce con `__()` usando el nombre técnico como clave.

### Archivos de traducción

**`lang/es/permissions.php`** — dos secciones:

- `groups` — nombres legibles de los grupos, keyed por `slug` de `PermissionGroup`
- `actions` — array por permiso con `name` y `description`


### Helper `trans_permission()`

**Ubicación:** `app/Helpers/PermissionHelper.php` (registrado en `composer.json` → `autoload.files`)

```php
function trans_permission(string $name, string $field = 'name'): string
{
    $key = "permissions.actions.{$name}.{$field}";
    $translated = __($key);

    // Fallback: si la clave no existe, devuelve el nombre técnico
    return $translated !== $key ? $translated : $name;
}
```

El fallback es importante durante el desarrollo: si se agrega un permiso nuevo al seeder antes de agregar su traducción, la UI muestra el nombre técnico en lugar de romperse o mostrar la clave de traducción cruda.

### Uso en vistas

```html
{{-- Nombre del grupo --}}
{{ __('permissions.groups.' . $group->slug) }}

{{-- Nombre y descripción del permiso --}}
{{ trans_permission($permission->name) }}
{{ trans_permission($permission->name, 'description') }}

```

### Convención de nombres técnicos

Los permisos de tenant siguen el patrón `{recurso}.{acción}` en inglés:

```
users.view  |  users.create  |  users.edit  |  users.delete
roles.view  |  roles.manage
settings.view  |  settings.update
```

Los permisos globales siguen el patrón `{verbo} {recurso}` heredado del seeder original:

```
manage schools  |  view schools
access admin hub  |  impersonate users
```

Cuando se agreguen permisos de módulos académicos en fases posteriores, deben seguir el patrón `{modulo}.{accion}` para mantener consistencia con las claves de traducción.

---

## 5. Flujo completo de una escuela nueva

```
Usuario completa el wizard
        │
        ▼
CompleteOnboardingAction / CompleteTenantOnboardingAction
        │
        ├─► School::create(...)            → Escuela creada con is_configured = true
        ├─► createPrincipal->execute(...)  → Director creado (sin rol aún)
        │
        ├─► SchoolRoleService::seedDefaultRoles($school)
        │       ├─ Lee roles globales (school_id = null) con permisos
        │       ├─ Crea School Principal, Teacher, Secretary, Student, Staff
        │       │  con school_id = $school->id, is_system = true, color heredado
        │       └─ Copia permisos de cada rol global al rol del tenant
        │
        └─► event(new SchoolConfigured($school, $academic))
                │
                ├─► SetupAcademicStructure    → Secciones y estructura académica
                ├─► CreateInitialAcademicYear → Año escolar con fechas del wizard

```