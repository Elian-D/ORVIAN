<?php

namespace App\Filters\App\Academic\Students;

use Illuminate\Database\Eloquent\Builder;
use App\Filters\Contracts\FilterInterface;

class HasFaceEncodingFilter implements FilterInterface
{
    public function apply(Builder $query, mixed $value): Builder
    {
        return $value ? $query->whereNotNull('face_encoding') : $query;
    }
}