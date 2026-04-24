<?php

namespace App\Filters\App\Attendance\Plantel;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class SearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->whereHas('student', fn ($q) => $q
            ->where('first_name', 'like', "%{$value}%")
            ->orWhere('last_name', 'like', "%{$value}%")
            ->orWhere('rnc', 'like', "%{$value}%")
        );
    }
}
