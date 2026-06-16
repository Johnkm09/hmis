<?php

namespace App\Http\Controllers\Api\V1\RoomType;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\RoomType\RoomTypeRequest;
use App\Http\Requests\Api\V1\RoomType\UpdateRoomTypeRequest;
use App\Http\Resources\Api\V1\RoomType\RoomTypeResource;
use App\Services\RoomTypeService;
use App\Support\ApiResponse;

class RoomTypeController extends Controller
{
    protected RoomTypeService $roomTypeService;

    public function __construct(RoomTypeService $roomTypeService)
    {
        $this->roomTypeService = $roomTypeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roomTypes = $this->roomTypeService->getAll();
        return ApiResponse::success(
            RoomTypeResource::collection($roomTypes),
            'Room types retrieved successfully.'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoomTypeRequest $request)
    {
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $roomType = $this->roomTypeService->findById((int) $id);
        return ApiResponse::success(
            new RoomTypeResource($roomType),
            'Room type retrieved successfully.'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomTypeRequest $request, string $id)
    {
        $roomType = $this->roomTypeService->update((int) $id, $request->validated());
        return ApiResponse::success(
            new RoomTypeResource($roomType),
            'Room type updated successfull'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->roomTypeService->delete((int) $id);
        return ApiResponse::success(
            [],
            'Room type deleted successfully'
        );
    }
}
