<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\ReceiptIndexRequest;
use App\Http\Requests\Api\V1\Payment\StoreReceiptRequest;
use App\Http\Requests\Api\V1\Payment\UpdateReceiptRequest;
use App\Http\Resources\Api\V1\Payment\ReceiptResource;
use App\Models\Payment\Receipt;
use App\Services\ReceiptService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReceiptController extends Controller
{
    use AuthorizesRequests;

    protected ReceiptService $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    /**
     * Display a listing of receipts.
     *
     * @group Receipts
     *
     * @queryParam filter[payment_id] integer Filter receipts by payment ID. Example: 1
     * @queryParam filter[receipt_number] string Filter receipts by receipt number. Example: RCT-000001
     * @queryParam sort string Sort by receipt_number, issued_at, or created_at. Example: -created_at
     * @queryParam per_page integer Number of receipts per page. Example: 10
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Receipts retrieved successfully",
     *     "data": []
     * }
     */
    public function index(ReceiptIndexRequest $request)
    {
        $this->authorize('viewAny', Receipt::class);

        $receipts = $this->receiptService->getAll();

        return ApiResponse::success(
            ReceiptResource::collection($receipts),
            'Receipts retrieved successfully',
            200,
            $receipts
        );
    }

    /**
     * Create a new receipt.
     *
     * @group Receipts
     *
     * @bodyParam payment_id integer required The payment ID for the receipt. Example: 1
     *
     * @response 201 {
     *     "success": true,
     *     "message": "Receipt created successfully",
     *     "data": {
     *         "id": 1,
     *         "receipt_number": "RCT-000001"
     *     }
     * }
     */
    public function store(StoreReceiptRequest $request)
    {
        $this->authorize('create', Receipt::class);

        $receipt = $this->receiptService->create($request->validated());

        return ApiResponse::success(
            new ReceiptResource($receipt),
            'Receipt created successfully',
            201
        );
    }

    /**
     * Display a specific receipt.
     *
     * @group Receipts
     *
     * @urlParam id integer required The receipt ID. Example: 1
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Receipt retrieved successfully",
     *     "data": {
     *         "id": 1,
     *         "receipt_number": "RCT-000001"
     *     }
     * }
     */
    public function show(string $id)
    {
        $receipt = $this->receiptService->findById((int) $id);

        $this->authorize('view', $receipt);

        return ApiResponse::success(
            new ReceiptResource($receipt),
            'Receipt retrieved successfully',
            200
        );
    }

    /**
     * Update a receipt.
     *
     * @group Receipts
     *
     * @urlParam id integer required The receipt ID. Example: 1
     *
     * @response 200 {
     *     "success": true,
     *     "message": "Receipt updated successfully",
     *     "data": {
     *         "id": 1,
     *         "receipt_number": "RCT-000001"
     *     }
     * }
     */
    public function update(UpdateReceiptRequest $request, string $id)
    {
        $receipt = $this->receiptService->findById((int) $id);

        $this->authorize('update', $receipt);

        $receipt = $this->receiptService->update(
            (int) $id,
            $request->validated()
        );

        return ApiResponse::success(
            new ReceiptResource($receipt),
            'Receipt updated successfully',
            200
        );
    }
}
