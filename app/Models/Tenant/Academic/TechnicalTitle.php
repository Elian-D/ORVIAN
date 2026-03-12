<?php

namespace App\Models\Tenant\Academic;

use App\Models\Tenant\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TechnicalTitle extends Model
{
    protected $fillable = ['code', 'technical_family_id', 'name', 'level'];

    public function family(): BelongsTo
    {
        return $this->belongsTo(TechnicalFamily::class, 'technical_family_id');
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_technical_titles');
    }
}