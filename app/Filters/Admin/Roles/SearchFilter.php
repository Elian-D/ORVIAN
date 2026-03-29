<?php

namespace App\Filters\Admin\Roles;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class SearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('name', 'like', "%{$value}%");
    }
}