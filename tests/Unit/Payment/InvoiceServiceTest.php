<?php

use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Models\Payment\Invoice;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(InvoiceService::class);
});

test('service can get all invoices', function () {
    Invoice::factory()->count(3)->create();

    $result = $this->service->getAll();

    expect($result)->toHaveCount(3);
});

test('service can create an invoice from folio charges', function () {
    $folio = Folio::factory()->create(['status' => 'open']);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 5000,
    ]);

    $invoice = $this->service->create([
        'folio_id' => $folio->id,
        'status' => 'draft',
    ]);

    expect($invoice)
        ->toBeInstanceOf(Invoice::class)
        ->and((float) $invoice->subtotal)->toBe(15000.0)
        ->and((float) $invoice->tax_amount)->toBe(0.0)
        ->and((float) $invoice->discount_amount)->toBe(0.0)
        ->and((float) $invoice->total_amount)->toBe(15000.0)
        ->and($invoice->status)->toBe('draft')
        ->and($invoice->issued_at)->toBeNull();
});

test('service automatically generates an invoice number', function () {
    $folio = Folio::factory()->create(['status' => 'open']);

    $invoice = $this->service->create([
        'folio_id' => $folio->id,
        'status' => 'draft',
    ]);

    expect($invoice->invoice_number)->toBe('INV-000001');
});

test('service generates the next invoice number', function () {
    Invoice::factory()->create([
        'invoice_number' => 'INV-000001',
    ]);

    $folio = Folio::factory()->create(['status' => 'open']);

    $invoice = $this->service->create([
        'folio_id' => $folio->id,
        'status' => 'draft',
    ]);

    expect($invoice->invoice_number)->toBe('INV-000002');
});

test('service can create an issued invoice with issued date', function () {
    $folio = Folio::factory()->create(['status' => 'open']);

    $invoice = $this->service->create([
        'folio_id' => $folio->id,
        'status' => 'issued',
    ]);

    expect($invoice->status)
        ->toBe('issued')
        ->and($invoice->issued_at)
        ->not->toBeNull();
});

test('service calculates invoice total using tax and discount', function () {
    $folio = Folio::factory()->create(['status' => 'open']);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 20000,
    ]);

    $invoice = $this->service->create([
        'folio_id' => $folio->id,
        'tax_amount' => 3000,
        'discount_amount' => 2000,
        'status' => 'draft',
    ]);

    expect((float) $invoice->subtotal)->toBe(20000.0)
        ->and((float) $invoice->tax_amount)->toBe(3000.0)
        ->and((float) $invoice->discount_amount)->toBe(2000.0)
        ->and((float) $invoice->total_amount)->toBe(21000.0);
});

test('service rejects a negative discount', function () {
    $folio = Folio::factory()->create(['status' => 'open']);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
    ]);

    expect(fn() => $this->service->create([
        'folio_id' => $folio->id,
        'discount_amount' => -100,
        'status' => 'draft',
    ]))->toThrow(ValidationException::class);
});

test('service rejects a discount greater than subtotal', function () {
    $folio = Folio::factory()->create(['status' => 'open']);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
    ]);

    expect(fn() => $this->service->create([
        'folio_id' => $folio->id,
        'discount_amount' => 10001,
        'status' => 'draft',
    ]))->toThrow(ValidationException::class);
});

test('service cannot create an invoice for a closed folio', function () {
    $folio = Folio::factory()->create(['status' => 'closed']);

    expect(fn() => $this->service->create([
        'folio_id' => $folio->id,
        'status' => 'draft',
    ]))->toThrow(ValidationException::class);
});

test('service can find an invoice by id', function () {
    $invoice = Invoice::factory()->create();

    $result = $this->service->findById($invoice->id);

    expect($result)
        ->toBeInstanceOf(Invoice::class)
        ->and($result->id)
        ->toBe($invoice->id);
});

test('service can update an invoice', function () {
    $invoice = Invoice::factory()->create([
        'status' => 'draft',
        'subtotal' => 20000,
        'tax_amount' => 1000,
        'discount_amount' => 500,
        'total_amount' => 20500,
    ]);

    $result = $this->service->update($invoice->id, [
        'tax_amount' => 2000,
        'discount_amount' => 1000,
    ]);

    expect($result)
        ->toBeInstanceOf(Invoice::class)
        ->and((float) $result->tax_amount)
        ->toBe(2000.0)
        ->and((float) $result->discount_amount)
        ->toBe(1000.0)
        ->and((float) $result->total_amount)
        ->toBe(21000.0);
});

test('service sets issued date when draft invoice is issued', function () {
    $invoice = Invoice::factory()->create([
        'status' => 'draft',
        'issued_at' => null,
    ]);

    $result = $this->service->update($invoice->id, [
        'status' => 'issued',
    ]);

    expect($result->status)
        ->toBe('issued')
        ->and($result->issued_at)
        ->not->toBeNull();
});

test('service clears issued date when invoice is changed from issued status', function () {
    $invoice = Invoice::factory()->create([
        'status' => 'issued',
        'issued_at' => now(),
    ]);

    $result = $this->service->update($invoice->id, [
        'status' => 'draft',
    ]);

    expect($result->status)
        ->toBe('draft')
        ->and($result->issued_at)
        ->toBeNull();
});

test('service cannot update a cancelled invoice', function () {
    $invoice = Invoice::factory()->create([
        'status' => 'cancelled',
    ]);

    expect(fn() => $this->service->update($invoice->id, [
        'notes' => 'Attempted update',
    ]))->toThrow(ValidationException::class);
});
