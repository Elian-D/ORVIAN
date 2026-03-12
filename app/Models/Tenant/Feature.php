<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = ['name', 'slug', 'module', 'is_active'];

    public function plans()
    {
        return $this->belongsToMany(Plan::class);
    }
}