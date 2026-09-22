<?php

use App\Models\Folio\Folio;
use App\Models\Payment\Payment;
use App\Models\Payment\Receipt;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\StripeService;
use Stripe\Service\PaymentIntentService;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $paymentIntent = (object) [
        'id' => 'pi_test_123',
        'client_secret' => 'pi_test_123_secret',
    ];

    $paymentIntentService = Mockery::mock(PaymentIntentService::class);

    $paymentIntentService
        ->shouldReceive('create')
        ->andReturn($paymentIntent);

    $stripe = Mockery::mock(StripeClient::class);

    $stripe->paymentIntents = $paymentIntentService;

    $this->app->instance(
        StripeService::class,
        new StripeService($stripe)
    );
});

function actingAsStripeUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

function stripeTestSignature(string $payload, string $secret): string
{
    $timestamp = time();

    $signature = hash_hmac(
        'sha256',
        $timestamp . '.' . $payload,
        $secret
    );

    return 't=' . $timestamp . ',v1=' . $signature;
}

test('receptionist can create a Stripe payment intent', function () {

    actingAsStripeUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $folio->charges()->create([
        'amount' => 5000,
        'quantity' => 1,
        'unit_price' => 5000,
        'type' => 'accommodation',
        'description' => 'Room charge',
        'charged_at' => now(),
        'charged_by' => auth()->id(),
    ]);

    $response = $this->postJson(
        '/api/v1/stripe/payment-intent',
        [
            'folio_id' => $folio->id,
            'amount' => 2000,
        ]
    );

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data' => [
            'payment',
            'payment_intent_id',
            'client_secret',
        ],
    ]);

    $response->assertJsonPath(
        'data.payment.method',
        'stripe'
    );

    $response->assertJsonPath(
        'data.payment.provider',
        'stripe'
    );

    $response->assertJsonPath(
        'data.payment.status',
        'pending'
    );

    $this->assertDatabaseHas('payments', [
        'folio_id' => $folio->id,
        'amount' => 2000,
        'method' => 'stripe',
        'provider' => 'stripe',
        'status' => 'pending',
    ]);
});

test('user cannot create a Stripe payment intent', function () {

    actingAsStripeUser('user');

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->postJson(
        '/api/v1/stripe/payment-intent',
        [
            'folio_id' => $folio->id,
            'amount' => 1000,
        ]
    );

    $response->assertStatus(403);
});

test('unauthenticated user cannot create a Stripe payment intent', function () {

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->postJson(
        '/api/v1/stripe/payment-intent',
        [
            'folio_id' => $folio->id,
            'amount' => 1000,
        ]
    );

    $response->assertStatus(401);
});

test('Stripe payment requires a folio', function () {

    actingAsStripeUser('receptionist');

    $response = $this->postJson(
        '/api/v1/stripe/payment-intent',
        [
            'amount' => 1000,
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'folio_id',
    ]);
});

test('Stripe payment requires a valid folio', function () {

    actingAsStripeUser('receptionist');

    $response = $this->postJson(
        '/api/v1/stripe/payment-intent',
        [
            'folio_id' => 999999,
            'amount' => 1000,
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'folio_id',
    ]);
});

test('Stripe payment amount must be greater than zero', function () {

    actingAsStripeUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->postJson(
        '/api/v1/stripe/payment-intent',
        [
            'folio_id' => $folio->id,
            'amount' => 0,
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'amount',
    ]);
});

test('Stripe payment cannot be created for a closed folio', function () {

    actingAsStripeUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'closed',
    ]);

    $response = $this->postJson(
        '/api/v1/stripe/payment-intent',
        [
            'folio_id' => $folio->id,
            'amount' => 1000,
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'folio_id',
    ]);
});

test('Stripe payment cannot exceed the outstanding balance', function () {

    actingAsStripeUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $folio->charges()->create([
        'amount' => 5000,
        'quantity' => 1,
        'unit_price' => 5000,
        'type' => 'accommodation',
        'description' => 'Room charge',
        'charged_at' => now(),
        'charged_by' => auth()->id(),
    ]);

    $response = $this->postJson(
        '/api/v1/stripe/payment-intent',
        [
            'folio_id' => $folio->id,
            'amount' => 6000,
        ]
    );

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'amount',
    ]);
});

test('Stripe webhook completes a pending payment', function () {

    $payment = Payment::factory()->create([
        'method' => 'stripe',
        'provider' => 'stripe',
        'status' => 'pending',
        'transaction_reference' => 'pi_test_success',
    ]);

    config([
        'services.stripe.webhook_secret' => 'whsec_test',
    ]);

    $payload = json_encode([
        'id' => 'evt_test_success',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_success',
                'object' => 'payment_intent',
            ],
        ],
    ]);

    $signature = stripeTestSignature(
        $payload,
        'whsec_test'
    );

    $response = $this->call(
        'POST',
        '/api/v1/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $response->assertNoContent();

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'completed',
    ]);

    $this->assertDatabaseHas('receipts', [
        'payment_id' => $payment->id,
    ]);
});

test('Stripe webhook does not create a duplicate receipt', function () {

    $payment = Payment::factory()->create([
        'method' => 'stripe',
        'provider' => 'stripe',
        'status' => 'pending',
        'transaction_reference' => 'pi_test_duplicate',
    ]);

    config([
        'services.stripe.webhook_secret' => 'whsec_test',
    ]);

    $payload = json_encode([
        'id' => 'evt_test_duplicate',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_duplicate',
                'object' => 'payment_intent',
            ],
        ],
    ]);

    $signature = stripeTestSignature(
        $payload,
        'whsec_test'
    );

    $this->call(
        'POST',
        '/api/v1/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $this->call(
        'POST',
        '/api/v1/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    expect(
        Receipt::where('payment_id', $payment->id)->count()
    )->toBe(1);
});

test('Stripe webhook marks a payment as failed', function () {

    $payment = Payment::factory()->create([
        'method' => 'stripe',
        'provider' => 'stripe',
        'status' => 'pending',
        'transaction_reference' => 'pi_test_failed',
    ]);

    config([
        'services.stripe.webhook_secret' => 'whsec_test',
    ]);

    $payload = json_encode([
        'id' => 'evt_test_failed',
        'object' => 'event',
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id' => 'pi_test_failed',
                'object' => 'payment_intent',
            ],
        ],
    ]);

    $signature = stripeTestSignature(
        $payload,
        'whsec_test'
    );

    $response = $this->call(
        'POST',
        '/api/v1/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $response->assertNoContent();

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'failed',
    ]);
});

test('Stripe webhook rejects an invalid signature', function () {

    config([
        'services.stripe.webhook_secret' => 'whsec_test',
    ]);

    $payload = json_encode([
        'id' => 'evt_invalid',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_invalid',
                'object' => 'payment_intent',
            ],
        ],
    ]);

    $response = $this->call(
        'POST',
        '/api/v1/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 'invalid-signature',
        ],
        $payload
    );

    $response->assertStatus(422);
});
