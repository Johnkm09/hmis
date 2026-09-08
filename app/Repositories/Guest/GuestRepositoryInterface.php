<?php

namespace App\Repositories\Guest;

interface GuestRepositoryInterface
{
    public function getAll();

    public function create(array $data);

    public function findById(int $id);

    public function findByIdNumber(string $idNumber);

    public function update(int $id, array $data);

    public function delete(int $id): void;
}
