<?php

namespace App\Repositories\Payment;

interface ReceiptRepositoryInterface
{
    public function getAll();

    public function create(array $data);

    public function findById(int $id);

    public function findByPaymentId(int $paymentId);

    public function update(int $id, array $data);
}
