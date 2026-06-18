<?php

namespace App\Http\Controllers\Api\V1\RoomType;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RoomType\RoomTypeRequest;
use App\Http\Requests\Api\V1\RoomType\UpdateRoomTypeRequest;
use App\Http\Resources\Api\V1\RoomType\RoomTypeResource;
use App\Models\RoomType\RoomType;
use App\Services\RoomTypeService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RoomTypeController extends Controller
{
    use AuthorizesRequests;
    protected RoomTypeService $roomTypeService;

    public function __construct(RoomTypeService $roomTypeService)
    {
        $this->roomTypeService = $roomTypeService;
    }

    /**
     * Get all room types
    *
    * This endpoint returns a paginated list of room types.
    *
    * @group Room Types
    */
    public function index()
    {
        $this->authorize('viewAny', RoomType::class);

        $roomTypes = $this->roomTypeService->getAll();

        return ApiResponse::success(
            RoomTypeResource::collection($roomTypes),
            'Room types retrieved successfully.'
        );
    }

    /**
     * Create a room type
        *
        * @group Room Types
        *
        * @bodyParam name string required Example: Deluxe Room
        * @bodyParam description string optional Example: Nice room
    */
    public function store(RoomTypeRequest $request)
    {
        $this->authorize('create', RoomType::class);

        $roomType = $this->roomTypeService->create(
            $request->validated()
        );

        return ApiResponse::success(
            new RoomTypeResource($roomType),
            'Room type created successfully.',
            201
        );
    }

    /**
     * Get single room type
    *
    * @group Room Types
    *
    * @urlParam id integer required The ID of the room type
    */
    public function show(string $id)
    {
        $roomType = $this->roomTypeService->findById((int) $id);

        $this->authorize('view', $roomType);

        return ApiResponse::success(
            new RoomTypeResource($roomType),
            'Room type retrieved successfully.'
        );
    }

    /**
         * Update room type
        *
        * @group Room Types
        *
        * @urlParam id integer required The ID of the room type
        * @bodyParam name string Example: Updated Room
        * @bodyParam description string Example: Updated description
    */
    public function update(UpdateRoomTypeRequest $request, string $id)
    {
        $roomType = $this->roomTypeService->findById((int) $id);

        $this->authorize('update', $roomType);

        $roomType = $this->roomTypeService->update(
            (int) $id,
            $request->validated()
        );

        return ApiResponse::success(
            new RoomTypeResource($roomType),
            'Room type updated successfully.'
        );
    }

    /**
         * Delete room type
        *
        * @group Room Types
        *
        * @urlParam id integer required The ID of the room type
    */
    public function destroy(string $id)
    {
        $roomType = $this->roomTypeService->findById((int) $id);

        $this->authorize('delete', $roomType);

        $this->roomTypeService->delete((int) $id);

        return ApiResponse::success(
            [],
            'Room type deleted successfully'
        );
    }
}