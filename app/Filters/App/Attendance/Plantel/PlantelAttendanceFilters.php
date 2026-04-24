<?php

namespace App\Filters\App\Attendance\Plantel;

use App\Filters\Base\QueryFilter;

class PlantelAttendanceFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'     => SearchFilter::class,
            'date_from'  => DateFromFilter::class,
            'date_to'    => DateToFilter::class,
            'section_id' => SectionFilter::class,
            'status'     => StatusFilter::class,
            'method'     => MethodFilter::class,
            'verified'   => VerifiedFilter::class,
        ];
    }
}
