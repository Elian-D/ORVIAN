<?php

namespace App\Filters\App\Teachers;

use App\Filters\Base\QueryFilter;

class TeacherFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'             => SearchFilter::class,
            'status'             => StatusFilter::class,
            'employment_type'    => EmploymentTypeFilter::class,
            'has_user'           => HasUserFilter::class,
        ];
    }
}