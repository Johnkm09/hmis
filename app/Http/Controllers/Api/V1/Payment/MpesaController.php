<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\MpesaStkPushRequest;
use App\Models\Payment\Payment;
use App\Services\MpesaService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MpesaController extends Controller
{
    use AuthorizesRequests;

    protected MpesaService $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    /**
     * Initiate an M-Pesa STK Push payment.
     *
     * @group M-Pesa Payments
     *
     * @bodyParam folio_id integer required The ID of the folio.
     * @bodyParam amount number required The payment amount.
     * @bodyParam phone string required The Kenyan phone number to receive the STK prompt.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "M-Pesa payment request sent successfully.",
     *   "data": {}
     * }
     */
    public function stkPush(
        MpesaStkPushRequest $request
    ): JsonResponse {
        $this->authorize('create', Payment::class);

        $response = $this->mpesaService->stkPush(
            $request->validated()
        );

        return ApiResponse::success(
            $response,
            'M-Pesa payment request sent successfully.'
        );
    }

    /**
     * Query the status of an M-Pesa STK Push payment.
     *
     * @group M-Pesa Payments
     *
     * @urlParam payment integer required The payment ID.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "M-Pesa payment status retrieved successfully.",
     *   "data": {}
     * }
     */
    public function query(string $payment): JsonResponse
    {
        $paymentModel = Payment::findOrFail(
            (int) $payment
        );

        $this->authorize('view', $paymentModel);

        $response = $this->mpesaService->queryStkPush(
            $paymentModel
        );

        return ApiResponse::success(
            $response,
            'M-Pesa payment status retrieved successfully.'
        );
    }

    /**
     * Receive the M-Pesa STK Push callback.
     *
     * This endpoint is called by Safaricom after the
     * customer completes or cancels the STK transaction.
     *
     * @group M-Pesa Payments
     *
     * @bodyParam Body object required The M-Pesa callback payload.
     *
     * @response 200 {
     *   "ResultCode": 0,
     *   "ResultDesc": "Callback received successfully."
     * }
     */
    public function callback(Request $request): JsonResponse
    {
        $this->mpesaService->processCallback(
            $request->all()
        );

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback received successfully.',
        ]);
    }
}
