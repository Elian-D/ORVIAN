<?php

namespace App\Models\Tenant;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StudentImportRecord extends Model
{
    protected $fillable = [
        'school_id', 'created_by', 'status',
        'total_rows', 'processed_rows', 'success_rows', 'failed_rows',
        'file_path', 'mapping', 'default_section_id', 'errors',
    ];

    protected $casts = [
        'mapping' => 'array',
        'errors'  => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows === 0) return 0;
        return round(($this->processed_rows / $this->total_rows) * 100, 1);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }
}
