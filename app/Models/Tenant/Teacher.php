<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Academic\Subject;
use App\Models\Tenant\Academic\TeacherSubjectSection;
use App\Models\User;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes, BelongsToSchool;

    protected $fillable = [
        'school_id', 'user_id', 'first_name', 'last_name', 'gender',
        'date_of_birth', 'rnc', 'employee_code', 'specialization',
        'employment_type', 'phone', 'emergency_contact_name',
        'emergency_contact_phone', 'photo_path', 'is_active',
        'hire_date', 'termination_date', 'termination_reason', 'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = ['full_name', 'has_user_account'];

    // Relaciones
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(TeacherSubjectSection::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject_sections')
            ->withPivot(['school_section_id', 'academic_year_id'])
            ->withTimestamps();
    }

    // Accessors
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}"
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

    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'school:id,name',
            'user:id,name,email',
        ]);
    }
}