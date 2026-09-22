<?php

use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Models\Payment\Payment;
use App\Models\Payment\Receipt;
use App\Models\User;
use App\Services\MpesaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    Permission::firstOrCreate([
        'name' => 'create payments',
        'guard_name' => 'web',
    ]);

    Permission::firstOrCreate([
        'name' => 'view payments',
        'guard_name' => 'web',
    ]);

    $this->user->givePermissionTo('create payments');
    $this->user->givePermissionTo('view payments');

    config([
        'services.mpesa.base_url' =>
        'https://sandbox.safaricom.co.ke',

        'services.mpesa.consumer_key' =>
        'test-consumer-key',

        'services.mpesa.consumer_secret' =>
        'test-consumer-secret',

        'services.mpesa.shortcode' =>
        '174379',

        'services.mpesa.passkey' =>
        'test-passkey',

        'services.mpesa.callback_url' =>
        'https://example.com/api/v1/mpesa/callback',
    ]);
});

test('authenticated user can initiate an mpesa stk push', function () {
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' =>
        Http::response([
            'access_token' => 'test-access-token',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' =>
        Http::response([
            'MerchantRequestID' => 'merchant-123',
            'CheckoutRequestID' => 'checkout-123',
            'ResponseCode' => '0',
            'ResponseDescription' => 'Success',
        ], 200),
    ]);

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'quantity' => 1,
        'unit_price' => 1000,
        'amount' => 1000,
    ]);

    $response = $this
        ->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/mpesa/stk-push', [
            'folio_id' => $folio->id,
            'amount' => 1000,
            'phone' => '254712345678',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath(
            'data.CheckoutRequestID',
            'checkout-123'
        );

    $this->assertDatabaseHas('payments', [
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'mpesa_phone' => '254712345678',
        'mpesa_merchant_request_id' => 'merchant-123',
        'mpesa_checkout_request_id' => 'checkout-123',
    ]);
});

test('mpesa callback completes a pending payment', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'quantity' => 1,
        'unit_price' => 1000,
        'amount' => 1000,
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'paid_at' => null,
        'mpesa_phone' => '254712345678',
        'mpesa_merchant_request_id' =>
        '29115-34620561-1',
        'mpesa_checkout_request_id' =>
        'ws_CO_191220191020363925',
    ]);

    $response = $this->postJson(
        '/api/v1/mpesa/callback',
        [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' =>
                    '29115-34620561-1',

                    'CheckoutRequestID' =>
                    'ws_CO_191220191020363925',

                    'ResultCode' => 0,

                    'ResultDesc' =>
                    'The service request is processed successfully.',

                    'CallbackMetadata' => [
                        'Item' => [
                            [
                                'Name' => 'Amount',
                                'Value' => 1000,
                            ],
                            [
                                'Name' =>
                                'MpesaReceiptNumber',
                                'Value' =>
                                'QWE1234567',
                            ],
                            [
                                'Name' =>
                                'TransactionDate',
                                'Value' =>
                                20260921180000,
                            ],
                            [
                                'Name' => 'PhoneNumber',
                                'Value' =>
                                254712345678,
                            ],
                        ],
                    ],
                ],
            ],
        ]
    );

    $response
        ->assertOk()
        ->assertJson([
            'ResultCode' => 0,
            'ResultDesc' =>
            'Callback received successfully.',
        ]);

    $payment->refresh();

    expect($payment->status)
        ->toBe('completed')
        ->and($payment->transaction_reference)
        ->toBe('QWE1234567')
        ->and($payment->mpesa_phone)
        ->toBe('254712345678')
        ->and($payment->paid_at)
        ->not->toBeNull();

    expect(
        Receipt::where('payment_id', $payment->id)->exists()
    )->toBeTrue();
});

test('mpesa callback marks a pending payment as failed when payment is unsuccessful', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'paid_at' => null,
        'transaction_reference' => null,
        'mpesa_merchant_request_id' =>
        'failed-merchant-123',
        'mpesa_checkout_request_id' =>
        'failed-checkout-123',
    ]);

    $response = $this->postJson(
        '/api/v1/mpesa/callback',
        [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' =>
                    'failed-merchant-123',

                    'CheckoutRequestID' =>
                    'failed-checkout-123',

                    'ResultCode' => 1032,

                    'ResultDesc' =>
                    'Request cancelled by user.',
                ],
            ],
        ]
    );

    $response->assertOk();

    $payment->refresh();

    expect($payment->status)
        ->toBe('failed')
        ->and($payment->paid_at)
        ->toBeNull()
        ->and($payment->transaction_reference)
        ->toBeNull();
});

