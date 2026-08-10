<?php

namespace App\Http\Controllers\Api\V1\Room;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Room\RoomRequest;
use App\Http\Requests\Api\V1\Room\UpdateRoomRequest;
use App\Http\Requests\Api\V1\Room\RoomIndexRequest;
use App\Http\Resources\Api\V1\Room\RoomResource;
use App\Models\Room\Room;
use App\Services\RoomService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RoomController extends Controller
{
    use AuthorizesRequests;

    protected RoomService $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function index(RoomIndexRequest $request)
    {
        $this->authorize('viewAny', Room::class);

        $rooms = $this->roomService->getAll();
        
        return ApiResponse::success(
            RoomResource::collection($rooms),
            'Rooms retrieved successfully',
            200,
            $rooms
        );
    }

    public function store(RoomRequest $request)
    {
        $this->authorize('create', Room::class);

        $room = $this->roomService->create($request->validated());

        return ApiResponse::success(
            new RoomResource($room),
            'Room created successfully',
            201
        );
    }

    public function show(string $id)
    {
        $room = $this->roomService->findById((int) $id);

        $this->authorize('view', $room);

        return ApiResponse::success(
            new RoomResource($room),
            'Room retrieved successfully.'
        );
    }

    public function update(UpdateRoomRequest $request, string $id)
    {
        $room = $this->roomService->findById((int) $id);

        $this->authorize('update', $room);

        $room = $this->roomService->update(
            (int) $id, 
            $request->validated()
        );

        return ApiResponse::success(
            new RoomResource($room),
            'Room updated successfully.'
        );
    }

    public function destroy(string $id)
    {
        $room = $this->roomService->findById((int) $id);

        $this->authorize('delete', $room);

        $this->roomService->delete((int) $id);

        return ApiResponse::success(
            [],
            'Room deleted successfully'
        );
    }
}
