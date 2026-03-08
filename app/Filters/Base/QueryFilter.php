<?php

namespace App\Filters\Base;

use Illuminate\Database\Eloquent\Builder;

abstract class QueryFilter
{
    /**
     * @param array $data Los filtros crudos (ej: ['search' => 'Santa', 'status' => 1])
     */
    public function __construct(protected array $data) {}

    /**
     * Orquesta la aplicación de cada filtro registrado.
     */
    public function apply(Builder $builder): Builder
    {
        foreach ($this->filters() as $key => $filterClass) {
            // Solo aplicamos si la llave existe en la data y no es nula/vacía
            if (array_key_exists($key, $this->data) && filled($this->data[$key])) {
                $filterInstance = new $filterClass();
                $filterInstance->apply($builder, $this->data[$key]);
            }
        }

        return $builder;
    }

    /**
     * Mapa de [nombre_del_filtro => Clase::class]
     */
    abstract protected function filters(): array;
}