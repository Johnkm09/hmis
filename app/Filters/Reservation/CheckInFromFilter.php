<?php

namespace App\Filters\Reservation;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class CheckInFromFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->whereDate('check_in', '>=', $value);
    }
}
