<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\StoreRefundRequest;
use App\Http\Requests\Api\V1\Payment\UpdateRefundRequest;
use App\Http\Resources\Api\V1\Payment\RefundResource;
use App\Models\Payment\Payment;
use App\Models\Payment\Refund;
use App\Services\RefundService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class RefundController extends Controller
{
    use AuthorizesRequests;

    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Display a list of refunds for a payment.
     *
     * @group Refunds
     *
     * @urlParam payment integer required The ID of the payment.
     *
     * @response 200 {
     *   "data": []
     * }
     */
    public function index(Payment $payment)
    {
        $this->authorize('viewAny', Refund::class);

        $refunds = $this->refundService->getByPayment(
            $payment->id
        );

        return ApiResponse::success(
            RefundResource::collection($refunds),
            'Refunds retrieved successfully'
        );
    }

    /**
     * Create a refund against a payment.
     *
     * @group Refunds
     *
     * @urlParam payment integer required The ID of the payment.
     *
     * @bodyParam amount number required Refund amount.
     * @bodyParam reason string required Reason for the refund.
     * @bodyParam status string required Refund status. Example: completed
     * @bodyParam transaction_reference string Optional refund transaction reference.
     *
     * @response 201 {
     *   "data": {}
     * }
     */
    public function store(
        StoreRefundRequest $request,
        Payment $payment
    ) {
        $this->authorize('create', Refund::class);

        $data = $request->validated();

        $data['payment_id'] = $payment->id;
        $data['refunded_by'] = Auth::id();

        $refund = $this->refundService->create($data);

        return ApiResponse::success(
            new RefundResource($refund),
            'Refund created successfully',
            201
        );
    }

    /**
     * Display a refund.
     *
     * @group Refunds
     *
     * @urlParam id integer required The refund ID.
     *
     * @response 200 {
     *   "data": {}
     * }
     */
    public function show(string $id)
    {
        $refund = $this->refundService->findById(
            (int) $id
        );

        $this->authorize('view', $refund);

        return ApiResponse::success(
            new RefundResource($refund),
            'Refund retrieved successfully.'
        );
    }

    /**
     * Update a refund.
     *
     * @group Refunds
     *
     * @urlParam id integer required The refund ID.
     *
     * @bodyParam amount number Optional Refund amount.
     * @bodyParam reason string Optional reason for the refund.
     * @bodyParam status string Optional Refund status.
     * @bodyParam transaction_reference string Optional refund transaction reference.
     *
     * @response 200 {
     *   "data": {}
     * }
     */
    public function update(
        UpdateRefundRequest $request,
        string $id
    ) {
        $refund = $this->refundService->findById(
            (int) $id
        );

        $this->authorize('update', $refund);

        $refund = $this->refundService->update(
            (int) $id,
            $request->validated()
        );

        return ApiResponse::success(
            new RefundResource($refund),
            'Refund updated successfully.'
        );
    }
}
