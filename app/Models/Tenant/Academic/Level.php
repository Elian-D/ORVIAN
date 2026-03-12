<?php

namespace App\Models\Tenant\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $fillable = ['name', 'slug'];

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}