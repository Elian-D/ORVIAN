<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EducationalDistrict extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'regional_education_id', 'name'];

    public function regionalEducation(): BelongsTo
    {
        return $this->belongsTo(RegionalEducation::class);
    }
}