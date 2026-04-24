<?php

namespace App\Filters\App\Attendance\Classroom;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class TeacherFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('teacher_id', $value);
    }
}
