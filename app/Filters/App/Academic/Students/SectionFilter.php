<?php

namespace App\Filters\App\Academic\Students;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class SectionFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where('school_section_id', $value);
    }
}