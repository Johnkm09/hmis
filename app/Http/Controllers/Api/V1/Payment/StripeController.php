<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\StripePaymentIntentRequest;
use App\Models\Payment\Payment;
use App\Services\StripeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StripeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Create a Stripe PaymentIntent.
     *
     * @group Stripe Payments
     *
     * @bodyParam folio_id integer required The ID of the folio.
     * @bodyParam amount number required The payment amount in KES.
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Stripe PaymentIntent created successfully",
     *   "data": {
     *     "payment": {},
     *     "payment_intent_id": "pi_test_123",
     *     "client_secret": "pi_test_123_secret"
     *   }
     * }
     */
    public function createPaymentIntent(
        StripePaymentIntentRequest $request
    ): JsonResponse {
        $this->authorize('create', Payment::class);

        $result = $this->stripeService->createPaymentIntent(
            $request->validated()
        );

        return ApiResponse::success(
            $result,
            'Stripe PaymentIntent created successfully',
            200
        );
    }

    /**
     * Handle Stripe webhook events.
     *
     * @group Stripe Payments
     *
     * @bodyParam id string required Stripe event ID.
     * @bodyParam type string required Stripe event type.
     *
     * @response 204 {}
     * @response 422 {
     *   "status": "error",
     *   "message": "Invalid Stripe webhook signature.",
     *   "errors": {}
     * }
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $this->stripeService->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature')
            );

            return response()->json([], 204);
        } catch (ValidationException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                422,
                $exception->errors()
            );
        }
    }
}
