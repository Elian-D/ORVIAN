<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = ['id', 'name'];

    /**
     * Una provincia tiene muchos municipios.
     */
    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
    }
}