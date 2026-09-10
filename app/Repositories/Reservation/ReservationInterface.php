<?php

namespace App\Repositories\Reservation;

interface ReservationInterface
{
    public function getAll();

    public function create(array $data);

    public function findById(int $id);

    public function update(int $id, array $data);

    public function delete(int $id): void;

    public function hasOverlappingReservation(int $roomId, string $checkIn, string $checkOut, ?int $excludeReservationId = null): bool;
}
