<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Academic\SchoolShift;
use App\Models\User;
use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DailyAttendanceSession extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'school_shift_id', 'date', 'opened_at', 'closed_at',
        'opened_by', 'closed_by', 'total_expected', 'total_registered',
        'total_present', 'total_late', 'total_absent', 'total_excused', 'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relaciones
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function shift()
    {
        return $this->belongsTo(SchoolShift::class, 'school_shift_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // Helper methods
    public function isOpen(): bool
    {
        return is_null($this->closed_at);
    }

    public function incrementRegistered(): void
    {
        $this->increment('total_registered');
    }

    public function updateStats(array $stats): void
    {
        $this->update($stats);
    }

    // Scopes
    public function scopeForDate($query, Carbon $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('closed_at');
    }
}