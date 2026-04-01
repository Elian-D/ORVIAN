<?php

namespace App\Filters\App\Users;

use App\Filters\Base\QueryFilter;

class TenantUserFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search' => SearchFilter::class,
            'role'   => RoleFilter::class,
            'status' => StatusFilter::class,
        ];
    }
}
