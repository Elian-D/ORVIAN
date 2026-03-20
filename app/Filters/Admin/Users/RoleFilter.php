<?php

namespace App\Filters\Admin\Users;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class RoleFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->whereHas('roles', function (Builder $query) use ($value) {
            $query->where('name', $value);
        });
    }
}
