<?php

namespace App\Services;

use App\Repositories\Guest\GuestRepositoryInterface;

class GuestService
{
    protected GuestRepositoryInterface $guestRepository;

    public function __construct(GuestRepositoryInterface $guestRepository)
    {
        $this->guestRepository = $guestRepository;
    }

    public function getAll()
    {
        return $this->guestRepository->getAll();
    }

    public function create(array $data)
    {
        return $this->guestRepository->create($data);
    }

    public function findById(int $id)
    {
        return $this->guestRepository->findById($id);
    }

    public function update(int $id, array $data)
    {
        return $this->guestRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->guestRepository->delete($id);
    }
}
