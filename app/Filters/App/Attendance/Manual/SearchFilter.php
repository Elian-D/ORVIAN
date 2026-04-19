<?php

namespace App\Filters\App\Attendance\Manual;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class SearchFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where(function ($q) use ($value) {
            $q->where('first_name', 'like', "%{$value}%")
              ->orWhere('last_name', 'like', "%{$value}%")
              ->orWhere('rnc', 'like', "%{$value}%"); // Opcional: buscar por matrícula
        });
    }
}