test('mpesa callback does not process an already completed payment again', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'completed',
        'transaction_reference' => 'OLD123456',
        'mpesa_merchant_request_id' =>
        'duplicate-merchant-123',
        'mpesa_checkout_request_id' =>
        'duplicate-checkout-123',
        'paid_at' => now()->subMinute(),
    ]);

    $response = $this->postJson(
        '/api/v1/mpesa/callback',
        [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' =>
                    'duplicate-merchant-123',

                    'CheckoutRequestID' =>
                    'duplicate-checkout-123',

                    'ResultCode' => 0,

                    'ResultDesc' => 'Success.',

                    'CallbackMetadata' => [
                        'Item' => [
                            [
                                'Name' => 'Amount',
                                'Value' => 1000,
                            ],
                            [
                                'Name' =>
                                'MpesaReceiptNumber',
                                'Value' => 'NEW123456',
                            ],
                            [
                                'Name' => 'PhoneNumber',
                                'Value' =>
                                254712345678,
                            ],
                        ],
                    ],
                ],
            ],
        ]
    );

    $response->assertOk();

    $payment->refresh();

    expect($payment->status)
        ->toBe('completed')
        ->and($payment->transaction_reference)
        ->toBe('OLD123456');
});

test('mpesa callback ignores an unknown checkout request', function () {
    $response = $this->postJson(
        '/api/v1/mpesa/callback',
        [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' =>
                    'unknown-merchant-123',

                    'CheckoutRequestID' =>
                    'unknown-checkout-123',

                    'ResultCode' => 0,

                    'ResultDesc' => 'Success.',
                ],
            ],
        ]
    );

    $response
        ->assertOk()
        ->assertJson([
            'ResultCode' => 0,
        ]);

    expect(
        Payment::where(
            'mpesa_checkout_request_id',
            'unknown-checkout-123'
        )->exists()
    )->toBeFalse();
});

test('mpesa callback safely ignores a malformed payload', function () {
    $response = $this->postJson(
        '/api/v1/mpesa/callback',
        [
            'invalid' => 'payload',
        ]
    );

    $response
        ->assertOk()
        ->assertJson([
            'ResultCode' => 0,
            'ResultDesc' =>
            'Callback received successfully.',
        ]);
});

test('mpesa callback does not complete payment when callback amount does not match', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'paid_at' => null,
        'transaction_reference' => null,
        'mpesa_merchant_request_id' =>
        'amount-mismatch-merchant-123',
        'mpesa_checkout_request_id' =>
        'amount-mismatch-123',
    ]);

    $response = $this->postJson(
        '/api/v1/mpesa/callback',
        [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' =>
                    'amount-mismatch-merchant-123',

                    'CheckoutRequestID' =>
                    'amount-mismatch-123',

                    'ResultCode' => 0,

                    'ResultDesc' => 'Success.',

                    'CallbackMetadata' => [
                        'Item' => [
                            [
                                'Name' => 'Amount',
                                'Value' => 500,
                            ],
                            [
                                'Name' =>
                                'MpesaReceiptNumber',
                                'Value' =>
                                'MISMATCH123',
                            ],
                        ],
                    ],
                ],
            ],
        ]
    );

    $response->assertOk();

    $payment->refresh();

    expect($payment->status)
        ->toBe('pending')
        ->and($payment->transaction_reference)
        ->toBeNull()
        ->and($payment->paid_at)
        ->toBeNull();

    expect(
        Receipt::where('payment_id', $payment->id)->exists()
    )->toBeFalse();
});

test('mpesa callback ignores a merchant request ID mismatch', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'quantity' => 1,
        'unit_price' => 1000,
        'amount' => 1000,
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'paid_at' => null,
        'transaction_reference' => null,
        'mpesa_merchant_request_id' =>
        'merchant-123',
        'mpesa_checkout_request_id' =>
        'checkout-123',
    ]);

    $response = $this->postJson(
        '/api/v1/mpesa/callback',
        [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' =>
                    'different-merchant',

                    'CheckoutRequestID' =>
                    'checkout-123',

                    'ResultCode' => 0,

                    'ResultDesc' => 'Success.',

                    'CallbackMetadata' => [
                        'Item' => [
                            [
                                'Name' => 'Amount',
                                'Value' => 1000,
                            ],
                            [
                                'Name' =>
                                'MpesaReceiptNumber',
                                'Value' =>
                                'MISMATCH123',
                            ],
                            [
                                'Name' => 'PhoneNumber',
                                'Value' =>
                                254712345678,
                            ],
                        ],
                    ],
                ],
            ],
        ]
    );

    $response->assertOk();

    $payment->refresh();

    expect($payment->status)
        ->toBe('pending')
        ->and($payment->transaction_reference)
        ->toBeNull()
        ->and($payment->paid_at)
        ->toBeNull();

    expect(
        Receipt::where('payment_id', $payment->id)->exists()
    )->toBeFalse();
});

