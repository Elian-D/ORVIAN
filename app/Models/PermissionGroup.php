<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Permission;

class PermissionGroup extends Model
{
    // Constantes para contextos (Evitamos Enums de BD)
    const CONTEXT_GLOBAL = 'global';
    const CONTEXT_TENANT = 'tenant';

    protected $fillable = [
        'name',
        'slug',
        'context',
        'order',
    ];

    /**
     * Relación con los permisos de Spatie.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'group_id');
    }

    /**
     * Scopes para filtrado rápido
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeTenant($query)
    {
        return $query->where('context', self::CONTEXT_TENANT);
    }

    public function scopeGlobal($query)
    {
        return $query->where('context', self::CONTEXT_GLOBAL);
    }

    /**
     * Helper para obtener el label traducido basado en el slug.
     */
    public function getLabelAttribute(): string
    {
        return __("permissions.groups.{$this->slug}");
    }
}