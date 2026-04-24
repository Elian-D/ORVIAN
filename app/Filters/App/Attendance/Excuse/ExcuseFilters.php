<?php

namespace App\Filters\App\Attendance\Excuse;

use App\Filters\Base\QueryFilter;

class ExcuseFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'    => SearchFilter::class,
            'status'     => StatusFilter::class,
            'date_range' => DateRangeFilter::class,
        ];
    }
}