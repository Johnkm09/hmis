<?php

namespace App\Http\Controllers\Api\V1\Folio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Folio\StoreFolioChargeRequest;
use App\Http\Requests\Api\V1\Folio\UpdateFolioChargeRequest;
use App\Http\Resources\Api\V1\Folio\FolioChargeResource;
use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Services\FolioChargeService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class FolioChargeController extends Controller
{
    use AuthorizesRequests;

    protected FolioChargeService $folioChargeService;

    public function __construct(FolioChargeService $folioChargeService)
    {
        $this->folioChargeService = $folioChargeService;
    }

    /**
     * Get all charges for a folio
     *
     * @group Folio Charges(v1)
     * @authenticated
     * @urlParam folio integer required The ID of the folio. Example: 1
     */
    public function index(Folio $folio)
    {
        $this->authorize('viewAny', FolioCharge::class);

        $charges = $this->folioChargeService->getByFolio(
            $folio->id
        );

        return ApiResponse::success(
            FolioChargeResource::collection($charges),
            'Folio charges retrieved successfully'
        );
    }

    /**
     * Create a folio charge
     *
     * @group Folio Charges(v1)
     * @authenticated
     * @urlParam folio integer required The ID of the folio. Example: 1
     * @bodyParam type string required Charge type. Example: service
     * @bodyParam service_id integer The ID of the service. Required for service charges. Example: 1
     * @bodyParam description string required Description of the charge. Example: Laundry service
     * @bodyParam quantity number required Quantity charged. Example: 2
     * @bodyParam unit_price number required Unit price of the charge. Example: 500.00
     */
    public function store(
        StoreFolioChargeRequest $request,
        Folio $folio
    ) {
        $this->authorize('create', FolioCharge::class);

        $data = $request->validated();

        $data['folio_id'] = $folio->id;
        $data['charged_at'] = now();
        $data['charged_by'] = Auth::id();

        $charge = $this->folioChargeService->create($data);

        return ApiResponse::success(
            new FolioChargeResource($charge),
            'Folio charge created successfully',
            201
        );
    }

    /**
     * Get a single folio charge
     *
     * @group Folio Charges(v1)
     * @authenticated
     * @urlParam id integer required The ID of the folio charge. Example: 1
     */
    public function show(string $id)
    {
        $charge = $this->folioChargeService->findById(
            (int) $id
        );

        $this->authorize('view', $charge);

        return ApiResponse::success(
            new FolioChargeResource($charge),
            'Folio charge retrieved successfully.'
        );
    }

    /**
     * Update a folio charge
     *
     * @group Folio Charges(v1)
     * @authenticated
     * @urlParam id integer required The ID of the folio charge. Example: 1
     * @bodyParam description string Description of the charge. Example: Updated laundry service
     * @bodyParam quantity number Quantity charged. Example: 3
     * @bodyParam unit_price number Unit price of the charge. Example: 600.00
     */
    public function update(
        UpdateFolioChargeRequest $request,
        string $id
    ) {
        $charge = $this->folioChargeService->findById(
            (int) $id
        );

        $this->authorize('update', $charge);

        $charge = $this->folioChargeService->update(
            (int) $id,
            $request->validated()
        );

        return ApiResponse::success(
            new FolioChargeResource($charge),
            'Folio charge updated successfully.'
        );
    }
}
