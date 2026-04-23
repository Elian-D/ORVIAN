<?php

namespace App\Filters\App\Attendance\Classroom;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class SectionFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->whereHas('assignment', fn ($q) => $q->where('school_section_id', $value));
    }
}
