<?php

namespace App\Filters\App\Academic\Students;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class SearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function (Builder $q) use ($value) {
            $q->where('first_name', 'like', "%{$value}%")
              ->orWhere('last_name', 'like', "%{$value}%")
              ->orWhere('rnc', 'like', "%{$value}%")
              ->orWhere('qr_code', 'like', "%{$value}%");
        });
    }
}