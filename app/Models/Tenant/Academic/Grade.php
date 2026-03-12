<?php

namespace App\Models\Tenant\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Grade extends Model
{
    protected $fillable = ['level_id', 'name', 'order', 'cycle', 'allows_technical'];

    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
    public function sections(): HasMany { return $this->hasMany(SchoolSection::class); }

    // Scope para identificar grados que soportan bachillerato técnico/artes
    public function scopeAllowsTechnical(Builder $query): void
    {
        $query->where('allows_technical', true);
    }
}