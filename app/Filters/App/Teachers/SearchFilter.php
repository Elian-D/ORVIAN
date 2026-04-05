<?php

namespace App\Filters\App\Teachers;

use Illuminate\Database\Eloquent\Builder;

class SearchFilter
{
    public function apply(Builder $query, string $value): Builder
    {
        return $query->where(function (Builder $q) use ($value) {
            $q->where('first_name', 'like', "%{$value}%")
            ->orWhere('last_name', 'like', "%{$value}%")
            ->orWhere('rnc', 'like', "%{$value}%")
            ->orWhere('employee_code', 'like', "%{$value}%");
        });
    }
}