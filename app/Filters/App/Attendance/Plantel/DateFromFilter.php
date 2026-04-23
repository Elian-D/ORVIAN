<?php

namespace App\Filters\App\Attendance\Plantel;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class DateFromFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->whereDate('date', '>=', $value);
    }
}
