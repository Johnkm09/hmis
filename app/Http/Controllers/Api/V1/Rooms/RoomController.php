<?php

namespace App\Http\Controllers\Api\V1\Rooms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\StoreRoomRequest;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Room;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rooms = Room::with('user')->paginate(10);
        return RoomResource::collection($rooms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomRequest $request, Room $room)
    {
        $data = $request->validated();
        $room = Room::create($data);
        return response()->json(new RoomResource($room));
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        return response()->json(new RoomResource($room));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room)
    {
        $data = $request->validated();
        $room = Room::update($data);
        return response()->json(new RoomResource($room));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
