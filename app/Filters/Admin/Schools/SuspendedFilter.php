<?php

namespace App\Filters\Admin\Schools;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class SuspendedFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Si el valor no es '1' (activado en el toggle), no filtramos
        if ($value !== '1' && $value !== true) {
            return $query;
        }

        return $query->where('is_suspended', true);
    }
}