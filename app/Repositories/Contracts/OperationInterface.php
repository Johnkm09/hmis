<?php

namespace App\Repositories\Contracts;

interface OperationInterface
{
    public function getByReservation(int $reservationId);

    public function create(array $data);
}