test('mpesa does not create another stk push when a folio already has a pending mpesa payment', function () {
    Http::fake();

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'quantity' => 1,
        'unit_price' => 1000,
        'amount' => 1000,
    ]);

    Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'mpesa_phone' => '254712345678',
        'mpesa_checkout_request_id' =>
        'existing-checkout-123',
    ]);

    $response = $this
        ->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/mpesa/stk-push', [
            'folio_id' => $folio->id,
            'amount' => 1000,
            'phone' => '254712345678',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('folio_id');

    Http::assertNothingSent();

    expect(
        Payment::where('folio_id', $folio->id)->count()
    )->toBe(1);
});

test('mpesa does not allow a payment greater than the outstanding balance', function () {
    Http::fake();

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'quantity' => 1,
        'unit_price' => 1000,
        'amount' => 1000,
    ]);

    $response = $this
        ->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/mpesa/stk-push', [
            'folio_id' => $folio->id,
            'amount' => 1001,
            'phone' => '254712345678',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    Http::assertNothingSent();

    expect(
        Payment::where('folio_id', $folio->id)->count()
    )->toBe(0);
});

test('mpesa callback is safe when the same callback is processed twice', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'mpesa_merchant_request_id' =>
        'idempotent-merchant-123',
        'mpesa_checkout_request_id' =>
        'idempotent-checkout-123',
    ]);

    $payload = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' =>
                'idempotent-merchant-123',

                'CheckoutRequestID' =>
                'idempotent-checkout-123',

                'ResultCode' => 0,

                'ResultDesc' => 'Success.',

                'CallbackMetadata' => [
                    'Item' => [
                        [
                            'Name' => 'Amount',
                            'Value' => 1000,
                        ],
                        [
                            'Name' =>
                            'MpesaReceiptNumber',
                            'Value' => 'IDEMP123456',
                        ],
                        [
                            'Name' => 'PhoneNumber',
                            'Value' => 254712345678,
                        ],
                    ],
                ],
            ],
        ],
    ];

    $this->postJson(
        '/api/v1/mpesa/callback',
        $payload
    )->assertOk();

    $payment->refresh();

    $paidAt = $payment->paid_at;

    $this->postJson(
        '/api/v1/mpesa/callback',
        $payload
    )->assertOk();

    $payment->refresh();

    expect($payment->status)
        ->toBe('completed')
        ->and($payment->transaction_reference)
        ->toBe('IDEMP123456')
        ->and($payment->paid_at->equalTo($paidAt))
        ->toBeTrue();

    expect(
        Receipt::where('payment_id', $payment->id)->count()
    )->toBe(1);
});

test('successful mpesa partial payment automatically creates a receipt', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'quantity' => 1,
        'unit_price' => 10000,
        'amount' => 10000,
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 4000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'mpesa_merchant_request_id' =>
        'partial-payment-merchant-123',
        'mpesa_checkout_request_id' =>
        'partial-payment-checkout-123',
        'paid_at' => null,
    ]);

    $response = $this->postJson(
        '/api/v1/mpesa/callback',
        [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' =>
                    'partial-payment-merchant-123',

                    'CheckoutRequestID' =>
                    'partial-payment-checkout-123',

                    'ResultCode' => 0,

                    'ResultDesc' => 'Success.',

                    'CallbackMetadata' => [
                        'Item' => [
                            [
                                'Name' => 'Amount',
                                'Value' => 4000,
                            ],
                            [
                                'Name' =>
                                'MpesaReceiptNumber',
                                'Value' => 'PARTIAL123',
                            ],
                            [
                                'Name' => 'PhoneNumber',
                                'Value' => 254712345678,
                            ],
                        ],
                    ],
                ],
            ],
        ]
    );

    $response->assertOk();

    $payment->refresh();

    expect($payment->status)
        ->toBe('completed');

    $this->assertDatabaseHas('receipts', [
        'payment_id' => $payment->id,
    ]);

    $receipt = $payment->receipt()->first();

    expect($receipt)
        ->not->toBeNull()
        ->and($receipt->payment_id)
        ->toBe($payment->id)
        ->and($receipt->issued_by)
        ->toBeNull();
});

