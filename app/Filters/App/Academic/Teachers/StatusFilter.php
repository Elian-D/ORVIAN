<?php

namespace App\Filters\App\Academic\Teachers;

use Illuminate\Database\Eloquent\Builder;

class StatusFilter
{
    public function apply(Builder $query, string $value): Builder
    {
        return $query->where('is_active', (bool) $value);
    }
}