<?php

namespace App\Services;

use App\Models\Payment\Receipt;
use App\Repositories\Payment\PaymentRepositoryInterface;
use App\Repositories\Payment\ReceiptRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReceiptService
{
    protected ReceiptRepositoryInterface $receiptRepository;

    protected PaymentRepositoryInterface $paymentRepository;

    public function __construct(
        ReceiptRepositoryInterface $receiptRepository,
        PaymentRepositoryInterface $paymentRepository
    ) {
        $this->receiptRepository = $receiptRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function getAll()
    {
        return $this->receiptRepository->getAll();
    }

    public function create(array $data)
    {
        $payment = $this->paymentRepository->findById($data['payment_id']);

        if ($payment->status !== 'completed') {
            throw ValidationException::withMessages([
                'payment_id' => [
                    'A receipt can only be issued for a completed payment.',
                ],
            ]);
        }

        if ($this->receiptRepository->findByPaymentId($payment->id)) {
            throw ValidationException::withMessages([
                'payment_id' => [
                    'This payment already has a receipt.',
                ],
            ]);
        }

        $nextId = Receipt::max('id') + 1;

        $data['receipt_number'] = 'RCT-' . str_pad(
            $nextId,
            6,
            '0',
            STR_PAD_LEFT
        );

        $data['issued_at'] = now();
        $data['issued_by'] = Auth::id();

        return $this->receiptRepository->create($data);
    }

    public function findById(int $id)
    {
        return $this->receiptRepository->findById($id);
    }

    public function update(int $id, array $data)
    {
        return $this->receiptRepository->update($id, $data);
    }
}