test('mpesa payment only receives one receipt', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 5000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'completed',
        'transaction_reference' => 'RECEIPT123',
        'mpesa_checkout_request_id' =>
        'receipt-checkout-123',
        'paid_at' => now(),
    ]);

    app(\App\Services\ReceiptService::class)
        ->createForPayment($payment, null);

    expect(
        fn() =>
        app(\App\Services\ReceiptService::class)
            ->createForPayment($payment, null)
    )->toThrow(
        ValidationException::class
    );

    expect(
        $payment->receipt()->exists()
    )->toBeTrue();

    expect(
        Receipt::where('payment_id', $payment->id)->count()
    )->toBe(1);
});

test('mpesa stk push normalizes a Kenyan phone number', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'quantity' => 1,
        'unit_price' => 5000,
        'amount' => 5000,
    ]);

    $user = User::factory()->create();

    $user->givePermissionTo('create payments');

    $this->actingAs($user, 'sanctum');

    Http::fake([
        '*' => Http::sequence()
            ->push([
                'access_token' => 'test-token',
            ], 200)
            ->push([
                'MerchantRequestID' => 'merchant-123',
                'CheckoutRequestID' => 'checkout-123',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200),
    ]);

    $response = $this->postJson('/api/v1/mpesa/stk-push', [
        'folio_id' => $folio->id,
        'amount' => 5000,
        'phone' => '0712345678',
    ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('payments', [
        'folio_id' => $folio->id,
        'mpesa_phone' => '254712345678',
    ]);
});

test('mpesa stk push rejects an invalid phone number', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $user = User::factory()->create();

    $user->givePermissionTo('create payments');

    $this->actingAs($user, 'sanctum');

    $response = $this->postJson('/api/v1/mpesa/stk-push', [
        'folio_id' => $folio->id,
        'amount' => 5000,
        'phone' => '123456789',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors('phone');

    expect(
        Payment::where('folio_id', $folio->id)->count()
    )->toBe(0);
});

test('mpesa stk push query completes a pending payment', function () {
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' =>
        Http::response([
            'access_token' => 'test-access-token',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' =>
        Http::response([
            'ResponseCode' => '0',
            'ResponseDescription' =>
            'The service request has been accepted successfully',
            'MerchantRequestID' =>
            'query-merchant-123',
            'CheckoutRequestID' =>
            'query-checkout-123',
            'ResultCode' => '0',
            'ResultDesc' =>
            'The service request is processed successfully.',
        ], 200),
    ]);

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'mpesa_checkout_request_id' =>
        'query-checkout-123',
        'paid_at' => null,
        'transaction_reference' => null,
    ]);

    $result = app(MpesaService::class)
        ->queryStkPush($payment);

    $payment->refresh();

    expect($result['status'])
        ->toBe('completed')
        ->and($payment->status)
        ->toBe('completed')
        ->and($payment->paid_at)
        ->not->toBeNull()
        ->and($payment->transaction_reference)
        ->toBeNull();
});

test('mpesa stk push query marks a pending payment as failed', function () {
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' =>
        Http::response([
            'access_token' => 'test-access-token',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' =>
        Http::response([
            'ResponseCode' => '0',
            'ResponseDescription' =>
            'The service request has been accepted successfully',
            'ResultCode' => '1032',
            'ResultDesc' =>
            'Request cancelled by user.',
        ], 200),
    ]);

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'mpesa_checkout_request_id' =>
        'query-failed-checkout-123',
        'paid_at' => null,
    ]);

    $result = app(MpesaService::class)
        ->queryStkPush($payment);

    $payment->refresh();

    expect($result['status'])
        ->toBe('failed')
        ->and($payment->status)
        ->toBe('failed')
        ->and($payment->paid_at)
        ->toBeNull();
});

test('mpesa stk push query leaves an unresolved payment pending', function () {
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' =>
        Http::response([
            'access_token' => 'test-access-token',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' =>
        Http::response([
            'ResponseCode' => '0',
            'ResponseDescription' =>
            'The service request has been accepted successfully',
        ], 200),
    ]);

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'mpesa_checkout_request_id' =>
        'query-pending-checkout-123',
        'paid_at' => null,
    ]);

    $result = app(MpesaService::class)
        ->queryStkPush($payment);

    $payment->refresh();

    expect($result['status'])
        ->toBe('pending')
        ->and($payment->status)
        ->toBe('pending')
        ->and($payment->paid_at)
        ->toBeNull();
});

