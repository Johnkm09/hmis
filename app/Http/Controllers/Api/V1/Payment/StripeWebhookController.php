<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    public function handle(Request $request): Response
    {
        $this->stripeService->handleWebhook(
            $request->getContent(),
            $request->header('Stripe-Signature')
        );

        return response()->noContent();
    }
}
