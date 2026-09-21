<?php

use App\Models\Payment\Invoice;
use App\Repositories\Payment\InvoiceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new InvoiceRepository();
});


test('repository can get all invoices', function () {

    Invoice::factory()->count(3)->create();

    $result = $this->repository->getAll();

    expect($result)
        ->toHaveCount(3);
});


test('repository can filter invoices by status', function () {

    Invoice::factory()->create([
        'status' => 'issued',
    ]);

    Invoice::factory()->create([
        'status' => 'paid',
    ]);

    request()->query->set('filter', [
        'status' => 'paid',
    ]);

    $result = $this->repository->getAll();

    expect($result)
        ->toHaveCount(1)
        ->and($result->first()->status)->toBe('paid');
});


test('repository can filter invoices by folio', function () {

    $invoice = Invoice::factory()->create();

    Invoice::factory()->create();

    request()->query->set('filter', [
        'folio_id' => $invoice->folio_id,
    ]);

    $result = $this->repository->getAll();

    expect($result)
        ->toHaveCount(1)
        ->and($result->first()->id)->toBe($invoice->id);
});


test('repository can filter invoices by invoice number', function () {

    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-12345678',
    ]);

    Invoice::factory()->create();

    request()->query->set('filter', [
        'invoice_number' => '12345678',
    ]);

    $result = $this->repository->getAll();

    expect($result)
        ->toHaveCount(1)
        ->and($result->first()->id)->toBe($invoice->id);
});


test('repository can create an invoice', function () {

    $invoice = Invoice::factory()->make();

    $result = $this->repository->create([
        'folio_id' => $invoice->folio_id,
        'invoice_number' => $invoice->invoice_number,
        'subtotal' => $invoice->subtotal,
        'tax_amount' => $invoice->tax_amount,
        'discount_amount' => $invoice->discount_amount,
        'total_amount' => $invoice->total_amount,
        'status' => $invoice->status,
        'issued_at' => $invoice->issued_at,
        'issued_by' => $invoice->issued_by,
        'notes' => $invoice->notes,
    ]);

    expect($result)
        ->toBeInstanceOf(Invoice::class);

    $this->assertDatabaseHas('invoices', [
        'id' => $result->id,
        'folio_id' => $invoice->folio_id,
        'invoice_number' => $invoice->invoice_number,
    ]);
});


test('repository can find invoice by id', function () {

    $invoice = Invoice::factory()->create();

    $result = $this->repository->findById($invoice->id);

    expect($result)
        ->toBeInstanceOf(Invoice::class)
        ->and($result->id)->toBe($invoice->id);
});


test('repository can update an invoice', function () {

    $invoice = Invoice::factory()->create([
        'status' => 'issued',
    ]);

    $result = $this->repository->update($invoice->id, [
        'status' => 'paid',
    ]);

    expect($result)
        ->toBeInstanceOf(Invoice::class)
        ->and($result->status)->toBe('paid');

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => 'paid',
    ]);
});
