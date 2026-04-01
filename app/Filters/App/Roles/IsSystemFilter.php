<?php

namespace App\Filters\App\Roles;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class IsSystemFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        // Si $value es 'system', mostrar solo roles del sistema
        // Si es 'custom', mostrar solo roles personalizados
        // Si está vacío, no filtrar
        
        return match($value) {
            'system' => $query->where('is_system', true),
            'custom' => $query->where('is_system', false),
            default => $query,
        };
    }
}