<?php

namespace App\Repositories\Payment;

use App\Models\Payment\Payment;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function getByFolio(int $folioId)
    {
        return Payment::where('folio_id', $folioId)
            ->with('receivedBy')
            ->latest('created_at')
            ->get();
    }

    public function create(array $data)
    {
        return Payment::create($data);
    }

    public function findById(int $id)
    {
        return Payment::with('receivedBy')->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $payment = Payment::findOrFail($id);

        $payment->update($data);

        return $payment->fresh('receivedBy');
    }
}
