<?php

namespace App\Services;

use App\Models\Payment\Refund;
use App\Repositories\Payment\PaymentRepositoryInterface;
use App\Repositories\Payment\RefundRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(
        private RefundRepositoryInterface $repository,
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    public function getByPayment(int $paymentId)
    {
        return $this->repository->getByPayment($paymentId);
    }

    public function create(array $data): Refund
    {
        $payment = $this->paymentRepository->findById($data['payment_id']);

        $this->ensurePaymentIsCompleted($payment->status);

        $this->validateRefundAmount(
            $payment,
            (float) $data['amount']
        );

        if (($data['status'] ?? 'pending') === 'completed') {
            $data['refunded_at'] = now();
        }

        return $this->repository->create($data);
    }

    public function findById(int $id): Refund
    {
        return $this->repository->findById($id);
    }

    public function update(int $id, array $data): Refund
    {
        $refund = $this->repository->findById($id);

        $payment = $this->paymentRepository->findById($refund->payment_id);

        $amount = $data['amount'] ?? $refund->amount;
        $status = $data['status'] ?? $refund->status;

        if (
            isset($data['amount']) ||
            (
                $refund->status !== 'completed' &&
                $status === 'completed'
            )
        ) {
            $this->validateRefundAmount(
                $payment,
                (float) $amount,
                $refund->id
            );
        }

        if ($status === 'completed') {
            $data['refunded_at'] = now();
        } else {
            $data['refunded_at'] = null;
        }

        return $this->repository->update($id, $data);
    }

    private function ensurePaymentIsCompleted(string $status): void
    {
        if ($status !== 'completed') {
            throw ValidationException::withMessages([
                'payment' => [
                    'Only completed payments can be refunded.',
                ],
            ]);
        }
    }

    private function validateRefundAmount(
        $payment,
        float $amount,
        ?int $ignoreRefundId = null
    ): void {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Refund amount must be greater than zero.',
                ],
            ]);
        }

        $completedRefunds = $payment->refunds()
            ->where('status', 'completed')
            ->when(
                $ignoreRefundId,
                fn($query) => $query->where('id', '!=', $ignoreRefundId)
            )
            ->sum('amount');

        if (($completedRefunds + $amount) > $payment->amount) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Refund exceeds the remaining refundable amount.',
                ],
            ]);
        }
    }
}
