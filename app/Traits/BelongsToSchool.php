<?php

namespace App\Traits;

use App\Models\Scopes\SchoolScope;
use App\Models\Tenant\School;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToSchool
{
    /**
     * El "boot" del trait se ejecuta automáticamente al iniciar el modelo.
     */
    protected static function bootBelongsToSchool(): void
    {
        // 1. Aplicamos el Scope Global para filtrado automático
        static::addGlobalScope(new SchoolScope);

        // 2. Evento "creating": Asignación automática de school_id
        static::creating(function ($model) {
            if (empty($model->school_id) && Auth::check()) {
                $model->school_id = Auth::user()->school_id;
            }
        });
    }

    /**
     * Relación: El modelo pertenece a una Escuela.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}