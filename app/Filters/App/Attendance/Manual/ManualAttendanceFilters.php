<?php

namespace App\Filters\App\Attendance\Manual;

use App\Filters\Base\QueryFilter;

class ManualAttendanceFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'  => SearchFilter::class,
            'section' => SectionFilter::class,
        ];
    }
}