test('mpesa stk push query rejects a payment without checkout request ID', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'mpesa_checkout_request_id' => null,
    ]);

    expect(
        fn() =>
        app(MpesaService::class)
            ->queryStkPush($payment)
    )->toThrow(
        ValidationException::class
    );

    Http::assertNothingSent();
});

test('mpesa stk push query rejects a non mpesa payment', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'cash',
        'status' => 'pending',
        'mpesa_checkout_request_id' =>
        'cash-checkout-123',
    ]);

    expect(
        fn() =>
        app(MpesaService::class)
            ->queryStkPush($payment)
    )->toThrow(
        ValidationException::class
    );

    Http::assertNothingSent();
});

test('mpesa stk push query does not modify an already completed payment', function () {
    Http::fake();

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'completed',
        'transaction_reference' => 'ACTUAL123',
        'mpesa_checkout_request_id' =>
        'completed-checkout-123',
        'paid_at' => now()->subMinute(),
    ]);

    $paidAt = $payment->paid_at;

    $result = app(MpesaService::class)
        ->queryStkPush($payment);

    $payment->refresh();

    expect($result['status'])
        ->toBe('completed')
        ->and($payment->status)
        ->toBe('completed')
        ->and($payment->transaction_reference)
        ->toBe('ACTUAL123')
        ->and($payment->paid_at->equalTo($paidAt))
        ->toBeTrue();

    Http::assertNothingSent();
});

test('successful mpesa stk push query automatically creates a receipt', function () {
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' =>
        Http::response([
            'access_token' => 'test-access-token',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' =>
        Http::response([
            'ResponseCode' => '0',
            'ResponseDescription' =>
            'The service request has been accepted successfully',
            'ResultCode' => '0',
            'ResultDesc' =>
            'The service request is processed successfully.',
        ], 200),
    ]);

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 5000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'mpesa_checkout_request_id' =>
        'query-receipt-checkout-123',
        'paid_at' => null,
    ]);

    $result = app(MpesaService::class)
        ->queryStkPush($payment);

    $payment->refresh();

    expect($result['status'])
        ->toBe('completed');

    $this->assertDatabaseHas('receipts', [
        'payment_id' => $payment->id,
    ]);

    expect(
        Receipt::where('payment_id', $payment->id)->count()
    )->toBe(1);

    expect($payment->receipt()->exists())
        ->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| M-Pesa API Authorization Tests
|--------------------------------------------------------------------------
*/

test('user without create payments permission cannot initiate an mpesa stk push', function () {
    Http::fake();

    $user = User::factory()->create();

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    FolioCharge::factory()->create([
        'folio_id' => $folio->id,
        'quantity' => 1,
        'unit_price' => 1000,
        'amount' => 1000,
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/mpesa/stk-push', [
            'folio_id' => $folio->id,
            'amount' => 1000,
            'phone' => '254712345678',
        ]);

    $response->assertForbidden();

    Http::assertNothingSent();

    expect(
        Payment::where('folio_id', $folio->id)->count()
    )->toBe(0);
});

test('user without view payments permission cannot query an mpesa payment', function () {
    Http::fake();

    $user = User::factory()->create();

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $payment = Payment::factory()->create([
        'folio_id' => $folio->id,
        'amount' => 1000,
        'method' => 'mpesa',
        'provider' => 'safaricom',
        'status' => 'pending',
        'mpesa_checkout_request_id' =>
        'authorization-query-123',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/mpesa/query/{$payment->id}"
        );

    $response->assertForbidden();

    Http::assertNothingSent();

    $payment->refresh();

    expect($payment->status)
        ->toBe('pending');
});

test('mpesa callback remains publicly accessible without authentication', function () {
    $response = $this->postJson(
        '/api/v1/mpesa/callback',
        [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' =>
                    'public-callback-merchant-123',

                    'CheckoutRequestID' =>
                    'public-callback-checkout-123',

                    'ResultCode' => 1,

                    'ResultDesc' =>
                    'Request cancelled by user.',
                ],
            ],
        ]
    );

    $response
        ->assertOk()
        ->assertJson([
            'ResultCode' => 0,
            'ResultDesc' =>
            'Callback received successfully.',
        ]);
});
