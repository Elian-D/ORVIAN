<?php

namespace App\Filters\Admin\Schools;

use App\Filters\Base\QueryFilter;

class AdminSchoolFilters extends QueryFilter
{
    /**
     * Diccionario de filtros disponibles para el módulo de Escuelas (Admin).
     * * @return array<string, string>
     */
    protected function filters(): array
    {
        return [
            'search' => SearchFilter::class,
            'status' => StatusFilter::class,
            'plan'   => PlanFilter::class,
            'suspended' => SuspendedFilter::class,
            'district' => EducationalDistrictFilter::class,
            'regional' => RegionalEducationFilter::class,
        ];
    }
}