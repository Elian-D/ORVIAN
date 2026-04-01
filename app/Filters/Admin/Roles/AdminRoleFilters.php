<?php

namespace App\Filters\Admin\Roles;

use App\Filters\Base\QueryFilter;

class AdminRoleFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'    => SearchFilter::class,
            'is_system' => IsSystemFilter::class,
        ];
    }
}