<?php

namespace App\Filters\App\Students;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class GenderFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('gender', $value);
    }
}