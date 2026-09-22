<?php

namespace App\Services;

use App\Models\Folio\Folio;
use App\Models\Payment\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MpesaService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.mpesa.base_url');
    }

    public function generateAccessToken()
    {
        $response = Http::withBasicAuth(
            config('services.mpesa.consumer_key'),
            config('services.mpesa.consumer_secret')
        )->get(
            $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials'
        );

        $response->throw();

        return $response->json('access_token');
    }

    public function stkPush(array $data)
    {
        /*
         * Create the pending payment inside a short transaction.
         *
         * Locking the folio prevents two simultaneous requests
         * from both seeing the same outstanding balance and
         * creating two pending M-Pesa payments.
         */
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

            /*
             * Only one active M-Pesa STK request is allowed
             * for a folio at a time.
             *
             * This protects against double-clicks and prevents
             * creating two separate Safaricom transactions
             * before the first one has been resolved.
             */
            $pendingPayment = $folio->payments()
                ->where('method', 'mpesa')
                ->where('status', 'pending')
                ->exists();

            if ($pendingPayment) {
                throw ValidationException::withMessages([
                    'folio_id' =>
                    'There is already a pending M-Pesa payment for this folio.',
                ]);
            }

            $charges = $folio->charges()->sum('amount');

            $paid = $folio->payments()
                ->where('status', 'completed')
                ->sum('amount');

            $outstanding = $charges - $paid;

            if ((float) $data['amount'] > (float) $outstanding) {
                throw ValidationException::withMessages([
                    'amount' =>
                    'Payment exceeds the outstanding balance.',
                ]);
            }

            return Payment::create([
                'folio_id' => $data['folio_id'],
                'amount' => $data['amount'],
                'method' => 'mpesa',
                'provider' => 'safaricom',
                'status' => 'pending',
                'mpesa_phone' => $data['phone'],
            ]);
        });

        /*
         * Never hold the database transaction open while
         * communicating with Safaricom.
         */
        try {
            $accessToken = $this->generateAccessToken();

            $timestamp = now()->format('YmdHis');

            $password = base64_encode(
                config('services.mpesa.shortcode') .
                    config('services.mpesa.passkey') .
                    $timestamp
            );

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post(
                    $this->baseUrl . '/mpesa/stkpush/v1/processrequest',
                    [
                        'BusinessShortCode' =>
                        config('services.mpesa.shortcode'),

                        'Password' => $password,

                        'Timestamp' => $timestamp,

                        'TransactionType' =>
                        'CustomerPayBillOnline',

                        'Amount' => $data['amount'],

                        'PartyA' => $data['phone'],

                        'PartyB' =>
                        config('services.mpesa.shortcode'),

                        'PhoneNumber' => $data['phone'],

                        'CallBackURL' =>
                        config('services.mpesa.callback_url'),

                        'AccountReference' =>
                        'Folio-' . $data['folio_id'],

                        'TransactionDesc' =>
                        'Hotel payment',
                    ]
                );

            $response->throw();

            $result = $response->json();

            $payment->update([
                'mpesa_merchant_request_id' =>
                $result['MerchantRequestID'] ?? null,

                'mpesa_checkout_request_id' =>
                $result['CheckoutRequestID'] ?? null,
            ]);

            return $result;
        } catch (\Throwable $exception) {
            /*
             * Do not mark the payment failed here.
             *
             * A timeout/network failure does not prove that
             * Safaricom did not receive the request.
             */
            Log::error('M-Pesa STK Push failed.', [
                'payment_id' => $payment->id,
                'folio_id' => $payment->folio_id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function queryStkPush(Payment $payment)
    {
        if ($payment->method !== 'mpesa') {
            throw ValidationException::withMessages([
                'payment_id' =>
                'Only M-Pesa payments can be queried.',
            ]);
        }

        if (!$payment->mpesa_checkout_request_id) {
            throw ValidationException::withMessages([
                'payment_id' =>
                'Payment does not have an M-Pesa checkout request ID.',
            ]);
        }

        /*
         * If the callback has already resolved the payment,
         * there is nothing left to reconcile.
         */
        if ($payment->status !== 'pending') {
            return [
                'status' => $payment->status,
                'payment' => $payment->fresh([
                    'receipt',
                ]),
            ];
        }

        $accessToken = $this->generateAccessToken();

        $timestamp = now()->format('YmdHis');

        $password = base64_encode(
            config('services.mpesa.shortcode') .
                config('services.mpesa.passkey') .
                $timestamp
        );

        $response = Http::withToken($accessToken)
            ->timeout(30)
            ->post(
                $this->baseUrl . '/mpesa/stkpushquery/v1/query',
                [
                    'BusinessShortCode' =>
                    config('services.mpesa.shortcode'),

                    'Password' => $password,

                    'Timestamp' => $timestamp,

                    'CheckoutRequestID' =>
                    $payment->mpesa_checkout_request_id,
                ]
            );

        $response->throw();

        $result = $response->json();

        /*
         * ResultCode 0 means Safaricom has confirmed that
         * the STK transaction was successful.
         */
        if (
            isset($result['ResultCode']) &&
            (int) $result['ResultCode'] === 0
        ) {
            return $this->completeQueriedPayment(
                $payment,
                $result
            );
        }

        /*
         * A non-zero result means the STK payment did not
         * complete successfully.
         *
         * We only resolve the payment when Safaricom gives us
         * an explicit result.
         */
        if (
            isset($result['ResultCode']) &&
            $result['ResultCode'] !== null
        ) {
            $payment->update([
                'status' => 'failed',
                'paid_at' => null,
            ]);

            return [
                'status' => 'failed',
                'payment' => $payment->fresh([
                    'receipt',
                ]),
                'response' => $result,
            ];
        }

        /*
         * If Safaricom does not give us a definitive result,
         * leave the payment pending.
         */
        return [
            'status' => 'pending',
            'payment' => $payment->fresh([
                'receipt',
            ]),
            'response' => $result,
        ];
    }

    private function completeQueriedPayment(Payment $payment, array $result)
    {
        return DB::transaction(function () use (
            $payment,
            $result
        ) {
            $payment = Payment::whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * The callback may have completed the payment while
             * the query was running.
             */
            if ($payment->status !== 'pending') {
                return [
                    'status' => $payment->status,
                    'payment' => $payment->fresh([
                        'receipt',
                    ]),
                    'response' => $result,
                ];
            }

            /*
             * The STK Push Query confirms the request succeeded,
             * but it does not provide the same M-Pesa receipt
             * metadata as the successful STK callback.
             *
             * Therefore we do not incorrectly store the
             * CheckoutRequestID as transaction_reference.
             */
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            /*
             * Automatically issue a receipt for the successfully
             * reconciled payment.
             *
             * issued_by is null because the payment was resolved
             * automatically rather than by an authenticated user.
             */
            app(ReceiptService::class)->createForPayment(
                $payment->fresh(),
                null
            );

            return [
                'status' => 'completed',
                'payment' => $payment->fresh([
                    'receipt',
                ]),
                'response' => $result,
            ];
        });
    }

    public function processCallback(array $payload)
    {
        $callback = $payload['Body']['stkCallback'] ?? null;

        if (!$callback) {
            return;
        }

        $checkoutRequestId =
            $callback['CheckoutRequestID'] ?? null;

        $merchantRequestId =
            $callback['MerchantRequestID'] ?? null;

        $resultCode =
            $callback['ResultCode'] ?? null;

        if (!$checkoutRequestId || !$merchantRequestId) {
            return;
        }

        /*
         * Lock the payment so two simultaneous callbacks cannot
         * both process the same pending payment.
         */
        DB::transaction(function () use (
            $checkoutRequestId,
            $merchantRequestId,
            $resultCode,
            $callback
        ) {
            $payment = Payment::where(
                'mpesa_checkout_request_id',
                $checkoutRequestId
            )
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return;
            }

            /*
             * Verify that the MerchantRequestID from the callback
             * belongs to the same STK request stored on the payment.
             */
            if (
                $payment->mpesa_merchant_request_id !==
                $merchantRequestId
            ) {
                Log::warning(
                    'M-Pesa callback merchant request ID mismatch.',
                    [
                        'payment_id' => $payment->id,
                        'checkout_request_id' =>
                        $checkoutRequestId,
                    ]
                );

                return;
            }

            /*
             * Idempotency protection.
             *
             * Once completed or failed, later duplicate callbacks
             * do nothing.
             */
            if ($payment->status !== 'pending') {
                return;
            }

            /*
             * Customer cancelled, declined, timed out, etc.
             */
            if ($resultCode !== 0) {
                $payment->update([
                    'status' => 'failed',
                    'paid_at' => null,
                ]);

                return;
            }

            $metadata = collect(
                $callback['CallbackMetadata']['Item'] ?? []
            )->keyBy('Name');

            $amount = $metadata
                ->get('Amount')['Value'] ?? null;

            $receiptNumber = $metadata
                ->get('MpesaReceiptNumber')['Value'] ?? null;

            $phoneNumber = $metadata
                ->get('PhoneNumber')['Value'] ?? null;

            /*
             * Successful callbacks must contain the essential
             * payment information.
             */
            if (
                $amount === null ||
                $receiptNumber === null
            ) {
                Log::warning(
                    'M-Pesa callback missing payment metadata.',
                    [
                        'payment_id' => $payment->id,
                        'checkout_request_id' =>
                        $checkoutRequestId,
                    ]
                );

                return;
            }

            /*
             * Never complete a payment if Safaricom reports
             * an amount different from the requested amount.
             */
            if ((float) $amount !== (float) $payment->amount) {
                Log::warning(
                    'M-Pesa callback amount mismatch.',
                    [
                        'payment_id' => $payment->id,
                        'expected_amount' => $payment->amount,
                        'received_amount' => $amount,
                        'checkout_request_id' =>
                        $checkoutRequestId,
                    ]
                );

                return;
            }

            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'transaction_reference' => $receiptNumber,
                'mpesa_phone' =>
                $phoneNumber ?? $payment->mpesa_phone,
            ]);

            /*
             * Automatically issue a receipt for every successful
             * M-Pesa payment, including partial payments.
             *
             * issued_by is null because the payment was completed
             * automatically by the M-Pesa callback rather than by
             * an authenticated staff member.
             */
            app(ReceiptService::class)->createForPayment(
                $payment->fresh(),
                null
            );
        });
    }
}
