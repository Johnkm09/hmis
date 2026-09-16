<?php

use App\Models\Payment\Payment;
use App\Repositories\Payment\PaymentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new PaymentRepository();
});


test('repository can get payments by folio', function () {

    $payment = Payment::factory()->create();

    Payment::factory()->create();

    $result = $this->repository->getByFolio($payment->folio_id);

    expect($result)
        ->toHaveCount(1)
        ->and($result->first()->id)->toBe($payment->id);
});


test('repository can create a payment', function () {

    $payment = Payment::factory()->make();

    $result = $this->repository->create([
        'folio_id' => $payment->folio_id,
        'amount' => $payment->amount,
        'method' => $payment->method,
        'provider' => $payment->provider,
        'transaction_reference' => $payment->transaction_reference,
        'status' => $payment->status,
        'paid_at' => $payment->paid_at,
        'received_by' => $payment->received_by,
        'notes' => $payment->notes,
    ]);

    expect($result)
        ->toBeInstanceOf(Payment::class);

    $this->assertDatabaseHas('payments', [
        'id' => $result->id,
        'folio_id' => $payment->folio_id,
        'amount' => $payment->amount,
        'method' => $payment->method,
    ]);
});


test('repository can find payment by id', function () {

    $payment = Payment::factory()->create();

    $result = $this->repository->findById($payment->id);

    expect($result)
        ->toBeInstanceOf(Payment::class)
        ->and($result->id)->toBe($payment->id);
});


test('repository can update a payment', function () {

    $payment = Payment::factory()->create([
        'status' => 'pending',
        'paid_at' => null,
    ]);

    $result = $this->repository->update($payment->id, [
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    expect($result)
        ->toBeInstanceOf(Payment::class)
        ->and($result->status)->toBe('completed');

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'completed',
    ]);
});
