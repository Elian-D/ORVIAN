<?php

namespace App\Filters\App\Teachers;

use Illuminate\Database\Eloquent\Builder;

class HasUserFilter
{
    public function apply(Builder $query, bool $value): Builder
    {
        return $value
            ? $query->whereNotNull('user_id')
            : $query->whereNull('user_id');
    }
}