<?php

namespace App\Repositories\Payment;

use App\Models\Payment\Invoice;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function getAll()
    {
        return QueryBuilder::for(Invoice::class)
            ->with(['folio', 'issuedBy'])
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('folio_id'),
                AllowedFilter::partial('invoice_number'),
            ])
            ->allowedSorts([
                'invoice_number',
                'status',
                'total_amount',
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
        return Invoice::create($data);
    }

    public function findById(int $id)
    {
        return Invoice::with(['folio', 'issuedBy'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $invoice = Invoice::findOrFail($id);

        $invoice->update($data);

        return $invoice->fresh(['folio', 'issuedBy']);
    }
}
