<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    protected $fillable = ['id', 'name', 'province_id'];

    /**
     * El municipio pertenece a una provincia.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Un municipio tiene muchos distritos municipales.
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }
}