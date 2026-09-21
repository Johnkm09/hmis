<?php

use App\Models\Payment\Payment;
use App\Models\Payment\Receipt;
use App\Models\User;
use App\Repositories\Payment\ReceiptRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = app(ReceiptRepository::class);
});

it('can get all receipts', function () {
    Receipt::factory()->count(3)->create();

    $receipts = $this->repository->getAll();

    expect($receipts->total())->toBe(3);
});

it('can create a receipt', function () {
    $payment = Payment::factory()->create();
    $user = User::factory()->create();

    $receipt = $this->repository->create([
        'payment_id' => $payment->id,
        'receipt_number' => 'RCT-000001',
        'issued_at' => now(),
        'issued_by' => $user->id,
    ]);

    expect($receipt)
        ->toBeInstanceOf(Receipt::class)
        ->payment_id->toBe($payment->id)
        ->receipt_number->toBe('RCT-000001');
});

it('can find a receipt by id', function () {
    $receipt = Receipt::factory()->create();

    $found = $this->repository->findById($receipt->id);

    expect($found->id)->toBe($receipt->id);
});

it('can update a receipt', function () {
    $receipt = Receipt::factory()->create();

    $updated = $this->repository->update($receipt->id, [
        'issued_at' => now(),
    ]);

    expect($updated->id)->toBe($receipt->id);
});
