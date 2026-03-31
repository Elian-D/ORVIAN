<?php

namespace App\Filters\Admin\Schools;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class PlanFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        if (!$value) return $query;

        return $query->where('plan_id', $value);
    }
}