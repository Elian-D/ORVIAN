<?php

namespace App\Filters\App\Attendance\Excuse;

use App\Filters\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class DateRangeFilter implements FilterInterface
{
    /**
     * El valor llega como array ['from' => 'YYYY-MM-DD', 'to' => 'YYYY-MM-DD']
     * desde el componente x-data-table.filter-date-range.
     */
    public function apply(Builder $query, mixed $value): Builder
    {
        // Verificamos que sea un array y que tenga contenido real
        if (!is_array($value)) return $query;

        $from = !empty($value['from']) ? Carbon::parse($value['from'])->startOfDay() : null;
        $to = !empty($value['to']) ? Carbon::parse($value['to'])->endOfDay() : null;

        return $query->when($from, fn($q) => $q->where('date_start', '>=', $from))
                    ->when($to, fn($q) => $q->where('date_end', '<=', $to));
    }
}