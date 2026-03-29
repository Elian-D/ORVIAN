<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'group_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'group_id');
    }

    /**
     * SOBREESCRIBIR RELACIÓN NATIVA DE SPATIE:
     * Ignora el SchoolScope para evitar que la caché de permisos
     * omita los roles globales (como el Owner) al reconstruirse.
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return parent::roles()->withoutGlobalScope(\App\Models\Scopes\SchoolScope::class);
    }
}