# Sistema de Tablas Adaptativas (DataTable Pattern)

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Livewire](https://img.shields.io/badge/Livewire-Compatible-orange)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-Compatible-blue)

El sistema de tablas de ORVIAN combina el **Pipeline Pattern** (filtros), el motor reactivo de **Livewire 3**, y componentes Blade especializados para ofrecer tablas con filtros, paginación personalizada, columnas configurables por dispositivo y carga asíncrona sin repetir código entre módulos.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [Guía de Implementación](#guía-de-implementación)
  - [1. Definir la Configuración de Columnas (TableConfig)](#1-definir-la-configuración-de-columnas-tableconfig)
  - [2. Crear el Componente Livewire](#2-crear-el-componente-livewire)
  - [3. Uso en Blade](#3-uso-en-blade)
- [Componentes de la Suite (x-data-table.*)](#componentes-de-la-suite-x-data-table)
- [Jerarquía Visual y Page Header](#jerarquía-visual-y-page-header)
- [Sistema de Paginación ORVIAN](#sistema-de-paginación-orvian)
- [Carga Asíncrona y Skeleton](#carga-asíncrona-y-skeleton)
- [Responsividad y Control de Columnas](#responsividad-y-control-de-columnas)
- [Eliminar N+1 con scopeWithIndexRelations](#eliminar-n1-con-scopewithindexrelations)
- [Componente Empty State](#componente-empty-state)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
├── Livewire/
│   └── Base/
│       └── DataTable.php                       # Clase abstracta base
├── Tables/
│   ├── Contracts/
│   │   └── TableConfig.php                     # Interfaz obligatoria
│   ├── Concerns/
│   │   └── HasResponsiveColumns.php            # Trait con cellClass()
│   └── [Modulo]/
│       └── [Modulo]TableConfig.php             # Configuración por módulo
resources/
└── views/
    └── components/
        ├── data-table/
        │   ├── base-table.blade.php            # Esqueleto visual
        │   ├── cell.blade.php                  # Celda inteligente
        │   ├── search.blade.php
        │   ├── per-page-selector.blade.php
        │   ├── filter-container.blade.php
        │   ├── filter-select.blade.php
        │   ├── filter-toggle.blade.php
        │   ├── filter-date-range.blade.php
        │   ├── filter-range.blade.php
        │   ├── filter-chips.blade.php
        │   └── column-selector.blade.php
        └── ui/
            ├── empty-state.blade.php
            ├── page-header.blade.php
            └── skeleton.blade.php
```

---

## Guía de Implementación

### 1. Definir la Configuración de Columnas (TableConfig)

Toda tabla requiere una clase `TableConfig` que implemente la interfaz `App\Tables\Contracts\TableConfig`. Esta clase centraliza columnas, visibilidad por dispositivo, labels de filtros y el helper de clases CSS.

```php
namespace App\Tables\Admin;

use App\Tables\Contracts\TableConfig;
use App\Tables\Concerns\HasResponsiveColumns;

class AdminUserTableConfig implements TableConfig
{
    use HasResponsiveColumns;  // Agrega cellClass() automáticamente

    /** Catálogo completo [clave => label legible] */
    public static function allColumns(): array
    {
        return [
            'name'          => 'Nombre',
            'email'         => 'Correo Electrónico',
            'role'          => 'Rol',
            'status'        => 'Estado',
            'last_login_at' => 'Último Acceso',
            'position'      => 'Cargo',
        ];
    }

    /** Columnas visibles por defecto en escritorio */
    public static function defaultDesktop(): array
    {
        return ['name', 'email', 'role', 'status', 'last_login_at'];
    }

    /** Columnas visibles por defecto en mobile */
    public static function defaultMobile(): array
    {
        return ['name', 'role'];
    }

    /**
     * Labels legibles para los chips de filtros activos.
     * Clave = nombre del campo en $filters del componente Livewire.
     */
    public static function filterLabels(): array
    {
        return [
            'search' => 'Búsqueda',
            'role'   => 'Rol',
            'status' => 'Estado',
        ];
    }
}
```

> [!NOTE]
> El trait `HasResponsiveColumns` provee `cellClass(string $column): string` que retorna las clases CSS correctas según si la columna es mobile-visible o no. Úsalo en las vistas para evitar repetir la lógica de `hidden md:table-cell`.

---

### 2. Crear el Componente Livewire

El componente hereda de `DataTable` e implementa solo lo específico del módulo: `getTableDefinition()`, `render()`, y los métodos de negocio (CRUD, etc.).

```php
namespace App\Livewire\Admin\Users;

use App\Filters\Admin\Users\AdminUserFilters;
use App\Livewire\Base\DataTable;
use App\Models\User;
use App\Tables\Admin\AdminUserTableConfig;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Usuarios del Sistema')]
#[Layout('components.admin')]
class UserIndex extends DataTable
{
    #[Url]
    public array $filters = [
        'search'  => '',
        'role'    => '',
        'status'  => '',
        'trashed' => '',
    ];

    protected function getTableDefinition(): string
    {
        return AdminUserTableConfig::class;
    }

    public function render()
    {
        $query = User::whereNull('school_id')
            ->withIndexRelations();  // ← SIEMPRE usar para evitar N+1

        $users = (new AdminUserFilters($this->filters))
            ->apply($query)
            ->orderBy('id')
            ->paginate($this->perPage);  // ← SIEMPRE $this->perPage, nunca literal

        return view('livewire.admin.users.index', [
            'users' => $users,
        ]);
    }
}
```

> [!IMPORTANT]
> Nunca uses `->paginate(10)` con un literal. Siempre `->paginate($this->perPage)` para que el selector de per-page del usuario funcione.

> [!TIP]
> `#[Lazy]` viene de la clase base `DataTable`. No necesitas redeclararlo en los hijos. Si en un módulo específico no quieres carga diferida, sobreescribe con `#[Lazy(enabled: false)]`.

---

### 3. Uso en Blade

La vista del módulo usa `x-data-table.base-table` y define únicamente las filas. La cabecera de la tabla, el panel de columnas, los chips de filtros y la paginación se gestionan automáticamente.

```html
{{-- Jerarquía: page-header fuera de la tabla --}}
<x-ui.page-header
    title="Usuarios del Sistema"
    description="Gestión de cuentas globales."
    :count="$users->total()"
    countLabel="usuarios"
>
    <x-slot:actions>
        <x-ui.button variant="primary" size="sm" iconLeft="heroicon-s-plus"
            wire:click="create">
            Nuevo Usuario
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-data-table.base-table
    :items="$users"
    :definition="\App\Tables\Admin\AdminUserTableConfig::class"
    :visibleColumns="$visibleColumns"
    :activeChips="$this->getActiveChips()"
    :hasFilters="count(array_filter($filters)) > 0"
>
    {{-- Filtros del módulo --}}
    <x-slot:filterSlot>
        <x-data-table.filter-container
            :activeCount="count(array_filter(array_diff_key($filters, ['trashed' => ''])))">
            <x-data-table.filter-select
                label="Rol"
                filterKey="role"
                :options="$roleOptions"
                placeholder="Todos los roles"
            />
            <x-data-table.filter-select
                label="Estado"
                filterKey="status"
                :options="['online' => 'En línea', 'offline' => 'Desconectado']"
            />
        </x-data-table.filter-container>
    </x-slot:filterSlot>

    {{-- Filas — el @forelse vive aquí --}}
    @forelse($users as $user)
        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors duration-150">

            {{-- Usando x-data-table.cell --}}
            <x-data-table.cell column="name" :visible="$visibleColumns">
                {{-- contenido de la celda --}}
            </x-data-table.cell>

            {{-- O usando el trait cellClass() directamente --}}
            @if(in_array('email', $visibleColumns))
                <td class="{{ \App\Tables\Admin\AdminUserTableConfig::cellClass('email') }}">
                    {{ $user->email }}
                </td>
            @endif

            <td class="px-4 py-3.5 text-right">
                {{-- acciones --}}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-12">
                <x-ui.empty-state variant="simple" title="Sin resultados"
                    description="No encontramos registros con los filtros aplicados." />
            </td>
        </tr>
    @endforelse

</x-data-table.base-table>
```

---

## Componentes de la Suite (x-data-table.*)

Ver `docs/architecture/datatable-components.md` para la documentación completa de props y uso de cada componente. Referencia rápida:

| Componente | Rol |
|---|---|
| `x-data-table.search` | Input de búsqueda con debounce y botón × |
| `x-data-table.per-page-selector` | Select pill para registros por página |
| `x-data-table.filter-container` | Dropdown/drawer que agrupa los filtros del módulo |
| `x-data-table.filter-select` | Select dentro del filter-container |
| `x-data-table.filter-toggle` | Toggle booleano dentro del filter-container |
| `x-data-table.filter-date-range` | Rango de fechas (desde/hasta) |
| `x-data-table.filter-range` | Rango numérico (mín/máx) con prefix/suffix |
| `x-data-table.filter-chips` | Chips de filtros activos con × individual |
| `x-data-table.column-selector` | Checkboxes reactivos para columnas visibles |
| `x-data-table.cell` | `<td>` condicional que se auto-oculta si la columna no está visible |

---

## Jerarquía Visual y Page Header

Las **acciones primarias** (Nuevo, Exportar) viven **fuera** de la tabla, en `x-ui.page-header`. Esto establece la jerarquía correcta: las acciones tienen mayor peso visual que los controles de filtrado.

```blade
<x-ui.page-header
    title="Nombre del Módulo"
    description="Descripción breve."
    :count="$items->total()"
    countLabel="registros"
>
    <x-slot:actions>
        <x-ui.button variant="primary" wire:click="create">Nuevo</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>
```

`base-table` no tiene slot de acciones. Todo lo que va dentro de la tabla son controles de filtrado y visualización.

---

## Sistema de Paginación ORVIAN

ORVIAN usa vistas de paginación propias en lugar de las de Laravel/Tailwind. Hay tres variantes:

| Vista | Uso recomendado |
|---|---|
| `pagination.orvian-compact` | **Default** — todos los DataTable. Botones numéricos compactos. |
| `pagination.orvian-full` | Módulos de alta densidad (auditoría, logs). Incluye "Ir a página". |
| `pagination.orvian-ledger` | Datasets masivos o mobile. Pill compacto con input. |

**Cómo funciona el default:** `DataTable` base declara `paginationView()` retornando `'pagination.orvian-compact'`. Livewire usa esto en lugar del view de Laravel. No necesitas hacer nada en los módulos hijos.

**Para usar una vista específica en un módulo:**
```blade
{{ $items->links('pagination.orvian-full') }}
```

**Para paginación fuera de Livewire** (controllers normales), `AppServiceProvider::boot()` ya registra `Paginator::defaultView('pagination.orvian-compact')`.

---

## Carga Asíncrona y Skeleton

`DataTable` tiene `#[Lazy]` y `placeholder()` en la clase base. Todos los módulos que hereden de ella cargan automáticamente en dos fases:

1. El navegador recibe el layout completo (navbar, sidebar, breadcrumbs) de inmediato.
2. El skeleton aparece donde irá la tabla.
3. Livewire ejecuta el `render()` en una segunda petición AJAX.
4. La tabla reemplaza el skeleton.

El número de filas del skeleton coincide con `$perPage` del componente.

**Para desactivar en un módulo específico:**
```php
#[Lazy(enabled: false)]
class MiComponente extends DataTable { ... }
```

Ver `docs/architecture/lazy-loading.md` para la documentación completa.

---

## Responsividad y Control de Columnas

El sistema maneja la visibilidad en tres niveles:

**Defaults por dispositivo** — `defaultDesktop()` define las columnas iniciales en escritorio. `defaultMobile()` define las columnas en mobile. Alpine detecta el viewport en `init()` del `column-selector` y llama `$wire.resetColumns(isMobile)` si es necesario corregir.

**Control del usuario** — El `column-selector` permite al usuario activar/desactivar columnas en runtime. Los checkboxes son reactivos via Alpine (`:checked="isVisible(key)"`) y nunca desincronización con el estado del servidor.

**Guard de mínimo** — El sistema nunca deja la tabla sin columnas. Si el usuario intenta quitar la última columna visible, se restauran automáticamente las columnas por defecto del dispositivo.

**Overflow controlado** — El contenedor usa `overflow-x-auto custom-scroll`. Si el usuario activa más columnas de las que caben, la tabla permite scroll horizontal sin romper el layout.

---

## Eliminar N+1 con scopeWithIndexRelations

Cada modelo que se use en una tabla **debe** definir `scopeWithIndexRelations()`. Este scope centraliza el eager loading necesario para la vista de listado.

```php
// En app/Models/User.php
public function scopeWithIndexRelations($query)
{
    return $query->with('roles');  // carga roles en 1 query adicional
}

// En el render() del DataTable
$query = User::whereNull('school_id')->withIndexRelations();
```

Sin este scope, Spatie ejecuta una query por fila para `getRoleNames()` → N+1. Con el scope: 1 query adicional independientemente del número de filas.

Ver `docs/architecture/n-plus-one.md` para el patrón completo y otros casos comunes.

---

## Componente Empty State

`x-ui.empty-state` se usa dentro del `@empty` del `@forelse`. No es automático — cada módulo lo declara en su vista.

```blade
@empty
    <tr>
        <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-12">
            <x-ui.empty-state
                variant="simple"
                title="Sin resultados"
                description="No encontramos registros que coincidan con los filtros aplicados."
            />
        </td>
    </tr>
```

---

## Notas Adicionales

- Nunca pases un literal a `paginate()`. Siempre `$this->perPage`.
- Nunca pongas acciones primarias (Nuevo, Exportar) dentro de `base-table`. Van en `x-ui.page-header`.
- Nunca omitas `->withIndexRelations()` si el modelo tiene relaciones que la vista consume.
- El `colspan` del empty state es siempre `count($visibleColumns) + 1` (la +1 es la columna de acciones).
- Para añadir una columna nueva: agrégala a `allColumns()`, inclúyela en `defaultDesktop()` si aplica, y añade el `<td>` o `<x-data-table.cell>` en la vista del módulo.