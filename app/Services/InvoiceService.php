<?php

namespace App\Services;

use App\Models\Folio\Folio;
use App\Models\Payment\Invoice;
use App\Repositories\Payment\InvoiceRepositoryInterface;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        protected InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function getAll()
    {
        return $this->invoiceRepository->getAll();
    }

    public function create(array $data): Invoice
    {
        $folio = Folio::findOrFail($data['folio_id']);

        $this->ensureFolioCanBeInvoiced($folio);

        $totals = $this->calculateTotals($folio);

        $data['invoice_number'] = $this->generateInvoiceNumber();
        $data['subtotal'] = $totals['subtotal'];
        $data['tax_amount'] = $data['tax_amount'] ?? 0;
        $data['discount_amount'] = $data['discount_amount'] ?? 0;

        $this->validateDiscount(
            $data['discount_amount'],
            $data['subtotal']
        );

        $data['total_amount'] = $this->calculateTotal(
            $data['subtotal'],
            $data['tax_amount'],
            $data['discount_amount']
        );

        if ($data['status'] === 'issued') {
            $data['issued_at'] = now();
        } else {
            $data['issued_at'] = null;
        }

        return $this->invoiceRepository->create($data);
    }

    public function findById(int $id): Invoice
    {
        return $this->invoiceRepository->findById($id);
    }

    public function update(int $id, array $data): Invoice
    {
        $invoice = $this->findById($id);

        $this->ensureInvoiceCanBeUpdated($invoice);

        if (
            isset($data['tax_amount']) ||
            isset($data['discount_amount'])
        ) {
            $subtotal = $invoice->subtotal;
            $tax = $data['tax_amount'] ?? $invoice->tax_amount;
            $discount = $data['discount_amount'] ?? $invoice->discount_amount;

            $this->validateDiscount($discount, $subtotal);

            $data['total_amount'] = $this->calculateTotal(
                $subtotal,
                $tax,
                $discount
            );
        }

        if (
            isset($data['status']) &&
            $data['status'] === 'issued'
        ) {
            if ($invoice->issued_at === null) {
                $data['issued_at'] = now();
            }
        }

        if (
            isset($data['status']) &&
            $data['status'] !== 'issued'
        ) {
            $data['issued_at'] = null;
        }

        return $this->invoiceRepository->update($id, $data);
    }

    private function generateInvoiceNumber(): string
    {
        $lastInvoice = Invoice::latest('id')->first();

        $nextNumber = $lastInvoice
            ? $lastInvoice->id + 1
            : 1;

        return 'INV-' . str_pad(
            $nextNumber,
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    private function ensureFolioCanBeInvoiced(Folio $folio): void
    {
        if ($folio->status === 'closed') {
            throw ValidationException::withMessages([
                'folio_id' => 'Cannot create an invoice for a closed folio.',
            ]);
        }
    }

    private function calculateTotals(Folio $folio): array
    {
        return [
            'subtotal' => $folio->charges()->sum('amount'),
        ];
    }

    private function validateDiscount(
        float|string $discount,
        float|string $subtotal
    ): void {
        if ($discount < 0) {
            throw ValidationException::withMessages([
                'discount_amount' => 'Discount cannot be negative.',
            ]);
        }

        if ($discount > $subtotal) {
            throw ValidationException::withMessages([
                'discount_amount' => 'Discount cannot exceed the subtotal.',
            ]);
        }
    }

    private function calculateTotal(
        float|string $subtotal,
        float|string $tax,
        float|string $discount
    ): float {
        return (float) $subtotal + (float) $tax - (float) $discount;
    }

    private function ensureInvoiceCanBeUpdated(Invoice $invoice): void
    {
        if ($invoice->status === 'cancelled') {
            throw ValidationException::withMessages([
                'invoice' => 'Cancelled invoices cannot be updated.',
            ]);
        }
    }
}
