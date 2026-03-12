<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegionalEducation extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'name'];

    public function districts(): HasMany
    {
        return $this->hasMany(EducationalDistrict::class);
    }
}