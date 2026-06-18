<?php

namespace App\Repositories\RoomType;
use App\Models\RoomType\RoomType;
use Override;

class RoomTypeRepository implements RoomTypeInterface
{
    //Returning all room types
    public function getAll()
    {
        return RoomType::query()->latest()->paginate(1);
    }

    //Creating room types
    public function create(array $data)
    {
        return RoomType::create($data);
    }

    //Show room type by ID
    public function findById(int $id)
    {
        return RoomType::findOrFail($id);
    }

    //Update a room type
    public function update(int $id, array $data)
    {
        $roomtype = RoomType::findOrFail($id);
        $roomtype->update($data);
        return $roomtype;
    }

    //Delete a room type
    public function delete(int $id):void
    {
        $roomtype = RoomType::findOrFail($id);
        $roomtype->delete();
    }
}
