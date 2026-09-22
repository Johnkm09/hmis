<?php

namespace App\Services;

use App\Models\Payment\Payment;
use App\Models\Payment\Receipt;
use App\Repositories\Payment\ReceiptRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReceiptService
{
    public function __construct(
        protected ReceiptRepositoryInterface $receiptRepository
    ) {}

    public function getAll()
    {
        return $this->receiptRepository->getAll();
    }

    public function create(array $data): Receipt
    {
        $payment = Payment::findOrFail($data['payment_id']);

        return $this->createForPayment(
            $payment,
            Auth::id()
        );
    }

    public function createForPayment(
        Payment $payment,
        ?int $issuedBy = null
    ): Receipt {
        if ($payment->status !== 'completed') {
            throw ValidationException::withMessages([
                'payment_id' =>
                'Receipt can only be created for a completed payment.',
            ]);
        }

        if ($this->receiptRepository->findByPaymentId($payment->id)) {
            throw ValidationException::withMessages([
                'payment_id' =>
                'Payment already has a receipt.',
            ]);
        }

        return DB::transaction(function () use (
            $payment,
            $issuedBy
        ) {
            /*
             * Re-check inside the transaction so concurrent
             * receipt creation attempts are also protected.
             */
            if (
                $this->receiptRepository
                ->findByPaymentId($payment->id)
            ) {
                throw ValidationException::withMessages([
                    'payment_id' =>
                    'Payment already has a receipt.',
                ]);
            }

            /*
             * Create the receipt first with a temporary unique
             * number so the database-generated ID can safely
             * become part of the final receipt number.
             */
            $receipt = $this->receiptRepository->create([
                'payment_id' => $payment->id,
                'receipt_number' =>
                'TEMP-' . Str::uuid(),
                'issued_at' => now(),
                'issued_by' => $issuedBy,
            ]);

            $receipt->update([
                'receipt_number' =>
                'RCT-' .
                    str_pad(
                        $receipt->id,
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),
            ]);

            return $receipt->fresh([
                'payment',
                'issuedBy',
            ]);
        });
    }

    public function findById(int $id): Receipt
    {
        return $this->receiptRepository->findById($id);
    }

    public function findByPaymentId(int $paymentId)
    {
        return $this->receiptRepository->findByPaymentId(
            $paymentId
        );
    }

    public function update(int $id, array $data): Receipt
    {
        return $this->receiptRepository->update($id, $data);
    }
}
