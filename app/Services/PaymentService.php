<?php

namespace App\Services;

use App\Models\Folio\Folio;
use App\Models\Payment\Payment;
use App\Repositories\Payment\PaymentRepositoryInterface;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository
    ) {}

    public function getByFolio(int $folioId)
    {
        return $this->paymentRepository->getByFolio($folioId);
    }

    public function create(array $data): Payment
    {
        $folio = Folio::findOrFail($data['folio_id']);

        $this->ensureFolioIsOpen($folio);

        $this->validateAmount($data['amount']);

        $this->preventOverpayment($folio, $data['amount']);

        if ($data['status'] === 'completed') {
            $data['paid_at'] = now();
        } else {
            $data['paid_at'] = null;
        }

        return $this->paymentRepository->create($data);
    }

    public function findById(int $id): Payment
    {
        return $this->paymentRepository->findById($id);
    }

    public function update(int $id, array $data): Payment
    {
        $payment = $this->findById($id);

        $this->ensureFolioIsOpen($payment->folio);

        $amount = $data['amount'] ?? $payment->amount;

        if (isset($data['amount'])) {
            $this->validateAmount($data['amount']);

            $this->preventOverpayment(
                $payment->folio,
                $data['amount'],
                $payment->id
            );
        }

        if (
            isset($data['status']) &&
            $data['status'] === 'completed'
        ) {
            if (!isset($data['amount'])) {
                $this->preventOverpayment(
                    $payment->folio,
                    $amount,
                    $payment->id
                );
            }

            if ($payment->paid_at === null) {
                $data['paid_at'] = now();
            }
        }

        return $this->paymentRepository->update($id, $data);
    }

    private function ensureFolioIsOpen(Folio $folio): void
    {
        if ($folio->status === 'closed') {
            throw ValidationException::withMessages([
                'folio_id' => 'Cannot accept payment for a closed folio.',
            ]);
        }
    }

    private function validateAmount(float|string $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }
    }

    private function preventOverpayment(
        Folio $folio,
        float|string $newAmount,
        ?int $ignorePaymentId = null
    ): void {
        $charges = $folio->charges()->sum('amount');

        $paid = $folio->payments()
            ->where('status', 'completed')
            ->when(
                $ignorePaymentId,
                fn($q) => $q->where('id', '!=', $ignorePaymentId)
            )
            ->sum('amount');

        if (($paid + $newAmount) > $charges) {
            throw ValidationException::withMessages([
                'amount' => 'Payment exceeds the outstanding balance.',
            ]);
        }
    }
}
