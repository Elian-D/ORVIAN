<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\User;
use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Student extends Model
{
    use HasFactory, SoftDeletes, BelongsToSchool;

    protected $fillable = [
        'school_id', 'school_section_id', 'user_id',
        'first_name', 'last_name', 'gender', 'date_of_birth',
        'place_of_birth', 'rnc', 'blood_type', 'allergies',
        'medical_conditions', 'photo_path', 'face_encoding',
        'is_active', 'enrollment_date', 'withdrawal_date',
        'withdrawal_reason', 'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'withdrawal_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = ['full_name', 'age', 'has_face_encoding', 'has_user_account'];


    // Relaciones
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function section()
    {
        return $this->belongsTo(SchoolSection::class, 'school_section_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plantelAttendanceRecords()
    {
        return $this->hasMany(PlantelAttendanceRecord::class);
    }

    public function classroomAttendanceRecords()
    {
        return $this->hasMany(ClassroomAttendanceRecord::class);
    }

    // Accessors
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}"
        );
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_of_birth 
                ? Carbon::parse($this->date_of_birth)->age 
                : null
        );
    }

    protected function hasFaceEncoding(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->face_encoding)
        );
    }

    protected function hasUserAccount(): Attribute
    {
        return Attribute::make(
            get: fn () => !is_null($this->user_id)
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInSection($query, $sectionId)
    {
        return $query->where('school_section_id', $sectionId);
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'school:id,name',
            'section:id,grade_id,label',
            'section.grade:id,name',
            'user:id,email',
        ]);
    }
}