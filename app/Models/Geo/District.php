<?php

namespace App\Models\Geo;

use App\Models\Tenant\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class District extends Model
{
    protected $fillable = ['id', 'name', 'municipality_id'];

    /**
     * El distrito pertenece a un municipio.
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * Un distrito puede tener muchas escuelas.
     */
    public function schools()
    {
        return $this->hasMany(School::class);
    }
}