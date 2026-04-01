# Componentes de DataTable (`x-data-table.*`)

![Alpine.js](https://img.shields.io/badge/Alpine.js-Required-green)
![Livewire](https://img.shields.io/badge/Livewire-Required-purple)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-Compatible-blue)

Suite de componentes Blade para construir interfaces de tabla en ORVIAN. Todos son componentes anónimos (sin clase PHP) y se comunican con el Livewire padre via `$wire`.

---

## Arquitectura General

Los componentes se dividen en tres grupos funcionales:

**Toolbar** — controles siempre visibles sobre la tabla:
`search` · `per-page-selector` · `filter-container` · `column-selector`

**Filtros internos** — viven dentro de `filter-container`:
`filter-select` · `filter-toggle` · `filter-date-range` · `filter-range`. Pueden agruparse con `filter-group` (nuevo).


**Feedback reactivo** — responden al estado de los filtros:
`filter-chips` · `cell`

### Contrato con el componente Livewire

Todos los componentes asumen que el Livewire padre extiende `DataTable` y tiene:
- `public array $filters` — array de filtros con claves string
- `public array $visibleColumns` — columnas actualmente visibles
- `public int $perPage` — registros por página
- Métodos: `clearFilter()`, `clearAllFilters()`, `toggleColumn()`, `resetColumns()`

---

## x-data-table.search

Input de búsqueda con debounce de 300ms y botón para limpiar.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `placeholder` | `string` | `'Buscar...'` | Texto del placeholder |
| `filterKey` | `string` | `'search'` | Clave en `$filters` del componente Livewire |

### Comportamiento

- `wire:model.live.debounce.300ms` — espera 300ms tras el último keystroke antes de disparar
- El botón × limpia el filtro con `$wire.set('filters.search', '')`
- El botón × usa Alpine `x-show` para aparecer solo si hay texto

### Ejemplo

```html
<x-data-table.search placeholder="Buscar por nombre o email..." />
<x-data-table.search filterKey="query" placeholder="Buscar factura..." />
```

---

## x-data-table.per-page-selector

Select pill compacto para controlar cuántos registros muestra la tabla.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `options` | `array` | `[10, 25, 50, 100]` | Opciones del select |

### Comportamiento

- `wire:model.live="perPage"` — sincroniza con `$perPage` del DataTable
- Cuando cambia, `updatedPerPage()` en `DataTable` llama `resetPage()` automáticamente
- Diseño visual diferente al `filter-select`: es un pill compacto sin label encima

### Ejemplo

```html
<x-data-table.per-page-selector />
<x-data-table.per-page-selector :options="[5, 10, 20, 50]" />
```

---

## x-data-table.filter-container

Contenedor dropdown para agrupar los filtros del módulo. En mobile se convierte en un drawer desde abajo.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `activeCount` | `int` | `0` | Número de filtros activos (muestra badge naranja) |

### Slots

- `$slot` — los filtros internos (`filter-select`, `filter-toggle`, etc.)

### Comportamiento

- Desktop: dropdown con `@click.away` para cerrar
- Mobile: overlay + panel deslizante desde abajo con botón "Aplicar"
- El botón "Limpiar todo" llama `wire:click="clearAllFilters"`
- Con `activeCount > 0`: el trigger muestra un badge naranja con el número

### Ejemplo

```html
<x-data-table.filter-container
    :activeCount="count(array_filter(array_diff_key($filters, ['trashed' => ''])))">

    <x-data-table.filter-select
        label="Rol"
        filterKey="role"
        :options="$roleOptions"
    />

    <x-data-table.filter-toggle
        label="Solo activos"
        filterKey="only_active"
    />

</x-data-table.filter-container>
```

> [!TIP]
> Para excluir filtros internos (como `trashed`) del conteo de `activeCount`, usa `array_diff_key($filters, ['trashed' => ''])` antes de `array_filter()`.

---

## x-data-table.filter-group 

Componente de agrupación colapsable para organizar filtros relacionados. Ideal para módulos con alta densidad de filtros (ej. Geografía + Suscripción).

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `title` | `string` | — | Título del grupo (se muestra en uppercase) |
| `collapsed` | `bool` | `false` | Si el grupo arranca cerrado |
| `activeCount` | `int` | `0` | Filtros activos *dentro* de este grupo (muestra contador lateral) |

### Comportamiento

- **Estado Persistente:** Usa Alpine `x-collapse` para una animación suave de apertura/cierre.
- **Feedback Visual:** Si `activeCount > 0`, el encabezado resalta y muestra el número de filtros aplicados, incluso si el grupo está colapsado.
- **Diseño Adaptativo:** Ajusta sus márgenes negativos (`-mx-1`) para alinearse perfectamente con los bordes del `filter-container`.

### Ejemplo

```html
<x-data-table.filter-group 
    title="Ubicación" 
    :activeCount="(!empty($filters['regional']) ? 1 : 0) + (!empty($filters['district']) ? 1 : 0)"
>
    <x-data-table.filter-select label="Regional" filterKey="regional" :options="$regionals" />
    <x-data-table.filter-select label="Distrito" filterKey="district" :options="$districts" />
</x-data-table.filter-group>
```

---

## x-data-table.filter-select

Select de filtro con label encima. Solo funciona dentro de `filter-container`.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Label encima del select |
| `filterKey` | `string` | `''` | Clave en `$filters` |
| `options` | `array` | `[]` | Array asociativo `['valor' => 'Label visible']` |
| `placeholder` | `string` | `'Todos'` | Opción vacía por defecto |

### Comportamiento

- `wire:model.live="filters.{filterKey}"`
- Cuando tiene valor seleccionado: el select cambia a color naranja visualmente
- El `placeholder` se muestra como `<option value="">` — seleccionarlo limpia el filtro

### Diferencia con `per-page-selector`

`filter-select` tiene label, usa el ancho completo del dropdown, y el estado activo es naranja. `per-page-selector` es un pill compacto sin label.

### Ejemplo

```html
<x-data-table.filter-select
    label="Estado"
    filterKey="status"
    :options="[
        'online'  => 'En línea',
        'away'    => 'Ausente',
        'busy'    => 'Ocupado',
        'offline' => 'Desconectado',
    ]"
    placeholder="Todos los estados"
/>
```

---

## x-data-table.filter-toggle

Toggle booleano para filtros de tipo on/off con sincronización en tiempo real.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Texto descriptivo del toggle |
| `filterKey` | `string` | `''` | Clave en `$filters` (debe ser booleano) |
| `description` | `string\|null` | `null` | Subtexto bajo el label |

### Comportamiento

- **Sincronización Total:** Utiliza `$wire.entangle().live` para que cualquier cambio en el switch dispare la actualización de la tabla inmediatamente sin necesidad de un botón de "Aplicar".
- **Estado Dual:** Alpine.js gestiona la suavidad de la animación, mientras que Livewire gestiona la persistencia y el filtrado SQL.
- **Auto-Update:** Si el filtro se limpia desde un "Badge" o un botón de "Limpiar todo", el toggle se apagará automáticamente gracias al entrelazado de datos.

> [!IMPORTANT]
> Para que la reactividad sea óptima, inicializa la clave en el array de filtros del componente Livewire como `false` (booleano) y no como `''` (string).
> 
> ```php
> public array $filters = [
>     'suspended' => false, // Correcto
> ];
> ```

### Ejemplo de Uso

```html
<x-data-table.filter-toggle
    label="Solo Suspendidos"
    filterKey="suspended"
    description="Muestra centros con servicios pausados por pago"
/>

---

## x-data-table.filter-date-range

Rango de fechas con dos inputs `date` (desde / hasta).

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Título del grupo |
| `fromKey` | `string` | `'date_from'` | Clave en `$filters` para la fecha inicio |
| `toKey` | `string` | `'date_to'` | Clave en `$filters` para la fecha fin |

### Comportamiento

- Ambos inputs usan `wire:model.live`
- El botón "Limpiar fechas" aparece solo si hay algún valor activo
- `[color-scheme:light] dark:[color-scheme:dark]` hace que el picker nativo respete el tema

### Ejemplo

```html
<x-data-table.filter-date-range
    label="Último acceso"
    fromKey="login_from"
    toKey="login_to"
/>
```

---

## x-data-table.filter-range

Rango numérico con dos inputs (mínimo / máximo).

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `label` | `string` | `''` | Título del grupo |
| `fromKey` | `string` | `'range_min'` | Clave del mínimo en `$filters` |
| `toKey` | `string` | `'range_max'` | Clave del máximo en `$filters` |
| `prefix` | `string\|null` | `null` | Prefijo visual (ej: `'RD$'`) |
| `suffix` | `string\|null` | `null` | Sufijo visual (ej: `'%'`, `'kg'`) |
| `min` | `int` | `0` | Valor mínimo del input |
| `step` | `int` | `1` | Paso del input |

### Ejemplo

```html
<x-data-table.filter-range
    label="Precio"
    fromKey="price_min"
    toKey="price_max"
    prefix="RD$"
/>

<x-data-table.filter-range
    label="Porcentaje"
    fromKey="pct_min"
    toKey="pct_max"
    suffix="%"
    :min="0"
    :step="5"
/>
```

---

## x-data-table.filter-chips

Chips de filtros activos. Se renderiza automáticamente debajo de la toolbar en `base-table`.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `chips` | `array` | `[]` | Array de `[key, label, value]` — viene de `getActiveChips()` |
| `hasFilters` | `bool` | `false` | Si hay filtros activos — controla si se renderiza |

### Comportamiento

- No renderiza nada si `$hasFilters` es `false` — no ocupa espacio
- Cada chip tiene un botón × que llama `wire:click="clearFilter('key')"`
- "Limpiar todo" llama `wire:click="clearAllFilters"`

### Uso en base-table (automático)

```html
<x-data-table.filter-chips
    :chips="$this->getActiveChips()"
    :hasFilters="count(array_filter($filters)) > 0"
/>
```

### Formato del array `chips`

`getActiveChips()` de `DataTable` devuelve:
```php
[
    ['key' => 'role',   'label' => 'Rol',    'value' => 'Owner'],
    ['key' => 'status', 'label' => 'Estado', 'value' => 'online'],
]
```

Los labels vienen de `TableConfig::filterLabels()`.

---

## x-data-table.column-selector

Selector de columnas visibles. Dropdown en desktop, drawer en mobile.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `definition` | `string\|null` | `null` | Clase TableConfig (`::class`) |
| `visibleColumns` | `array` | `[]` | Columnas actualmente visibles |

### Comportamiento

- Los checkboxes usan Alpine `:checked="isVisible(key)"` — reactivos a cambios de `$wire.visibleColumns`
- El botón "Restablecer" llama `$wire.resetColumns(isMobile)` con el contexto del dispositivo
- Guard de mínimo: si solo queda 1 columna visible, ese checkbox se deshabilita
- En `init()`, detecta si el cliente está en mobile y corrige las columnas si el servidor mandó defaults de desktop
- El listener de `matchMedia` re-detecta si el usuario rota la pantalla

### Por qué los checkboxes son Alpine y no PHP

`@checked($isVisible)` es PHP estático — se evalúa una vez en el render inicial. Cuando Livewire hace morfing del DOM tras `resetColumns()`, PHP no re-corre el template. Alpine con `:checked` es reactivo y siempre refleja el estado actual de `$wire.visibleColumns`.

---

## x-data-table.cell

Celda de tabla (`<td>`) que se auto-oculta si la columna no está en las columnas visibles.

### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `column` | `string` | — | Clave de la columna |
| `visible` | `array` | — | Array `$visibleColumns` del componente |
| `class` | `string` | `'px-4 py-3.5'` | Clases de padding del `<td>` |

### Comportamiento

- Si `$column` no está en `$visible`, no renderiza nada (ni siquiera el `<td>`)
- `$attributes->merge()` permite pasar atributos HTML adicionales al `<td>`

### Ejemplo

```html
<x-data-table.cell column="name" :visible="$visibleColumns">
    <div class="flex items-center gap-3">
        <x-ui.avatar :user="$user" size="sm" />
        <span>{{ $user->name }}</span>
    </div>
</x-data-table.cell>

{{-- Con clase adicional --}}
<x-data-table.cell column="email" :visible="$visibleColumns" class="px-4 py-3.5 max-w-xs">
    {{ $user->email }}
</x-data-table.cell>
```

### Alternativa con cellClass()

Si prefieres `@if` manual con el trait:

```html
@if(in_array('email', $visibleColumns))
    <td class="{{ \App\Tables\Admin\AdminUserTableConfig::cellClass('email') }}">
        {{ $user->email }}
    </td>
@endif
```

Ambas formas son válidas. `x-data-table.cell` es más limpio en vistas con muchas columnas.

---

## Responsividad de los Dropdowns

Todos los componentes dropdown (`filter-container`, `column-selector`) usan un patrón de detección de dispositivo en Alpine:

```js
isMobile: window.innerWidth < 768,
init() {
    window.addEventListener('resize', () => {
        this.isMobile = window.innerWidth < 768;
    });
}
```

En mobile (`< 768px`):
- El dropdown se reemplaza por un **drawer** desde la parte inferior de la pantalla
- El drawer tiene un handle visual, overlay con blur, y botón "Aplicar" / "Listo"
- No puede salirse de los límites de la pantalla

En desktop:
- Dropdown estándar con `@click.away` y transición de escala

Este comportamiento es automático — no requiere configuración por módulo.