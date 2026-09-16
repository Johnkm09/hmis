<?php

use App\Models\Payment\Payment;
use App\Models\Payment\Refund;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsRefundUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

test('receptionist can view refunds for a payment', function () {

    actingAsRefundUser('receptionist');

    $payment = Payment::factory()->create();

    Refund::factory()->count(2)->create([
        'payment_id' => $payment->id,
    ]);

    $response = $this->getJson(
        "/api/v1/payments/{$payment->id}/refunds"
    );

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data',
    ]);

    $response->assertJsonCount(2, 'data');
});

test('receptionist can create a completed refund', function () {

    actingAsRefundUser('receptionist');

    $payment = Payment::factory()->create([
        'amount' => 10000,
        'status' => 'completed',
    ]);

    $response = $this->postJson(
        "/api/v1/payments/{$payment->id}/refunds",
        [
            'amount' => 5000,
            'reason' => 'Guest cancellation',
            'status' => 'completed',
            'transaction_reference' => 'REF-001',
        ]
    );

    $response->assertStatus(201);

    $response->assertJsonPath('data.amount', '5000.00');
    $response->assertJsonPath('data.status', 'completed');

    $this->assertDatabaseHas('refunds', [
        'payment_id' => $payment->id,
        'amount' => 5000,
        'status' => 'completed',
    ]);
});

test('receptionist can create a pending refund', function () {

    actingAsRefundUser('receptionist');

    $payment = Payment::factory()->create([
        'amount' => 10000,
        'status' => 'completed',
    ]);

    $response = $this->postJson(
        "/api/v1/payments/{$payment->id}/refunds",
        [
            'amount' => 2000,
            'reason' => 'Awaiting refund processing',
            'status' => 'pending',
        ]
    );

    $response->assertStatus(201);

    $response->assertJsonPath('data.status', 'pending');
    $response->assertJsonPath('data.refunded_at', null);

    $this->assertDatabaseHas('refunds', [
        'payment_id' => $payment->id,
        'amount' => 2000,
        'status' => 'pending',
        'refunded_at' => null,
    ]);
});

test('receptionist can view a specific refund', function () {

    actingAsRefundUser('receptionist');

    $refund = Refund::factory()->create();

    $response = $this->getJson(
        "/api/v1/refunds/{$refund->id}"
    );

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $refund->id);
    $response->assertJsonPath('data.payment_id', $refund->payment_id);
    $response->assertJsonPath('data.amount', $refund->amount);
    $response->assertJsonPath('data.status', $refund->status);
});

test('manager can update a refund', function () {

    actingAsRefundUser('manager');

    $payment = Payment::factory()->create([
        'amount' => 5000,
        'status' => 'completed',
    ]);
    $refund = Refund::factory()->create([
        'payment_id' => $payment->id,
        'status' => 'pending',
        'refunded_at' => null,
        'amount' => 3000,
    ]);

    $response = $this->patchJson(
        "/api/v1/refunds/{$refund->id}",
        [
            'status' => 'completed',
        ]
    );

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $refund->id);
    $response->assertJsonPath('data.status', 'completed');

    $this->assertDatabaseHas('refunds', [
        'id' => $refund->id,
        'status' => 'completed',
    ]);
});

