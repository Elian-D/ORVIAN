<?php

namespace App\Filters\App\Students;

use App\Filters\Base\QueryFilter;

class StudentFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'             => SearchFilter::class,
            'school_section_id'  => SectionFilter::class,
            'status'             => StatusFilter::class,
            'gender'             => GenderFilter::class,
            'has_photo'          => HasPhotoFilter::class,
            'has_face_encoding'  => HasFaceEncodingFilter::class,
        ];
    }
}