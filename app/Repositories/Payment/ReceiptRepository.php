<?php

namespace App\Repositories\Payment;

use App\Models\Payment\Receipt;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ReceiptRepository implements ReceiptRepositoryInterface
{
    public function getAll()
    {
        return QueryBuilder::for(Receipt::class)
            ->with(['payment', 'issuedBy'])
            ->allowedFilters([
                AllowedFilter::exact('payment_id'),
                AllowedFilter::partial('receipt_number'),
            ])
            ->allowedSorts([
                'receipt_number',
                'issued_at',
                'created_at',
            ])
            ->defaultSort('-created_at')
            ->paginate(
                request('per_page', 10)
            );
    }

    public function create(array $data)
    {
        return Receipt::create($data)
            ->load(['payment', 'issuedBy']);
    }

    public function findById(int $id)
    {
        return Receipt::with(['payment', 'issuedBy'])
            ->findOrFail($id);
    }

    public function findByPaymentId(int $paymentId)
    {
        return Receipt::with(['payment', 'issuedBy'])
            ->where('payment_id', $paymentId)
            ->first();
    }

    public function update(int $id, array $data)
    {
        $receipt = Receipt::findOrFail($id);

        $receipt->update($data);

        return $receipt->fresh(['payment', 'issuedBy']);
    }
}
