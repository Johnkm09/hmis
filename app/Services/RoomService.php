<?php

namespace App\Services;
use App\Repositories\Room\RoomRepositoryInterface;

class RoomService
{
    protected RoomRepositoryInterface $roomRepository;

    public function __construct(RoomRepositoryInterface $roomRepository)
    {
        $this->roomRepository = $roomRepository;
    }

    public function getAll()
    {
        return $this->roomRepository->getAll();
    }

    public function create(array $data)
    {
        return $this->roomRepository->create($data);
    }

    public function findById(int $id)
    {
        return $this->roomRepository->findById($id);
    }

    public function update(int $id, array $data)
    {
        return $this->roomRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        $this->roomRepository->delete($id);
    }
}
