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

    /**
     * Get all rooms list
     *
     * @group Rooms(v1)
     * @authenticated
     * @queryParam filter[room_number] string Filter by room number. Example: 101
     * @queryParam filter[room_type_id] integer Filter by room type ID. Example: 1
     * @queryParam filter[status] string Filter by room status. Example: available
     * @queryParam filter[floor_no] integer Filter by floor number. Example: 2
     * @queryParam filter[is_active] boolean Filter by active status. Example: 1
     * @queryParam sort string Sort rooms. Prefix with - for descending order. Example: -price
     * @queryParam per_page integer Number of rooms per page. Example: 10
     */
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

    /**
     * Create a room
     *
     * @group Rooms(v1)
     * @authenticated
     * @bodyParam room_type_id integer required The ID of the room type. Example: 1
     * @bodyParam room_number string required The room number. Example: 101
     * @bodyParam floor_no integer optional The floor number. Example: 1
     * @bodyParam status string required Room status. Example: available
     * @bodyParam price number required Room price. Example: 150.00
     * @bodyParam is_active boolean optional Whether the room is active. Example: true
     */
    public function store(RoomRequest $request)
    {
        $this->authorize('create', Room::class);

        $room = $this->roomService->create(
            $request->validated()
        );

        return ApiResponse::success(
            new RoomResource($room),
            'Room created successfully',
            201
        );
    }

    /**
     * Get a single room
     *
     * @group Rooms(v1)
     * @authenticated
     * @urlParam id integer required The ID of the room. Example: 1
     */
    public function show(string $id)
    {
        $room = $this->roomService->findById((int) $id);

        $this->authorize('view', $room);

        return ApiResponse::success(
            new RoomResource($room),
            'Room retrieved successfully.'
        );
    }

    /**
     * Update a room
     *
     * @group Rooms(v1)
     * @authenticated
     * @urlParam id integer required The ID of the room. Example: 1
     * @bodyParam room_type_id integer The ID of the room type. Example: 2
     * @bodyParam room_number string The room number. Example: 201
     * @bodyParam floor_no integer The floor number. Example: 2
     * @bodyParam status string Room status. Example: maintenance
     * @bodyParam price number Room price. Example: 250.00
     * @bodyParam is_active boolean Whether the room is active. Example: true
     */
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

    /**
     * Delete a room
     *
     * @group Rooms(v1)
     * @authenticated
     * @urlParam id integer required The ID of the room. Example: 1
     */
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