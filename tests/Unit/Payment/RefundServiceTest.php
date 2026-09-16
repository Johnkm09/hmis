<?php

use App\Models\Payment\Payment;
use App\Models\Payment\Refund;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Payment\RefundRepository;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new RefundService(
        new RefundRepository(),
        new PaymentRepository()
    );
});

test('service gets refunds by payment', function () {
    $payment = Payment::factory()->create();

    Refund::factory()->count(2)->create([
        'payment_id' => $payment->id,
    ]);

    $refunds = $this->service->getByPayment($payment->id);

    expect($refunds)->toHaveCount(2);
});

test('service creates a completed refund', function () {
    $payment = Payment::factory()->create([
        'amount' => 1000,
        'status' => 'completed',
    ]);

    $refund = $this->service->create([
        'payment_id' => $payment->id,
        'amount' => 250,
        'reason' => 'Guest request',
        'status' => 'completed',
        'refunded_by' => null,
        'transaction_reference' => 'REF-001',
    ]);

    expect($refund->status)->toBe('completed')
        ->and($refund->refunded_at)->not->toBeNull()
        ->and($refund->amount)->toBe('250.00');
});

test('service rejects refund for pending payment', function () {
    $payment = Payment::factory()->create([
        'status' => 'pending',
    ]);

    expect(fn() => $this->service->create([
        'payment_id' => $payment->id,
        'amount' => 100,
        'reason' => 'Test',
        'status' => 'completed',
    ]))->toThrow(ValidationException::class);
});

test('service rejects refund greater than remaining balance', function () {
    $payment = Payment::factory()->create([
        'amount' => 1000,
        'status' => 'completed',
    ]);

    Refund::factory()->create([
        'payment_id' => $payment->id,
        'amount' => 800,
        'status' => 'completed',
    ]);

    expect(fn() => $this->service->create([
        'payment_id' => $payment->id,
        'amount' => 300,
        'reason' => 'Over refund',
        'status' => 'completed',
    ]))->toThrow(ValidationException::class);
});

test('service completes a pending refund', function () {
    $payment = Payment::factory()->create([
        'amount' => 1000,
        'status' => 'completed',
    ]);

    $refund = Refund::factory()->create([
        'payment_id' => $payment->id,
        'status' => 'pending',
        'refunded_at' => null,
        'amount' => 200,
    ]);

    $result = $this->service->update(
        $refund->id,
        ['status' => 'completed']
    );

    expect($result->status)->toBe('completed')
        ->and($result->refunded_at)->not->toBeNull();
});

test('service rejects increasing a completed refund beyond the remaining refundable amount', function () {
    $payment = Payment::factory()->create([
        'amount' => 1000,
        'status' => 'completed',
    ]);

    $refund = Refund::factory()->create([
        'payment_id' => $payment->id,
        'amount' => 600,
        'status' => 'completed',
    ]);

    Refund::factory()->create([
        'payment_id' => $payment->id,
        'amount' => 300,
        'status' => 'completed',
    ]);

    expect(fn() => $this->service->update(
        $refund->id,
        ['amount' => 800]
    ))->toThrow(ValidationException::class);
});

test('service clears refunded at when a completed refund is no longer completed', function () {
    $payment = Payment::factory()->create([
        'amount' => 1000,
        'status' => 'completed',
    ]);

    $refund = Refund::factory()->create([
        'payment_id' => $payment->id,
        'amount' => 300,
        'status' => 'completed',
        'refunded_at' => now(),
    ]);

    $result = $this->service->update(
        $refund->id,
        ['status' => 'failed']
    );

    expect($result->status)->toBe('failed')
        ->and($result->refunded_at)->toBeNull();
});
