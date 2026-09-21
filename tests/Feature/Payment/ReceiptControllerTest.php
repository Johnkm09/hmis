<?php

use App\Models\Payment\Payment;
use App\Models\Payment\Receipt;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsReceiptUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

test('receptionist can view receipts', function () {

    actingAsReceiptUser('receptionist');

    Receipt::factory()->count(2)->create();

    $response = $this->getJson('/api/v1/receipts');

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data',
    ]);

    $response->assertJsonCount(2, 'data');
});

test('receptionist can create a receipt for a completed payment', function () {

    actingAsReceiptUser('receptionist');

    $payment = Payment::factory()->create([
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    $response = $this->postJson(
        '/api/v1/receipts',
        [
            'payment_id' => $payment->id,
        ]
    );

    $response->assertStatus(201);

    $response->assertJsonPath('data.payment.id', $payment->id);
    $response->assertJsonPath('data.receipt_number', 'RCT-000001');

    $this->assertDatabaseHas('receipts', [
        'payment_id' => $payment->id,
        'receipt_number' => 'RCT-000001',
    ]);
});

test('receptionist can view a specific receipt', function () {

    actingAsReceiptUser('receptionist');

    $receipt = Receipt::factory()->create();

    $response = $this->getJson(
        "/api/v1/receipts/{$receipt->id}"
    );

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $receipt->id);
    $response->assertJsonPath(
        'data.receipt_number',
        $receipt->receipt_number
    );
});

test('manager can update a receipt', function () {

    actingAsReceiptUser('manager');

    $receipt = Receipt::factory()->create();

    $response = $this->patchJson(
        "/api/v1/receipts/{$receipt->id}",
        []
    );

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $receipt->id);
});

test('receptionist cannot update a receipt', function () {

    actingAsReceiptUser('receptionist');

    $receipt = Receipt::factory()->create();

    $response = $this->patchJson(
        "/api/v1/receipts/{$receipt->id}",
        []
    );

    $response->assertStatus(403);
});

test('user cannot view receipts', function () {

    actingAsReceiptUser('user');

    $response = $this->getJson('/api/v1/receipts');

    $response->assertStatus(403);
});

test('user cannot create receipt', function () {

    actingAsReceiptUser('user');

    $payment = Payment::factory()->create([
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    $response = $this->postJson(
        '/api/v1/receipts',
        [
            'payment_id' => $payment->id,
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseMissing('receipts', [
        'payment_id' => $payment->id,
    ]);
});

test('user cannot update receipt', function () {

    actingAsReceiptUser('user');

    $receipt = Receipt::factory()->create();

    $response = $this->patchJson(
        "/api/v1/receipts/{$receipt->id}",
        []
    );

    $response->assertStatus(403);
});

test('unauthenticated user cannot view receipts', function () {

    $response = $this->getJson('/api/v1/receipts');

    $response->assertStatus(401);
});

test('unauthenticated user cannot create receipt', function () {

    $payment = Payment::factory()->create([
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    $response = $this->postJson(
        '/api/v1/receipts',
        [
            'payment_id' => $payment->id,
        ]
    );

    $response->assertStatus(401);
});

test('unauthenticated user cannot update receipt', function () {

    $receipt = Receipt::factory()->create();

    $response = $this->patchJson(
        "/api/v1/receipts/{$receipt->id}",
        []
    );

    $response->assertStatus(401);
});

test('payment id is required when creating a receipt', function () {

    actingAsReceiptUser('receptionist');

    $response = $this->postJson(
        '/api/v1/receipts',
        []
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'payment_id',
    ]);
});

test('payment must exist when creating a receipt', function () {

    actingAsReceiptUser('receptionist');

    $response = $this->postJson(
        '/api/v1/receipts',
        [
            'payment_id' => 999999,
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'payment_id',
    ]);
});

test('receipt cannot be created for a pending payment', function () {

    actingAsReceiptUser('receptionist');

    $payment = Payment::factory()->create([
        'status' => 'pending',
        'paid_at' => null,
    ]);

    $response = $this->postJson(
        '/api/v1/receipts',
        [
            'payment_id' => $payment->id,
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseMissing('receipts', [
        'payment_id' => $payment->id,
    ]);
});

test('receipt cannot be created for a failed payment', function () {

    actingAsReceiptUser('receptionist');

    $payment = Payment::factory()->create([
        'status' => 'failed',
        'paid_at' => null,
    ]);

    $response = $this->postJson(
        '/api/v1/receipts',
        [
            'payment_id' => $payment->id,
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseMissing('receipts', [
        'payment_id' => $payment->id,
    ]);
});

test('payment can only have one receipt', function () {

    actingAsReceiptUser('receptionist');

    $payment = Payment::factory()->create([
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    Receipt::factory()->create([
        'payment_id' => $payment->id,
    ]);

    $response = $this->postJson(
        '/api/v1/receipts',
        [
            'payment_id' => $payment->id,
        ]
    );

    $response->assertStatus(422);

    expect(
        Receipt::where('payment_id', $payment->id)->count()
    )->toBe(1);
});

test('non-existent receipt cannot be viewed', function () {

    actingAsReceiptUser('receptionist');

    $response = $this->getJson(
        '/api/v1/receipts/999999'
    );

    $response->assertStatus(404);
});

test('non-existent receipt cannot be updated', function () {

    actingAsReceiptUser('manager');

    $response = $this->patchJson(
        '/api/v1/receipts/999999',
        []
    );

    $response->assertStatus(404);
});
