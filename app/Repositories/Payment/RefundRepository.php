<?php

namespace App\Repositories\Payment;

use App\Models\Payment\Refund;

class RefundRepository implements RefundRepositoryInterface
{
    public function getByPayment(int $paymentId)
    {
        return Refund::where('payment_id', $paymentId)
            ->with('refundedBy')
            ->latest('created_at')
            ->get();
    }

    public function create(array $data)
    {
        return Refund::create($data);
    }

    public function findById(int $id)
    {
        return Refund::with('refundedBy')
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $refund = Refund::findOrFail($id);

        $refund->update($data);

        return $refund->fresh('refundedBy');
    }
}
