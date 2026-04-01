<?php

namespace App\Filters\Admin\Users;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class SearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function (Builder $query) use ($value) {
            $query->where('name', 'LIKE', "%{$value}%")
                  ->orWhere('email', 'LIKE', "%{$value}%");
        });
    }
}