test('receptionist cannot update a refund', function () {

    actingAsRefundUser('receptionist');

    $refund = Refund::factory()->create();

    $response = $this->patchJson(
        "/api/v1/refunds/{$refund->id}",
        [
            'reason' => 'Updated reason',
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseHas('refunds', [
        'id' => $refund->id,
        'reason' => $refund->reason,
    ]);
});

test('user cannot view refunds', function () {

    actingAsRefundUser('user');

    $payment = Payment::factory()->create();

    $response = $this->getJson(
        "/api/v1/payments/{$payment->id}/refunds"
    );

    $response->assertStatus(403);
});

test('user cannot create refund', function () {

    actingAsRefundUser('user');

    $payment = Payment::factory()->create([
        'status' => 'completed',
    ]);

    $response = $this->postJson(
        "/api/v1/payments/{$payment->id}/refunds",
        [
            'amount' => 1000,
            'reason' => 'Unauthorized refund',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseMissing('refunds', [
        'payment_id' => $payment->id,
        'amount' => 1000,
    ]);
});

test('user cannot update refund', function () {

    actingAsRefundUser('user');

    $refund = Refund::factory()->create();

    $response = $this->patchJson(
        "/api/v1/refunds/{$refund->id}",
        [
            'reason' => 'Unauthorized update',
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseHas('refunds', [
        'id' => $refund->id,
        'reason' => $refund->reason,
    ]);
});

test('unauthenticated user cannot view refunds', function () {

    $payment = Payment::factory()->create();

    $response = $this->getJson(
        "/api/v1/payments/{$payment->id}/refunds"
    );

    $response->assertStatus(401);
});

test('unauthenticated user cannot create refund', function () {

    $payment = Payment::factory()->create([
        'status' => 'completed',
    ]);

    $response = $this->postJson(
        "/api/v1/payments/{$payment->id}/refunds",
        [
            'amount' => 1000,
            'reason' => 'Unauthorized refund',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(401);
});

test('unauthenticated user cannot update refund', function () {

    $refund = Refund::factory()->create();

    $response = $this->patchJson(
        "/api/v1/refunds/{$refund->id}",
        [
            'reason' => 'Unauthorized update',
        ]
    );

    $response->assertStatus(401);
});

test('refund amount must be greater than zero', function () {

    actingAsRefundUser('receptionist');

    $payment = Payment::factory()->create([
        'status' => 'completed',
    ]);

    $response = $this->postJson(
        "/api/v1/payments/{$payment->id}/refunds",
        [
            'amount' => 0,
            'reason' => 'Invalid refund',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'amount',
    ]);
});

test('refund status must be valid', function () {

    actingAsRefundUser('receptionist');

    $payment = Payment::factory()->create([
        'status' => 'completed',
    ]);

    $response = $this->postJson(
        "/api/v1/payments/{$payment->id}/refunds",
        [
            'amount' => 1000,
            'reason' => 'Test refund',
            'status' => 'processing',
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'status',
    ]);
});

test('refund cannot be created for a pending payment', function () {

    actingAsRefundUser('receptionist');

    $payment = Payment::factory()->create([
        'amount' => 5000,
        'status' => 'pending',
    ]);

    $response = $this->postJson(
        "/api/v1/payments/{$payment->id}/refunds",
        [
            'amount' => 1000,
            'reason' => 'Guest cancellation',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseMissing('refunds', [
        'payment_id' => $payment->id,
        'amount' => 1000,
    ]);
});

test('refund cannot exceed the remaining refundable amount', function () {

    actingAsRefundUser('receptionist');

    $payment = Payment::factory()->create([
        'amount' => 10000,
        'status' => 'completed',
    ]);

    Refund::factory()->create([
        'payment_id' => $payment->id,
        'amount' => 7000,
        'status' => 'completed',
    ]);

    $response = $this->postJson(
        "/api/v1/payments/{$payment->id}/refunds",
        [
            'amount' => 4000,
            'reason' => 'Over refund',
            'status' => 'completed',
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseMissing('refunds', [
        'payment_id' => $payment->id,
        'amount' => 4000,
    ]);
});

test('manager can complete a pending refund', function () {

    actingAsRefundUser('manager');

    $payment = Payment::factory()->create([
        'amount' => 10000,
        'status' => 'completed',
    ]);

    $refund = Refund::factory()->create([
        'payment_id' => $payment->id,
        'amount' => 3000,
        'status' => 'pending',
        'refunded_at' => null,
    ]);

    $response = $this->patchJson(
        "/api/v1/refunds/{$refund->id}",
        [
            'status' => 'completed',
        ]
    );

    $response->assertStatus(200);

    $response->assertJsonPath('data.status', 'completed');

    $this->assertDatabaseHas('refunds', [
        'id' => $refund->id,
        'status' => 'completed',
    ]);

    expect($refund->fresh()->refunded_at)->not->toBeNull();
});

test('non-existent refund cannot be viewed', function () {

    actingAsRefundUser('receptionist');

    $response = $this->getJson('/api/v1/refunds/999999');

    $response->assertStatus(404);
});

test('non-existent refund cannot be updated', function () {

    actingAsRefundUser('manager');

    $response = $this->patchJson(
        '/api/v1/refunds/999999',
        [
            'reason' => 'Updated refund',
        ]
    );

    $response->assertStatus(404);
});
