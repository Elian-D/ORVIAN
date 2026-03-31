<?php

namespace App\Filters\Admin\Schools;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class SearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        if (!$value) return $query;

        return $query->where(function ($q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
              ->orWhere('sigerd_code', 'like', "%{$value}%");
        });
    }
}