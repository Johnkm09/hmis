<?php

namespace App\Repositories\Folio;

interface FolioRepositoryInterface
{
    public function getByReservation(int $reservationId);

    public function create(array $data);

    public function findById(int $id);

    public function findByReservation(int $reservationId);

    public function update(int $id, array $data);
}
