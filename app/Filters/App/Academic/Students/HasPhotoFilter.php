<?php

namespace App\Filters\App\Academic\Students;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class HasPhotoFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $value ? $query->whereNotNull('photo_path') : $query;
    }
}