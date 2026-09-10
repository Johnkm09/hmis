<?php

namespace App\Filters\Reservation;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class CheckOutFromFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->whereDate('check_out', '>=', $value);
    }
}
