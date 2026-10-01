<?php

namespace App\Repositories\RoomTypeImage;

interface RoomTypeImageInterface
{
    public function getByRoomType(int $roomTypeId);

    public function create(array $data);

    public function findById(int $id);

    public function update(int $id, array $data);

    public function delete(int $id): void;
}
