# Sistema de Filtrado (Pipeline Pattern)

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Livewire](https://img.shields.io/badge/Livewire-Compatible-orange)

ORVIAN utiliza un sistema de filtrado desacoplado basado en el patrón **Pipeline**. A diferencia de los filtros tradicionales atados al `Request`, este sistema opera sobre un `array` de datos plano, lo que lo hace compatible con **Livewire**, **API Controllers** y **CLI Commands** sin modificaciones.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [Contratos y Clases Base](#contratos-y-clases-base)
  - [FilterInterface](#filterinterface)
  - [QueryFilter](#queryfilter)
- [Guía de Implementación](#guía-de-implementación)
  - [1. Crear el Filtro Atómico](#1-crear-el-filtro-atómico)
  - [2. Registrar en el Orquestador](#2-registrar-en-el-orquestador)
  - [3. Uso en Livewire](#3-uso-en-livewire)
- [Beneficios del Sistema](#beneficios-del-sistema)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
└── Filters/
    ├── Contracts/
    │   └── FilterInterface.php      # Contrato obligatorio para todo filtro
    ├── Base/
    │   └── QueryFilter.php          # Orquestador: itera y aplica los filtros registrados
    └── [Modulo]/
        ├── [Modulo]Filters.php      # Registro de filtros del módulo
        └── [Nombre]Filter.php       # Lógica atómica de un filtro específico
```

---

## Contratos y Clases Base

### FilterInterface

Todo filtro debe implementar esta interfaz. Define el contrato mínimo: recibir un `Builder` y un valor, y devolver el `Builder` modificado.

```php
namespace App\Filters\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface FilterInterface
{
    /**
     * Aplica la lógica del filtro a la consulta.
     */
    public function apply(Builder $query, mixed $value): Builder;
}
```

### QueryFilter

Clase abstracta que actúa como orquestador. Recibe el array de datos del contexto (Livewire, Request, etc.) e itera sobre los filtros registrados, aplicando únicamente aquellos cuyo valor no sea vacío.

```php
namespace App\Filters\Base;

use Illuminate\Database\Eloquent\Builder;

abstract class QueryFilter
{
    /**
     * @param array $data Filtros crudos. Ej: ['search' => 'Santa', 'modalidad' => 1]
     */
    public function __construct(protected array $data) {}

    /**
     * Itera sobre los filtros registrados y aplica solo los que tienen valor.
     */
    public function apply(Builder $builder): Builder
    {
        foreach ($this->filters() as $key => $filterClass) {
            if (array_key_exists($key, $this->data) && filled($this->data[$key])) {
                $filterInstance = new $filterClass();
                $filterInstance->apply($builder, $this->data[$key]);
            }
        }

        return $builder;
    }

    /**
     * Mapa de [nombre_del_filtro => Clase::class] que cada módulo debe definir.
     */
    abstract protected function filters(): array;
}
```

> [!NOTE]
> `QueryFilter` usa `filled()` de Laravel para ignorar valores nulos, vacíos (`''`) y arrays vacíos. Un filtro solo se ejecuta si su clave existe en `$data` **y** tiene un valor válido.

---

## Guía de Implementación

### 1. Crear el Filtro Atómico

Cada clase de filtro debe implementar `FilterInterface` y realizar **una sola responsabilidad**: aplicar su criterio a la consulta.

**Ejemplo:** `app/Filters/Tenant/School/ModalidadFilter.php`

```php
namespace App\Filters\Tenant\School;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class ModalidadFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('modalidad', $value);
    }
}
```

> [!TIP]
> El parámetro `$value` ya está garantizado como no vacío cuando `apply()` es llamado por el orquestador. No es necesario validarlo dentro del filtro.

---

### 2. Registrar en el Orquestador

Crea la clase `[Modulo]Filters` extendiendo `QueryFilter` y mapea cada clave del array de datos con su clase de filtro correspondiente.

**Ejemplo:** `app/Filters/Tenant/School/SchoolFilters.php`

```php
namespace App\Filters\Tenant\School;

use App\Filters\Base\QueryFilter;

class SchoolFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'    => SearchFilter::class,
            'modalidad' => ModalidadFilter::class,
        ];
    }
}
```

> [!IMPORTANT]
> La clave del array (ej: `'modalidad'`) debe coincidir exactamente con la clave usada en el array `$data` que se pasa al constructor. Si usan un nombre diferente, el filtro nunca se ejecutará.

---

### 3. Uso en Livewire

En un componente Livewire, el estado de los filtros se sincroniza en el array `$filters`. Este array se pasa directamente al orquestador.

```php
use Livewire\Attributes\Url;
use Livewire\Component;
use App\Filters\Tenant\School\SchoolFilters;
use App\Models\School;

class SchoolTable extends Component
{
    #[Url]
    public array $filters = [
        'search'    => '',
        'modalidad' => '',
    ];

    public function render()
    {
        $schools = (new SchoolFilters($this->filters))
            ->apply(School::query())
            ->paginate(10);

        return view('livewire.tenant.school-table', compact('schools'));
    }
}
```

> [!NOTE]
> El atributo `#[Url]` de Livewire serializa automáticamente el array `$filters` en la URL del navegador. Esto permite que los filtros persistan al recargar la página y que la URL sea compartible.

---

## Beneficios del Sistema

**Single Responsibility** — Cada filtro encapsula exactamente una condición de consulta. El componente Livewire o el controlador no conoce nada sobre la base de datos.

**Testabilidad** — Puedes probar un filtro individualmente instanciándolo con un valor y un Builder de prueba, sin necesidad de simular peticiones HTTP ni levantar un componente Livewire.

**Escalabilidad** — Agregar un nuevo criterio de búsqueda implica solo dos pasos: crear la clase del filtro y registrarla en el array del orquestador.

**Portabilidad** — Al operar sobre un `array` genérico en lugar del objeto `Request`, el mismo sistema funciona en Livewire, controladores de API, comandos Artisan y tests unitarios sin cambios.

---

## Notas Adicionales

- Los filtros registrados en `filters()` pero cuya clave no exista en `$data` son ignorados silenciosamente por el orquestador. Esto facilita la inicialización parcial del array de filtros.
- Para módulos con muchos filtros, considera agrupar los filtros relacionados en subdirectorios dentro de `app/Filters/[Modulo]/`.
- Si un filtro requiere acceso a relaciones, aplica el `join` o `whereHas` directamente dentro del método `apply()` de esa clase.