<?php

namespace App\Models;

use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'school_id', 'color', 'is_system'];
    
    protected $casts = [
        'is_system' => 'boolean',
    ];
    
    protected static function booted()
    {
        // Aplicar SchoolScope solo en contexto Tenant
        static::addGlobalScope(new SchoolScope());
        
        // Deshabilitar scope en rutas admin
        static::addGlobalScope('adminBypass', function (Builder $builder) {
            if (request()->is('admin/*')) {
                $builder->withoutGlobalScope(SchoolScope::class);
            }
        });
    }
    
    // Accessor para compatibilidad con Badge
    public function getDisplayNameAttribute(): string
    {
        return trans("roles.{$this->name}.name");
    }
}