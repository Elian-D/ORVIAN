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
        if (Auth::check() && Auth::user()->school_id) {
            /** @var Model $model */
            $column = $model instanceof \App\Models\Tenant\School 
                ? $model->getKeyName() 
                : 'school_id';
            
            $builder->where($model->getTable() . '.' . $column, Auth::user()->school_id);
        }
    }
}