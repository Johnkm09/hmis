<?php

use App\Models\Payment\Payment;
use App\Models\Payment\Receipt;
use App\Models\User;
use App\Services\ReceiptService;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(ReceiptService::class);
});

test('service can get all receipts', function () {

    Receipt::factory()->count(3)->create();

    $receipts = $this->service->getAll();

    expect($receipts->total())->toBe(3);
});

test('service can create a receipt for a completed payment', function () {

    $payment = Payment::factory()->create([
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    $user = User::factory()->create();

    test()->actingAs($user, 'sanctum');

    $receipt = $this->service->create([
        'payment_id' => $payment->id,
    ]);

    expect($receipt)
        ->toBeInstanceOf(Receipt::class)
        ->and($receipt->payment_id)->toBe($payment->id)
        ->and($receipt->receipt_number)->toBe('RCT-000001')
        ->and($receipt->issued_by)->toBe($user->id)
        ->and($receipt->issued_at)->not->toBeNull();

    $this->assertDatabaseHas('receipts', [
        'id' => $receipt->id,
        'payment_id' => $payment->id,
        'receipt_number' => 'RCT-000001',
        'issued_by' => $user->id,
    ]);
});

test('service rejects receipt for pending payment', function () {

    $payment = Payment::factory()->create([
        'status' => 'pending',
        'paid_at' => null,
    ]);

    expect(fn() => $this->service->create([
        'payment_id' => $payment->id,
    ]))->toThrow(
        ValidationException::class,
        'A receipt can only be issued for a completed payment.'
    );
});

test('service rejects receipt for failed payment', function () {

    $payment = Payment::factory()->create([
        'status' => 'failed',
        'paid_at' => null,
    ]);

    expect(fn() => $this->service->create([
        'payment_id' => $payment->id,
    ]))->toThrow(
        ValidationException::class,
        'A receipt can only be issued for a completed payment.'
    );
});

test('service rejects duplicate receipt for payment', function () {

    $payment = Payment::factory()->create([
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Receipt::factory()->create([
        'payment_id' => $payment->id,
    ]);

    expect(fn() => $this->service->create([
        'payment_id' => $payment->id,
    ]))->toThrow(
        ValidationException::class,
        'This payment already has a receipt.'
    );
});

test('service can find a receipt by id', function () {

    $receipt = Receipt::factory()->create();

    $found = $this->service->findById($receipt->id);

    expect($found->id)->toBe($receipt->id);
});

test('service can update a receipt', function () {

    $receipt = Receipt::factory()->create();

    $updated = $this->service->update($receipt->id, [
        'issued_at' => now(),
    ]);

    expect($updated->id)->toBe($receipt->id);
});
