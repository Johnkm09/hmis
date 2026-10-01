<?php

namespace App\Repositories\RoomTypeImage;

use App\Models\RoomType\RoomTypeImage;
use Override;

class RoomTypeImageRepository implements RoomTypeImageInterface
{
    public function getByRoomType(int $roomTypeId)
    {
        return RoomTypeImage::where('room_type_id', $roomTypeId)
            ->oldest()
            ->get();
    }

    public function create(array $data)
    {
        return RoomTypeImage::create($data);
    }

    public function findById(int $id)
    {
        return RoomTypeImage::findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $image = RoomTypeImage::findOrFail($id);
        $image->update($data);

        return $image;
    }

    public function delete(int $id): void
    {
        $image = RoomTypeImage::findOrFail($id);
        $image->delete();
    }
}
