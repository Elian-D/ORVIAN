<?php

namespace App\Filters\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface FilterInterface
{
    /**
     * Aplica la lógica del filtro a la consulta.
     */
    public function apply(Builder $query, mixed $value): Builder;
}