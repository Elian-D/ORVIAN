<?php

namespace App\Filters\Admin\Users;

use App\Filters\Base\QueryFilter;

class AdminUserFilters extends QueryFilter
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
