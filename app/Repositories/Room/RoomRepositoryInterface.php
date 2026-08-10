<?php

namespace App\Repositories\Room;

interface RoomRepositoryInterface
{
    public function getAll();

    public function create(array $data);

    public function findById(int $id);

    public function update(int $id, array $data);
    
    public function delete(int $id):void;
}
