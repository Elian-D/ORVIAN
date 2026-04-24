<?php

namespace App\Filters\App\Students;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class StatusFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Importante: Tratamos '0' como falso y '1' como verdadero
        if ($value === '' || $value === null) return $query;

        return $query->where('is_active', (bool) $value);
    }
}