<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = ['id', 'name', 'district_id', 'municipality_id'];

    public function municipality(): BelongsTo {
        return $this->belongsTo(Municipality::class);
    }

    public function district(): BelongsTo {
        return $this->belongsTo(District::class);
    }

    public function neighborhoods(): HasMany {
        return $this->hasMany(Neighborhood::class);
    }
}