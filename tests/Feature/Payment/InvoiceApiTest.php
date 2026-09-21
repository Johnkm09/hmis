<?php

use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Models\Payment\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsInvoiceUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

test('receptionist can view invoices', function () {

    actingAsInvoiceUser('receptionist');

    Invoice::factory()->count(2)->create();

    $response = $this->getJson('/api/v1/invoices');

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data',
    ]);

    $response->assertJsonCount(2, 'data');
});

test('receptionist can create an invoice', function () {

    actingAsInvoiceUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 20000,
    ]);

    $response = $this->postJson(
        '/api/v1/invoices',
        [
            'folio_id' => $folio->id,
            'tax_amount' => 3000,
            'discount_amount' => 1000,
            'status' => 'issued',
            'notes' => 'Final guest invoice',
        ]
    );

    $response->assertStatus(201);

    $response->assertJsonPath('data.subtotal', '20000.00');
    $response->assertJsonPath('data.tax_amount', '3000.00');
    $response->assertJsonPath('data.discount_amount', '1000.00');
    $response->assertJsonPath('data.total_amount', '22000.00');
    $response->assertJsonPath('data.status', 'issued');

    $this->assertDatabaseHas('invoices', [
        'folio_id' => $folio->id,
        'subtotal' => 20000,
        'total_amount' => 22000,
        'status' => 'issued',
    ]);
});

test('receptionist can view a specific invoice', function () {

    actingAsInvoiceUser('receptionist');

    $invoice = Invoice::factory()->create();

    $response = $this->getJson(
        "/api/v1/invoices/{$invoice->id}"
    );

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $invoice->id);
    $response->assertJsonPath('data.invoice_number', $invoice->invoice_number);
    $response->assertJsonPath('data.status', $invoice->status);
});

test('manager can update an invoice', function () {

    actingAsInvoiceUser('manager');

    $invoice = Invoice::factory()->create([
        'status' => 'draft',
        'subtotal' => 20000,
        'tax_amount' => 1000,
        'discount_amount' => 500,
        'total_amount' => 20500,
    ]);

    $response = $this->patchJson(
        "/api/v1/invoices/{$invoice->id}",
        [
            'tax_amount' => 2000,
            'discount_amount' => 1000,
        ]
    );

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $invoice->id);
    $response->assertJsonPath('data.tax_amount', '2000.00');
    $response->assertJsonPath('data.discount_amount', '1000.00');
    $response->assertJsonPath('data.total_amount', '21000.00');

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'total_amount' => 21000,
    ]);
});

test('receptionist cannot update an invoice', function () {

    actingAsInvoiceUser('receptionist');

    $invoice = Invoice::factory()->create();

    $response = $this->patchJson(
        "/api/v1/invoices/{$invoice->id}",
        [
            'notes' => 'Updated invoice',
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'notes' => $invoice->notes,
    ]);
});

test('user cannot view invoices', function () {

    actingAsInvoiceUser('user');

    $response = $this->getJson('/api/v1/invoices');

    $response->assertStatus(403);
});

test('user cannot create invoice', function () {

    actingAsInvoiceUser('user');

    $folio = Folio::factory()->create();

    $response = $this->postJson(
        '/api/v1/invoices',
        [
            'folio_id' => $folio->id,
            'status' => 'draft',
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseMissing('invoices', [
        'folio_id' => $folio->id,
    ]);
});

test('user cannot update invoice', function () {

    actingAsInvoiceUser('user');

    $invoice = Invoice::factory()->create();

    $response = $this->patchJson(
        "/api/v1/invoices/{$invoice->id}",
        [
            'notes' => 'Unauthorized update',
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'notes' => $invoice->notes,
    ]);
});

test('unauthenticated user cannot view invoices', function () {

    $response = $this->getJson('/api/v1/invoices');

    $response->assertStatus(401);
});

test('unauthenticated user cannot create invoice', function () {

    $folio = Folio::factory()->create();

    $response = $this->postJson(
        '/api/v1/invoices',
        [
            'folio_id' => $folio->id,
            'status' => 'draft',
        ]
    );

    $response->assertStatus(401);
});

test('unauthenticated user cannot update invoice', function () {

    $invoice = Invoice::factory()->create();

    $response = $this->patchJson(
        "/api/v1/invoices/{$invoice->id}",
        [
            'notes' => 'Unauthorized update',
        ]
    );

    $response->assertStatus(401);
});

test('invoice status must be valid', function () {

    actingAsInvoiceUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->postJson(
        '/api/v1/invoices',
        [
            'folio_id' => $folio->id,
            'status' => 'processing',
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'status',
    ]);
});

test('invoice tax amount cannot be negative', function () {

    actingAsInvoiceUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->postJson(
        '/api/v1/invoices',
        [
            'folio_id' => $folio->id,
            'tax_amount' => -100,
            'status' => 'draft',
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'tax_amount',
    ]);
});

test('invoice discount cannot exceed subtotal', function () {

    actingAsInvoiceUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 5000,
    ]);

    $response = $this->postJson(
        '/api/v1/invoices',
        [
            'folio_id' => $folio->id,
            'discount_amount' => 6000,
            'status' => 'draft',
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseMissing('invoices', [
        'folio_id' => $folio->id,
    ]);
});

test('invoice cannot be created for a closed folio', function () {

    actingAsInvoiceUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'closed',
        'closed_at' => now(),
    ]);

    $response = $this->postJson(
        '/api/v1/invoices',
        [
            'folio_id' => $folio->id,
            'status' => 'draft',
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseMissing('invoices', [
        'folio_id' => $folio->id,
    ]);
});

test('cancelled invoice cannot be updated', function () {

    actingAsInvoiceUser('manager');

    $invoice = Invoice::factory()->create([
        'status' => 'cancelled',
    ]);

    $response = $this->patchJson(
        "/api/v1/invoices/{$invoice->id}",
        [
            'notes' => 'Attempted update',
        ]
    );

    $response->assertStatus(422);
});

test('non-existent invoice cannot be viewed', function () {

    actingAsInvoiceUser('receptionist');

    $response = $this->getJson('/api/v1/invoices/999999');

    $response->assertStatus(404);
});

test('non-existent invoice cannot be updated', function () {

    actingAsInvoiceUser('manager');

    $response = $this->patchJson(
        '/api/v1/invoices/999999',
        [
            'notes' => 'Updated invoice',
        ]
    );

    $response->assertStatus(404);
});
