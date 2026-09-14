<?php

namespace App\Services;

use App\Repositories\Service\ServiceRepositoryInterface;

class ServiceService
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository
    ) {}

    public function getAll()
    {
        return $this->serviceRepository->getAll();
    }

    public function create(array $data)
    {
        return $this->serviceRepository->create($data);
    }

    public function findById(int $id)
    {
        return $this->serviceRepository->findById($id);
    }

    public function update(int $id, array $data)
    {
        return $this->serviceRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->serviceRepository->delete($id);
    }
}
