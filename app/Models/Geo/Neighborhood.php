<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Neighborhood extends Model
{
    protected $fillable = ['id', 'name', 'section_id'];

    public function section(): BelongsTo {
        return $this->belongsTo(Section::class);
    }
}