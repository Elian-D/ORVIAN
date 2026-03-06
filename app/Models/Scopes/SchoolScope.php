<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class SchoolScope implements Scope
{
    /**
     * Aplicar el scope al constructor de consultas de Eloquent.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Determinamos el ID de la escuela: el propio o el de la sesión (soporte)
            $schoolId = $user->school_id ?: session('impersonated_school_id');

            if ($schoolId) {
                /** @var Model $model */
                $column = $model instanceof \App\Models\Tenant\School 
                    ? $model->getKeyName() 
                    : 'school_id';
                
                $builder->where($model->getTable() . '.' . $column, $schoolId);
            }
            // Si no hay school_id y no hay sesión de soporte, el SuperAdmin sigue viendo todo
        }
    }
}