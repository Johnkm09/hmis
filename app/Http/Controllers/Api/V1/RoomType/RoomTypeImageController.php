<?php

namespace App\Http\Controllers\Api\V1\RoomType;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RoomType\RoomTypeImageRequest;
use App\Http\Resources\Api\V1\RoomType\RoomTypeImageResource;
use App\Models\RoomType\RoomType;
use App\Models\RoomType\RoomTypeImage;
use App\Services\RoomTypeImageService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RoomTypeImageController extends Controller
{
    use AuthorizesRequests;

    protected RoomTypeImageService $roomTypeImageService;

    public function __construct(RoomTypeImageService $roomTypeImageService)
    {
        $this->roomTypeImageService = $roomTypeImageService;
    }

    /**
     * Get room type images
     *
     * @group Room Type Images(v1)
     * @authenticated
     * @urlParam roomType integer required The ID of the room type. Example: 1
     */
    public function index(RoomType $roomType)
    {
        $this->authorize('view', $roomType);

        $images = $this->roomTypeImageService->getByRoomType(
            $roomType->id
        );

        return ApiResponse::success(
            RoomTypeImageResource::collection($images),
            'Room type images retrieved successfully.'
        );
    }

    /**
     * Upload room type image
     *
     * @group Room Type Images(v1)
     * @authenticated
     * @urlParam roomType integer required The ID of the room type. Example: 1
     * @bodyParam image file required Room type image. Supported formats: JPG, JPEG, PNG, WebP. Maximum size: 5MB.
     */
    public function store(
        RoomTypeImageRequest $request,
        RoomType $roomType
    ) {
        $this->authorize('update', $roomType);

        $image = $this->roomTypeImageService->add(
            $roomType,
            $request->file('image')
        );

        return ApiResponse::success(
            new RoomTypeImageResource($image),
            'Room type image uploaded successfully.',
            201
        );
    }

    /**
     * Set room type primary image
     *
     * @group Room Type Images(v1)
     * @authenticated
     * @urlParam roomTypeImage integer required The ID of the room type image. Example: 1
     */
    public function setPrimary(RoomTypeImage $roomTypeImage)
    {
        $this->authorize('update', $roomTypeImage->roomType);

        $image = $this->roomTypeImageService->setPrimary(
            $roomTypeImage
        );

        return ApiResponse::success(
            new RoomTypeImageResource($image),
            'Room type primary image updated successfully.'
        );
    }

    /**
     * Delete room type image
     *
     * @group Room Type Images(v1)
     * @authenticated
     * @urlParam roomTypeImage integer required The ID of the room type image. Example: 1
     */
    public function destroy(RoomTypeImage $roomTypeImage)
    {
        $this->authorize('update', $roomTypeImage->roomType);

        $this->roomTypeImageService->delete($roomTypeImage);

        return ApiResponse::success(
            [],
            'Room type image deleted successfully.'
        );
    }
}
