<?php

namespace App\Repositories\Payment;

interface RefundRepositoryInterface
{
    public function getByPayment(int $paymentId);

    public function create(array $data);

    public function findById(int $id);

    public function update(int $id, array $data);
}
