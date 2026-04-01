<?php

namespace App\Filters\App\Users;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class RoleFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->whereHas('roles', function ($q) use ($value) {
            $q->where('name', $value);
        });
    }
}
