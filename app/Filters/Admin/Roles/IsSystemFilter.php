<?php

namespace App\Filters\Admin\Roles;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class IsSystemFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return match($value) {
            'system' => $query->where('is_system', true),
            'custom' => $query->where('is_system', false),
            default => $query,
        };
    }
}