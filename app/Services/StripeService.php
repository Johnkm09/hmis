<?php

namespace App\Services;

use App\Models\Folio\Folio;
use App\Models\Payment\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $stripe;

    public function __construct(?StripeClient $stripe = null)
    {
        $this->stripe = $stripe ?? new StripeClient([
            'api_key' => config('services.stripe.secret'),
        ]);
    }

    public function createPaymentIntent(array $data)
    {
        $payment = DB::transaction(function () use ($data) {
            $folio = Folio::whereKey($data['folio_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($folio->status === 'closed') {
                throw ValidationException::withMessages([
                    'folio_id' =>
                    'Cannot accept payment for a closed folio.',
                ]);
            }

            $charges = $folio->charges()->sum('amount');

            $paid = $folio->payments()
                ->where('status', 'completed')
                ->sum('amount');

            $outstanding = (float) $charges - (float) $paid;

            if ((float) $data['amount'] > $outstanding) {
                throw ValidationException::withMessages([
                    'amount' =>
                    'Payment exceeds the outstanding balance.',
                ]);
            }

            if ((float) $data['amount'] <= 0) {
                throw ValidationException::withMessages([
                    'amount' =>
                    'Payment amount must be greater than zero.',
                ]);
            }

            $pendingPayment = $folio->payments()
                ->where('method', 'stripe')
                ->where('status', 'pending')
                ->exists();

            if ($pendingPayment) {
                throw ValidationException::withMessages([
                    'folio_id' =>
                    'There is already a pending Stripe payment for this folio.',
                ]);
            }

            return Payment::create([
                'folio_id' => $folio->id,
                'amount' => $data['amount'],
                'method' => 'stripe',
                'provider' => 'stripe',
                'status' => 'pending',
            ]);
        });

        try {
            /*
             * Stripe expects the amount in the currency's
             * smallest unit.
             *
             * KES 1,000.00 becomes 100000.
             */
            $amount = (int) round(
                (float) $payment->amount * 100
            );

            $paymentIntent = $this->stripe->paymentIntents->create(
                [
                    'amount' => $amount,
                    'currency' => 'kes',
                    'metadata' => [
                        'payment_id' => $payment->id,
                        'folio_id' => $payment->folio_id,
                    ],
                    'automatic_payment_methods' => [
                        'enabled' => true,
                    ],
                ],
                [
                    'idempotency_key' =>
                    'hmis-payment-' . $payment->id,
                ]
            );

            $payment->update([
                'transaction_reference' =>
                $paymentIntent->id,
            ]);

            return [
                'payment' => $payment->fresh(),
                'payment_intent_id' =>
                $paymentIntent->id,
                'client_secret' =>
                $paymentIntent->client_secret,
            ];
        } catch (\Throwable $exception) {
            Log::error(
                'Stripe PaymentIntent creation failed.',
                [
                    'payment_id' => $payment->id,
                    'folio_id' => $payment->folio_id,
                    'error' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }

    public function retrievePaymentIntent(
        Payment $payment
    ) {
        if ($payment->method !== 'stripe') {
            throw ValidationException::withMessages([
                'payment_id' =>
                'Only Stripe payments can be retrieved.',
            ]);
        }

        if (!$payment->transaction_reference) {
            throw ValidationException::withMessages([
                'payment_id' =>
                'Payment does not have a Stripe PaymentIntent ID.',
            ]);
        }

        return $this->stripe->paymentIntents->retrieve(
            $payment->transaction_reference
        );
    }

    public function handleWebhook(
        string $payload,
        ?string $signature
    ): void {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature ?? '',
                config('services.stripe.webhook_secret')
            );
        } catch (\UnexpectedValueException $exception) {
            throw ValidationException::withMessages([
                'webhook' => 'Invalid Stripe webhook payload.',
            ]);
        } catch (\Stripe\Exception\SignatureVerificationException $exception) {
            throw ValidationException::withMessages([
                'webhook' => 'Invalid Stripe webhook signature.',
            ]);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->completePaymentFromWebhook(
                    $event->data->object
                );
                break;

            case 'payment_intent.payment_failed':
                $this->failPaymentFromWebhook(
                    $event->data->object
                );
                break;
        }
    }

    private function completePaymentFromWebhook(
        \Stripe\PaymentIntent $paymentIntent
    ): void {
        DB::transaction(function () use ($paymentIntent) {
            $payment = Payment::where(
                'transaction_reference',
                $paymentIntent->id
            )
                ->lockForUpdate()
                ->first();

            if (!$payment || $payment->status !== 'pending') {
                return;
            }

            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            app(ReceiptService::class)->createForPayment(
                $payment->fresh(),
                null
            );
        });
    }

    private function failPaymentFromWebhook(
        \Stripe\PaymentIntent $paymentIntent
    ): void {
        Payment::where(
            'transaction_reference',
            $paymentIntent->id
        )
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'paid_at' => null,
            ]);
    }
}
