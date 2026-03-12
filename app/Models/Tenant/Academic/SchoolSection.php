<?php

namespace App\Models\Tenant\Academic;

use App\Traits\BelongsToSchool;
use App\Models\Tenant\Academic\TechnicalTitle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSection extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'grade_id', 'technical_title_id', 'label'];

    public function grade(): BelongsTo { return $this->belongsTo(Grade::class); }
    public function technicalTitle(): BelongsTo { return $this->belongsTo(TechnicalTitle::class); }
}