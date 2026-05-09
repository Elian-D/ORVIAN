<?php

namespace App\Filters\App\Academic\Teachers;

use Illuminate\Database\Eloquent\Builder;

class EmploymentTypeFilter
{
    public function apply(Builder $query, string $value): Builder
    {
        return $query->where('employment_type', $value);
    }
}