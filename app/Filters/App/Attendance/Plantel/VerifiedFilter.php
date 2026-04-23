<?php

namespace App\Filters\App\Attendance\Plantel;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class VerifiedFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $value === 'yes'
            ? $query->whereNotNull('verified_at')
            : $query->whereNull('verified_at');
    }
}
