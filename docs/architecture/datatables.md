# Sistema de Tablas Adaptativas (DataTable Pattern)

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Livewire](https://img.shields.io/badge/Livewire-Compatible-orange)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-Compatible-blue)

El sistema de tablas de ORVIAN combina el **Pipeline Pattern** (filtros) con el motor de estado reactivo de **Livewire** para ofrecer tablas responsivas sin duplicar vistas ni escribir JavaScript adicional. La configuración de columnas vive en clases dedicadas, la visibilidad se adapta automáticamente según el dispositivo, y los estados vacíos se manejan con un componente dedicado.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [Guía de Implementación](#guía-de-implementación)
  - [1. Definir la Configuración de Columnas](#1-definir-la-configuración-de-columnas)
  - [2. Crear el Componente Livewire](#2-crear-el-componente-livewire)
  - [3. Uso en Blade](#3-uso-en-blade)
- [Componente Empty State](#componente-empty-state)
- [Responsividad y Control de Columnas](#responsividad-y-control-de-columnas)
- [Beneficios del Sistema](#beneficios-del-sistema)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
├── Livewire/
│   └── Base/
│       └── DataTable.php                   # Clase abstracta con la lógica compartida
└── Tables/
    └── [Modulo]/
        └── [Modulo]TableConfig.php         # Definición de columnas por módulo
resources/
└── views/
    └── components/
        ├── data-table/
        │   └── base-table.blade.php        # Esqueleto visual de la tabla
        └── ui/
            └── empty-state.blade.php       # Estado vacío reutilizable
```

---

## Guía de Implementación

### 1. Definir la Configuración de Columnas

En lugar de codificar las columnas directamente en el HTML, se definen en una clase de configuración dedicada. Esto centraliza el control de orden, etiquetas y visibilidad por dispositivo.

**Ejemplo:** `app/Tables/Tenant/SchoolTableConfig.php`

```php
namespace App\Tables\Tenant;

class SchoolTableConfig
{
    /**
     * Catálogo completo de columnas disponibles: [clave => etiqueta].
     */
    public static function allColumns(): array
    {
        return [
            'name'        => 'Institución',
            'sigerd_code' => 'Código SIGERD',
            'modalidad'   => 'Modalidad',
            'status'      => 'Estado',
        ];
    }

    /**
     * Columnas visibles por defecto en escritorio.
     */
    public static function defaultDesktop(): array
    {
        return ['name', 'sigerd_code', 'modalidad', 'status'];
    }

    /**
     * Columnas visibles por defecto en dispositivos móviles.
     */
    public static function defaultMobile(): array
    {
        return ['name', 'status'];
    }
}
```

> [!NOTE]
> Las claves del array (ej: `'name'`, `'status'`) son el identificador interno de cada columna. Deben mantenerse consistentes entre `allColumns()`, `defaultDesktop()`, `defaultMobile()` y la vista Blade.

---

### 2. Crear el Componente Livewire

El componente hereda de `DataTable` e integra el Pipeline de filtros. Debe implementar `getTableDefinition()` y el método `render()`. También expone `toggleColumn()` a través de la clase base para que el panel de ajustes de columnas funcione.

**Ejemplo:** `app/Livewire/Tenant/SchoolTable.php`

```php
namespace App\Livewire\Tenant;

use App\Filters\Tenant\School\SchoolFilters;
use App\Livewire\Base\DataTable;
use App\Models\School;
use App\Tables\Tenant\SchoolTableConfig;

class SchoolTable extends DataTable
{
    protected function getTableDefinition(): string
    {
        return SchoolTableConfig::class;
    }

    public function render()
    {
        $schools = (new SchoolFilters($this->filters))
            ->apply(School::query())
            ->paginate(10);

        return view('livewire.tenant.school-table', [
            'items' => $schools,
        ]);
    }
}
```

> [!TIP]
> `getTableDefinition()` devuelve el nombre de la clase de configuración como string (`::class`). La clase base `DataTable` usa esto para inicializar `$visibleColumns` con los valores correctos según el dispositivo al montar el componente, y para construir el panel de ajuste de columnas.

---

### 3. Uso en Blade

`x-data-table.base-table` gestiona automáticamente la cabecera, el panel de columnas, el estado vacío y la paginación. La vista solo necesita definir las filas del `$slot`.

```html
<x-data-table.base-table
    :items="$items"
    :definition="App\Tables\Tenant\SchoolTableConfig::class"
    :visibleColumns="$visibleColumns"
>
    {{-- Filtros en la barra superior --}}
    <x-slot name="filters">
        <input wire:model.live="filters.search" placeholder="Buscar...">
    </x-slot>

    {{-- Filas de datos --}}
    @foreach($items as $school)
        <tr>
            @if(in_array('name', $visibleColumns))
                <td class="{{ in_array('name', $definition::defaultMobile()) ? '' : 'hidden md:table-cell' }} px-6 py-4">
                    {{ $school->name }}
                </td>
            @endif

            @if(in_array('sigerd_code', $visibleColumns))
                <td class="hidden md:table-cell px-6 py-4">
                    {{ $school->sigerd_code }}
                </td>
            @endif

            {{-- Resto de columnas siguiendo el mismo patrón --}}

            <td class="px-6 py-4 text-right">
                {{-- Acciones por fila --}}
            </td>
        </tr>
    @endforeach
</x-data-table.base-table>
```

> [!IMPORTANT]
> Cada `<td>` debe verificar dos condiciones independientes: si la columna está en `$visibleColumns` (el usuario no la ocultó) y si corresponde a móvil o escritorio para aplicar `hidden md:table-cell`. Omitir cualquiera de las dos rompe el comportamiento responsivo.

El componente `base-table` incluye internamente lo siguiente cuando `$items` está vacío:

```html
@empty
    <tr>
        <td colspan="{{ count($visibleColumns) + 1 }}">
            <x-ui.empty-state
                variant="simple"
                title="Búsqueda sin resultados"
                description="No encontramos ningún registro que coincida con los filtros aplicados actualmente."
            />
        </td>
    </tr>
@endforelse
```

No es necesario manejar el estado vacío manualmente en la vista de cada módulo.

---

## Componente Empty State

`x-ui.empty-state` es un componente independiente que puede usarse tanto dentro del `base-table` (automáticamente) como en cualquier otra vista que requiera comunicar ausencia de datos.

### Props disponibles

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `icon` | `string` | `heroicon-o-circle-stack` | Heroicon para el área visual |
| `title` | `string` | `'No hay datos para mostrar'` | Título del estado vacío |
| `description` | `string` | `'Parece que aún no...'` | Subtexto descriptivo |
| `actionLabel` | `string\|null` | `null` | Texto del botón de acción (opcional) |
| `actionClick` | `string\|null` | `null` | Método Livewire para `wire:click` |
| `variant` | `string` | `'dashed'` | `dashed` (con borde) o `simple` (sin borde) |

### Variantes

**`dashed`** — Incluye un borde punteado `border-2 border-dashed` alrededor del contenido. Recomendado para estados vacíos de página completa, como cuando no hay registros en absoluto.

**`simple`** — Sin borde exterior. Recomendado para usar dentro de celdas de tabla (`@empty` del `@forelse`), donde el borde propio de la tabla ya delimita el área.

### Ejemplos de uso

**Estado vacío de página, con botón de creación:**
```html
<x-ui.empty-state
    icon="heroicon-o-academic-cap"
    title="No hay escuelas registradas"
    description="Comienza registrando el primer centro educativo del sistema."
    actionLabel="Nueva Escuela"
    actionClick="openCreateModal"
/>
```

**Estado vacío inline sin botón:**
```html
<x-ui.empty-state
    variant="simple"
    icon="heroicon-o-magnifying-glass"
    title="Sin resultados"
    description="Intenta ajustar los filtros de búsqueda."
/>
```

> [!NOTE]
> El botón de acción usa `x-ui.button` con `variant="primary"`, `hoverEffect` e `iconLeft="heroicon-s-plus-circle"`. Si `actionLabel` es `null`, el botón no se renderiza. Si se pasa `actionLabel` sin `actionClick`, el botón aparece sin comportamiento — siempre proporciona ambos props juntos.

---

## Responsividad y Control de Columnas

El sistema maneja la visibilidad en tres niveles que se complementan entre sí:

**Prioridad nativa** — Las columnas definidas en `defaultMobile()` se renderizan sin restricción de dispositivo. Las demás se ocultan en pantallas pequeñas con `hidden md:table-cell`, sin intervención del usuario.

**Control del usuario** — El botón de ajustes (ícono de sliders, esquina superior derecha del componente) despliega un panel con checkboxes que llaman a `wire:click="toggleColumn('clave')"`. El estado se almacena en `$visibleColumns` dentro del componente Livewire y persiste durante la sesión.

**Overflow controlado** — El contenedor usa `overflow-x-auto` con `custom-scrollbar`. Si el usuario activa más columnas de las que caben en pantalla, la tabla permite desplazamiento lateral sin romper el layout.

---

## Beneficios del Sistema

**Consistencia entre módulos** — Todas las tablas de ORVIAN comparten el mismo contrato y comportamiento visual. Cambiar la lógica base en `DataTable.php` se propaga automáticamente a todos los módulos.

**Estado vacío incluido** — El `@empty` del `@forelse` está gestionado por `base-table` internamente. No hay que repetir lógica de estado vacío en cada vista.

**Mantenimiento centralizado** — Modificar qué columnas se muestran en móvil requiere cambiar una sola línea en el `TableConfig`, sin tocar la vista ni el componente Livewire.

**Integración con el Pipeline** — El componente consume directamente el sistema de filtros del Pipeline Pattern sin lógica adicional de adaptación.

**Sin JS adicional** — La reactividad es responsabilidad de Livewire y Alpine. El panel de columnas usa `x-data="{ showSettings: false }"` y `@click.away` sin dependencias externas.

---

## Notas Adicionales

- El `colspan` del estado vacío se calcula como `count($visibleColumns) + 1` para incluir siempre la columna de acciones. Si tu tabla no tiene columna de acciones, ajusta este valor en `base-table.blade.php`.
- Las columnas calculadas o con lógica condicional (badges de color, formatos especiales) deben definirse en la vista Blade de cada módulo, no en `TableConfig`.
- Para añadir una columna nueva: agrégala a `allColumns()`, inclúyela en `defaultDesktop()` si aplica, y añade el `<td>` correspondiente en la vista Blade del módulo.
- `defaultMobile()` debe mantenerse reducido. El objetivo es que la tabla sea usable en pantallas pequeñas sin scroll horizontal en su estado por defecto.