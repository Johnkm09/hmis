<?php

namespace App\Repositories\Contracts;

use App\Models\Contract\Operation;
use App\Repositories\Contracts\OperationInterface;

class OperationRepository implements OperationInterface
{
    public function getByReservation(int $reservationId)
    {
        return Operation::where('reservation_id', $reservationId)
            ->with('performedBy')
            ->latest('performed_at')
            ->get();
    }

    public function create(array $data)
    {
        return Operation::create($data);
    }
}
