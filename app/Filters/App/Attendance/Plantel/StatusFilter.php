<?php

namespace App\Filters\App\Attendance\Plantel;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class StatusFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('status', $value);
    }
}
