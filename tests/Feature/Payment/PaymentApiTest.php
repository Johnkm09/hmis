<?php

use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Models\Payment\Payment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsPaymentUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

test('receptionist can view payments for a folio', function () {

    actingAsPaymentUser('receptionist');

    $folio = Folio::factory()->create();

    Payment::factory()->count(2)->create([
        'folio_id' => $folio->id,
    ]);

    $response = $this->getJson(
        "/api/v1/folios/{$folio->id}/payments"
    );

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data',
    ]);

    $response->assertJsonCount(2, 'data');
});

test('receptionist can create a completed payment', function () {

    actingAsPaymentUser('receptionist');

    $folio = Folio::factory()->create();

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
    ]);

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/payments",
        [
            'amount' => 5000,
            'method' => 'cash',
            'status' => 'completed',
            'notes' => 'Deposit',
        ]
    );

    $response->assertStatus(201);

    $response->assertJsonPath('data.amount', '5000.00');
    $response->assertJsonPath('data.status', 'completed');

    $this->assertDatabaseHas('payments', [
        'folio_id' => $folio->id,
        'amount' => 5000,
        'method' => 'cash',
        'status' => 'completed',
    ]);
});

test('receptionist can create a pending payment', function () {

    actingAsPaymentUser('receptionist');

    $folio = Folio::factory()->create();

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 10000,
    ]);

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/payments",
        [
            'amount' => 2000,
            'method' => 'mpesa',
            'provider' => 'Safaricom',
            'transaction_reference' => 'ABC123',
            'status' => 'pending',
        ]
    );

    $response->assertStatus(201);

    $response->assertJsonPath('data.status', 'pending');
    $response->assertJsonPath('data.paid_at', null);

    $this->assertDatabaseHas('payments', [
        'folio_id' => $folio->id,
        'amount' => 2000,
        'status' => 'pending',
        'paid_at' => null,
    ]);
});

test('receptionist can view a specific payment', function () {

    actingAsPaymentUser('receptionist');

    $payment = Payment::factory()->create();

    $response = $this->getJson(
        "/api/v1/payments/{$payment->id}"
    );

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $payment->id);
    $response->assertJsonPath('data.amount', $payment->amount);
    $response->assertJsonPath('data.method', $payment->method);
    $response->assertJsonPath('data.status', $payment->status);
});

test('manager can update a payment', function () {

    actingAsPaymentUser('manager');

    $folio = Folio::factory()->create();

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 5000,
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 3000,
        'status' => 'pending',
        'paid_at' => null,
    ]);

    $response = $this->patchJson(
        "/api/v1/payments/{$payment->id}",
        [
            'status' => 'completed',
        ]
    );

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $payment->id);
    $response->assertJsonPath('data.status', 'completed');

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'completed',
    ]);
});

test('receptionist cannot update a payment', function () {

    actingAsPaymentUser('receptionist');

    $payment = Payment::factory()->create();

    $response = $this->patchJson(
        "/api/v1/payments/{$payment->id}",
        [
            'notes' => 'Updated payment',
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'notes' => $payment->notes,
    ]);
});

test('user cannot view payments', function () {

    actingAsPaymentUser('user');

    $folio = Folio::factory()->create();

    $response = $this->getJson(
        "/api/v1/folios/{$folio->id}/payments"
    );

    $response->assertStatus(403);
});

test('user cannot create payment', function () {

    actingAsPaymentUser('user');

    $folio = Folio::factory()->create();

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/payments",
        [
            'amount' => 1000,
            'method' => 'cash',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseMissing('payments', [
        'folio_id' => $folio->id,
        'amount' => 1000,
    ]);
});

test('user cannot update payment', function () {

    actingAsPaymentUser('user');

    $payment = Payment::factory()->create();

    $response = $this->patchJson(
        "/api/v1/payments/{$payment->id}",
        [
            'notes' => 'Unauthorized update',
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'notes' => $payment->notes,
    ]);
});

test('unauthenticated user cannot view payments', function () {

    $folio = Folio::factory()->create();

    $response = $this->getJson(
        "/api/v1/folios/{$folio->id}/payments"
    );

    $response->assertStatus(401);
});

test('unauthenticated user cannot create payment', function () {

    $folio = Folio::factory()->create();

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/payments",
        [
            'amount' => 1000,
            'method' => 'cash',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(401);
});

test('unauthenticated user cannot update payment', function () {

    $payment = Payment::factory()->create();

    $response = $this->patchJson(
        "/api/v1/payments/{$payment->id}",
        [
            'notes' => 'Unauthorized update',
        ]
    );

    $response->assertStatus(401);
});

test('payment amount must be greater than zero', function () {

    actingAsPaymentUser('receptionist');

    $folio = Folio::factory()->create();

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/payments",
        [
            'amount' => 0,
            'method' => 'cash',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'amount',
    ]);
});

test('payment method must be valid', function () {

    actingAsPaymentUser('receptionist');

    $folio = Folio::factory()->create();

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/payments",
        [
            'amount' => 1000,
            'method' => 'bank_transfer',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'method',
    ]);
});

test('payment status must be valid', function () {

    actingAsPaymentUser('receptionist');

    $folio = Folio::factory()->create();

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/payments",
        [
            'amount' => 1000,
            'method' => 'cash',
            'status' => 'processing',
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'status',
    ]);
});

test('payment cannot exceed the outstanding folio balance', function () {

    actingAsPaymentUser('receptionist');

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

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/payments",
        [
            'amount' => 2500,
            'method' => 'cash',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseMissing('payments', [
        'folio_id' => $folio->id,
        'amount' => 2500,
    ]);
});

test('payment cannot be created for a closed folio', function () {

    actingAsPaymentUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'closed',
        'closed_at' => now(),
    ]);

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/payments",
        [
            'amount' => 1000,
            'method' => 'cash',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseMissing('payments', [
        'folio_id' => $folio->id,
        'amount' => 1000,
    ]);
});

test('non-existent payment cannot be viewed', function () {

    actingAsPaymentUser('receptionist');

    $response = $this->getJson('/api/v1/payments/999999');

    $response->assertStatus(404);
});

test('non-existent payment cannot be updated', function () {

    actingAsPaymentUser('manager');

    $response = $this->patchJson(
        '/api/v1/payments/999999',
        [
            'notes' => 'Updated payment',
        ]
    );

    $response->assertStatus(404);
});
