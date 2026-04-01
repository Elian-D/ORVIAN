# Carga Asíncrona de Tablas (Lazy Loading)

![Livewire 3](https://img.shields.io/badge/Livewire-3.x-purple)
![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)

---

## El Problema

Sin carga asíncrona, cuando el usuario navega a una vista con tabla, el navegador espera que:
1. Laravel ejecute el middleware stack
2. Livewire ejecute `mount()` y `render()` (incluyendo la query SQL)
3. Se renderice todo el HTML

Si la query tarda 200ms, el usuario ve una pantalla en blanco o el layout parpadeando durante ese tiempo. En módulos con datasets grandes o múltiples joins, este retraso es perceptible.

---

## La Solución: `#[Lazy]` en DataTable

La clase base `DataTable` tiene el atributo `#[Lazy]` de Livewire 3. Todos los componentes que hereden de ella cargan automáticamente en **dos fases**:

**Fase 1 — Respuesta inmediata:**
El navegador recibe el HTML completo del layout (navbar, sidebar, breadcrumbs, page-header) con el resultado de `placeholder()` donde irá la tabla. Esta respuesta es instantánea porque no ejecuta queries.

**Fase 2 — Hidratación asíncrona:**
Livewire ejecuta una segunda petición AJAX. En esa petición sí corre `mount()`, `render()` y la query SQL. Cuando termina, reemplaza el skeleton con la tabla real.

```php
// app/Livewire/Base/DataTable.php

#[Lazy]
abstract class DataTable extends Component
{
    public function placeholder(): \Illuminate\Contracts\View\View
    {
        return view('components.ui.skeleton', [
            'type' => 'table',
            'rows' => $this->perPage,  // mismo número de filas que la tabla real
        ]);
    }
    // ...
}
```

---

## Resultado Visual

```
Tiempo 0ms   → Layout completo visible (navbar, sidebar, breadcrumbs)
Tiempo 0ms   → Skeleton de tabla visible (N filas shimmer = $perPage)
Tiempo ~Xms  → Query SQL ejecutada en background
Tiempo ~Xms  → Skeleton reemplazado por la tabla con datos
```

El usuario percibe la aplicación como rápida porque ve contenido inmediatamente, aunque los datos tarden en cargar.

---

## El Componente Skeleton

`x-ui.skeleton` es polimórfico — un componente para todos los estados de carga.

```html
{{-- Para una tabla --}}
<x-ui.skeleton type="table" :rows="15" />

{{-- Para cards de estadísticas --}}
<x-ui.skeleton type="stats" />

{{-- Para listas de usuarios --}}
<x-ui.skeleton type="avatar-text" :rows="5" />

{{-- Para formularios --}}
<x-ui.skeleton type="form" :rows="4" />
```

### Variantes disponibles

| `type` | Descripción |
|---|---|
| `table` | Toolbar + cabecera + filas con avatar y datos. Acepta `rows` y `cols`. |
| `card` | Imagen + título + texto + botón |
| `avatar-text` | Lista de filas con avatar, nombre y badge |
| `stats` | Tarjeta de métrica con icono y número |
| `form` | Campos apilados con labels |

### El número de filas coincide con perPage

`placeholder()` pasa `$this->perPage` como `rows`. Esto hace que el skeleton tenga exactamente las mismas filas que mostrará la tabla real, eliminando el salto visual al reemplazarlo.

---

## Todos los Módulos lo Heredan

No necesitas hacer nada en cada módulo. Si tu componente extiende `DataTable`, la carga asíncrona funciona automáticamente:

```php
// app/Livewire/Admin/Schools/SchoolIndex.php
class SchoolIndex extends DataTable
{
    // Sin @[Lazy], sin placeholder() — viene de DataTable
    // La tabla de escuelas también carga con skeleton automáticamente

    public function render()
    {
        $schools = School::withIndexRelations()
            ->paginate($this->perPage);

        return view('livewire.admin.schools.index', ['schools' => $schools]);
    }
}
```

---

## Desactivar en un Módulo Específico

Si un módulo tiene datos muy simples y el skeleton se vería innecesario:

```php
use Livewire\Attributes\Lazy;

#[Lazy(enabled: false)]
class SimpleIndex extends DataTable
{
    // Este componente carga de forma síncrona
    // El skeleton no aparece
}
```

---

## Diferencia con `wire:loading`

`#[Lazy]` y `wire:loading` resuelven problemas distintos:

| | `#[Lazy]` | `wire:loading` |
|---|---|---|
| **Cuándo actúa** | Carga inicial de la página | Cada interacción posterior (filtrar, paginar) |
| **Qué muestra** | Skeleton completo | Atenuación de opacidad + overlay con blur |
| **Implementación** | En la clase base — automático | En `base-table.blade.php` — automático |

Ambos coexisten y se complementan. El usuario ve:
- Skeleton en la primera carga
- Atenuación rápida en cada filtro/paginación siguiente

---

## Consideraciones

**El skeleton siempre tiene `$perPage` filas:** Si `$perPage` es 15, el skeleton muestra 15 filas. Si el usuario cambia a 50 registros por página, el próximo render mostrará 50 filas en el skeleton. Esto es correcto — refleja la expectativa del usuario.

**Los filtros URL persisten:** `#[Url]` en `$filters` funciona con `#[Lazy]`. Si el usuario llega a `/admin/users?filters[role]=Owner`, el skeleton aparece primero y luego la tabla ya filtrada.

**No hay flash de contenido vacío:** Sin `#[Lazy]`, si la query tarda, Livewire puede mostrar brevemente la tabla sin datos antes del render completo. Con `#[Lazy]`, la tabla nunca aparece incompleta — o es el skeleton, o son los datos finales.