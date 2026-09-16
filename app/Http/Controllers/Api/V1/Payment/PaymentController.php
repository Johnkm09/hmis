<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\StorePaymentRequest;
use App\Http\Requests\Api\V1\Payment\UpdatePaymentRequest;
use App\Http\Resources\Api\V1\Payment\PaymentResource;
use App\Models\Folio\Folio;
use App\Models\Payment\Payment;
use App\Services\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display a list of payments for a folio.
     *
     * @group Payments
     *
     * @urlParam folio integer required The ID of the folio.
     *
     * @response 200 {
     *   "data": []
     * }
     */
    public function index(Folio $folio)
    {
        $this->authorize('viewAny', Payment::class);

        $payments = $this->paymentService->getByFolio(
            $folio->id
        );

        return ApiResponse::success(
            PaymentResource::collection($payments),
            'Payments retrieved successfully'
        );
    }

    /**
     * Record a payment against a folio.
     *
     * @group Payments
     *
     * @urlParam folio integer required The ID of the folio.
     *
     * @bodyParam amount number required Payment amount.
     * @bodyParam method string required Payment method. Example: mpesa
     * @bodyParam provider string Optional payment provider.
     * @bodyParam transaction_reference string Optional transaction reference.
     * @bodyParam status string required Payment status. Example: completed
     * @bodyParam notes string Optional payment notes.
     *
     * @response 201 {
     *   "data": {}
     * }
     */
    public function store(
        StorePaymentRequest $request,
        Folio $folio
    ) {
        $this->authorize('create', Payment::class);

        $data = $request->validated();

        $data['folio_id'] = $folio->id;
        $data['received_by'] = Auth::id();

        $payment = $this->paymentService->create($data);

        return ApiResponse::success(
            new PaymentResource($payment),
            'Payment created successfully',
            201
        );
    }

    /**
     * Display a payment.
     *
     * @group Payments
     *
     * @urlParam id integer required The payment ID.
     *
     * @response 200 {
     *   "data": {}
     * }
     */
    public function show(string $id)
    {
        $payment = $this->paymentService->findById(
            (int) $id
        );

        $this->authorize('view', $payment);

        return ApiResponse::success(
            new PaymentResource($payment),
            'Payment retrieved successfully.'
        );
    }

    /**
     * Update a payment.
     *
     * @group Payments
     *
     * @urlParam id integer required The payment ID.
     *
     * @bodyParam amount number Optional Payment amount.
     * @bodyParam method string Optional Payment method.
     * @bodyParam provider string Optional payment provider.
     * @bodyParam transaction_reference string Optional transaction reference.
     * @bodyParam status string Optional Payment status.
     * @bodyParam notes string Optional payment notes.
     *
     * @response 200 {
     *   "data": {}
     * }
     */
    public function update(
        UpdatePaymentRequest $request,
        string $id
    ) {
        $payment = $this->paymentService->findById(
            (int) $id
        );

        $this->authorize('update', $payment);

        $payment = $this->paymentService->update(
            (int) $id,
            $request->validated()
        );

        return ApiResponse::success(
            new PaymentResource($payment),
            'Payment updated successfully.'
        );
    }
}
