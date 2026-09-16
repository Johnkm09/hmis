<?php

use App\Models\Payment\Payment;
use App\Models\Payment\Refund;
use App\Repositories\Payment\RefundRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new RefundRepository();
});

it('gets refunds by payment', function () {
    $payment = Payment::factory()->create();

    Refund::factory()->count(2)->create([
        'payment_id' => $payment->id,
    ]);

    $refunds = $this->repository->getByPayment($payment->id);

    expect($refunds)->toHaveCount(2);
});

it('creates a refund', function () {
    $payment = Payment::factory()->create();

    $refund = $this->repository->create([
        'payment_id' => $payment->id,
        'amount' => 100.00,
        'reason' => 'Guest cancellation',
        'status' => 'completed',
        'refunded_at' => now(),
        'refunded_by' => null,
        'transaction_reference' => 'REF-12345678',
    ]);

    expect($refund)
        ->toBeInstanceOf(Refund::class)
        ->payment_id->toBe($payment->id)
        ->amount->toBe('100.00');
});

it('finds a refund by id', function () {
    $refund = Refund::factory()->create();

    $result = $this->repository->findById($refund->id);

    expect($result->id)->toBe($refund->id);
});

it('updates a refund', function () {
    $refund = Refund::factory()->create([
        'status' => 'pending',
        'refunded_at' => null,
    ]);

    $result = $this->repository->update($refund->id, [
        'status' => 'completed',
        'refunded_at' => now(),
    ]);

    expect($result->status)->toBe('completed')
        ->and($result->refunded_at)->not->toBeNull();
});
