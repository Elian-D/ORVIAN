# Prevención de N+1 con `scopeWithIndexRelations`

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Spatie](https://img.shields.io/badge/Spatie_Permissions-Compatible-purple)

---

## El Problema

El problema N+1 ocurre cuando se ejecuta **1 query para obtener la lista** y luego **N queries adicionales** (una por fila) para obtener datos relacionados. En tablas de ORVIAN, el caso más común es Spatie Permissions:

```php
// render() sin eager loading
$users = User::whereNull('school_id')->paginate(15);

// En la vista, por cada usuario:
{{ $user->getRoleNames()->first() }}
// → ejecuta: SELECT roles FROM model_has_roles WHERE model_id = {id}
// 15 usuarios = 15 queries extra → 17 queries totales
// 100 usuarios = 100 queries extra → 102 queries totales
```

Con 100 usuarios en el DebugBar se ven ~107 queries donde deberían ser 3-4.

---

## La Solución: `scopeWithIndexRelations`

Cada modelo que se use en una tabla define un scope que centraliza el eager loading necesario para la vista de listado. Es el contrapunto del `allColumns()` del `TableConfig`: el TableConfig dice **qué mostrar**, el scope dice **qué cargar** para que ese mostrar sea eficiente.

```php
// app/Models/User.php

public function scopeWithIndexRelations($query)
{
    // Carga roles en 1 query adicional para todos los usuarios del resultado
    return $query->with('roles');
}
```

Con esto, Eloquent ejecuta:
1. `SELECT * FROM users WHERE school_id IS NULL LIMIT 15`
2. `SELECT * FROM roles JOIN model_has_roles WHERE model_id IN (1,2,3,...,15)`

2 queries, independientemente de cuántos usuarios haya en la página.

---

## Uso en el Componente Livewire

```php
public function render()
{
    $query = User::whereNull('school_id')
        ->withIndexRelations();  // ← siempre aplicar antes de filtros

    // Los filtros se aplican sobre la query ya con las relaciones declaradas
    $users = (new AdminUserFilters($this->filters))
        ->apply($query)
        ->orderBy('id')
        ->paginate($this->perPage);

    return view('livewire.admin.users.index', ['users' => $users]);
}
```

El scope se declara antes de los filtros para que Eloquent pueda optimizar el JOIN correctamente.

---

## Casos Comunes en ORVIAN

### Spatie Permissions (roles)

```php
// Modelo User, School Principal, Teacher, etc.
public function scopeWithIndexRelations($query)
{
    return $query->with('roles');
}
```

### Relación BelongsTo simple

```php
// Modelo Student — necesita school para mostrar el nombre del centro
public function scopeWithIndexRelations($query)
{
    return $query->with('school');
}
```

### Múltiples relaciones

```php
// Modelo Enrollment — necesita student y section
public function scopeWithIndexRelations($query)
{
    return $query->with(['student', 'section.grade']);
}
```

### Relación con conteo

```php
// Modelo School — necesita contar estudiantes
public function scopeWithIndexRelations($query)
{
    return $query->with('plan')->withCount('users');
}
```

---

## Regla de Implementación

Cuando crees un nuevo módulo con tabla:

1. Identifica qué relaciones consume la vista (badges de rol, nombres de entidades relacionadas, conteos)
2. Agrégalas al scope `scopeWithIndexRelations()` del modelo
3. Aplica el scope en `render()` antes de los filtros
4. Verifica en el DebugBar que las queries no crezcan al paginar

```php
// Checklist mental antes de `->paginate()`:
// ¿La vista llama métodos como getRoleNames(), $model->relation->name, etc.?
// → Si sí, esas relaciones deben estar en scopeWithIndexRelations()
```

---

## Diagnóstico con Tinker

Si sospechas de un N+1 en un módulo existente:

```php
// Comparar con y sin scopes globales
\App\Models\User::withoutGlobalScopes()->whereNull('school_id')->count();
// vs
\App\Models\User::whereNull('school_id')->count();
// Si el primero es mayor → hay un GlobalScope filtrando (SoftDeletes, tenant scope, etc.)

// Medir queries con eager loading
\App\Models\User::with('roles')->whereNull('school_id')->paginate(15);
// Observar en DebugBar: debe ser 2 queries, no 17
```

---

## N+1 en Listas de Selects (CatalogService)

El N+1 también ocurre en los dropdowns de formularios cuando se cargan opciones sin eager loading:

```php
// ❌ N+1 en un select de secciones por grado
$sections = Section::all(); // luego en la vista: $section->grade->name

// ✅ Correcto
$sections = Section::with('grade')->orderBy('name')->get();
```

Para módulos con muchos selects dependientes, centraliza la carga en el método del componente Livewire que popula los datos del formulario.

---

## Notas

- `scopeWithIndexRelations` es una **convención de ORVIAN**, no un método de Laravel. Laravel lo detecta como Local Query Scope por el prefijo `scope`.
- Solo cargar lo que la **vista de listado** necesita. No cargar relaciones para formularios (el formulario de edición puede hacer su propia query con `findOrFail()`).
- Si el scope empieza a tener muchas relaciones (más de 4-5), es señal de que la vista muestra demasiados datos o que hay columnas que deberían estar en `defaultDesktop()` pero no en `allColumns()`.