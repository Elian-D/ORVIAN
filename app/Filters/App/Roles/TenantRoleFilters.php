<?php

namespace App\Filters\App\Roles;

use App\Filters\Base\QueryFilter;

class TenantRoleFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'    => SearchFilter::class,
            'is_system' => IsSystemFilter::class,
        ];
    }
}