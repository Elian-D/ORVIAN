<?php

namespace App\Filters\App\Attendance\Manual;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

class SectionFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('school_section_id', $value);
    }
}