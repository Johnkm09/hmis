<?php

use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Models\Payment\Payment;
use App\Services\PaymentService;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PaymentService::class);
});

test('service can get payments by folio', function () {

    $folio = Folio::factory()->create();

    Payment::factory()->count(2)->create([
        'folio_id' => $folio->id,
    ]);

    Payment::factory()->create();

    $result = $this->service->getByFolio($folio->id);

    expect($result)->toHaveCount(2);
});

test('service can create a completed payment', function () {

    $folio = Folio::factory()->create();

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
    ]);

    $payment = $this->service->create([
        'folio_id' => $folio->id,
        'amount' => 5000,
        'method' => 'cash',
        'provider' => null,
        'transaction_reference' => null,
        'status' => 'completed',
        'received_by' => 1,
        'notes' => 'Deposit',
    ]);

    expect($payment)
        ->toBeInstanceOf(Payment::class)
        ->and($payment->paid_at)->not->toBeNull();

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'amount' => 5000,
        'status' => 'completed',
    ]);
});

test('service creates pending payment without paid at', function () {

    $folio = Folio::factory()->create();

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
    ]);

    $payment = $this->service->create([
        'folio_id' => $folio->id,
        'amount' => 2000,
        'method' => 'mpesa',
        'provider' => 'Safaricom',
        'transaction_reference' => 'ABC123',
        'status' => 'pending',
        'received_by' => 1,
        'notes' => null,
    ]);

    expect($payment->paid_at)->toBeNull();
});

test('service rejects payment for closed folio', function () {

    $folio = Folio::factory()->create([
        'status' => 'closed',
        'closed_at' => now(),
    ]);

    expect(fn() => $this->service->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'cash',
        'provider' => null,
        'transaction_reference' => null,
        'status' => 'completed',
        'received_by' => 1,
        'notes' => null,
    ]))->toThrow(
        ValidationException::class,
        'Cannot accept payment for a closed folio.'
    );
});

test('service rejects zero payment amount', function () {

    $folio = Folio::factory()->create();

    expect(fn() => $this->service->create([
        'folio_id' => $folio->id,
        'amount' => 0,
        'method' => 'cash',
        'provider' => null,
        'transaction_reference' => null,
        'status' => 'completed',
        'received_by' => 1,
        'notes' => null,
    ]))->toThrow(
        ValidationException::class,
        'Payment amount must be greater than zero.'
    );
});

test('service rejects overpayment', function () {

    $folio = Folio::factory()->create();

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 5000,
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 3000,
        'status' => 'completed',
    ]);

    expect(fn() => $this->service->create([
        'folio_id' => $folio->id,
        'amount' => 2500,
        'method' => 'cash',
        'provider' => null,
        'transaction_reference' => null,
        'status' => 'completed',
        'received_by' => 1,
        'notes' => null,
    ]))->toThrow(
        ValidationException::class,
        'Payment exceeds the outstanding balance.'
    );
});

test('service can update payment to completed', function () {

    $folio = Folio::factory()->create();

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 5000,
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'status' => 'pending',
        'paid_at' => null,
        'amount' => 3000,
    ]);

    $updated = $this->service->update($payment->id, [
        'status' => 'completed',
    ]);

    expect($updated->status)->toBe('completed')
        ->and($updated->paid_at)->not->toBeNull();
});

test('cannot complete a payment if it would cause an overpayment', function () {
    $folio = Folio::factory()->create();

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 5000,
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 3000,
        'status' => 'completed',
    ]);

    $pendingPayment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 3000,
        'status' => 'pending',
        'paid_at' => null,
    ]);

    $service = app(PaymentService::class);

    expect(fn() => $service->update(
        $pendingPayment->id,
        ['status' => 'completed']
    ))->toThrow(
        ValidationException::class,
        'Payment exceeds the outstanding balance.'
    );
});
