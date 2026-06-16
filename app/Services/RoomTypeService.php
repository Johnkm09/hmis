<?php

namespace App\Services;
use App\Repositories\RoomType\RoomTypeInterface;
use Illuminate\Support\Str;

class RoomTypeService
{
    protected RoomTypeInterface $roomTypeRepository;

    public function __construct(RoomTypeInterface $roomTypeRepository)
    {
        $this->roomTypeRepository = $roomTypeRepository;
    }

    //Returning all room types
    public function getAll()
    {
        return $this->roomTypeRepository->getAll();
    }

    //Creating room types
    public function create(array $data)
    {
        $data['slug'] = Str::slug($data['name']);
        return $this->roomTypeRepository->create($data);
    }

    //Return a room type by ID 
    public function findById(int $id)
    {
        return $this->roomTypeRepository->findById($id);
    }

    //Update room type
    public function update(int $id, array $data)  
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        return $this->roomTypeRepository->update($id, $data);
    }

    //Delete room type
    public function delete(int $id):void
    {
        $this->roomTypeRepository->delete($id);
    }
}
