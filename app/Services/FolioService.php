<?php

namespace App\Services;

use App\Repositories\Folio\FolioRepositoryInterface;
use Illuminate\Validation\ValidationException;

class FolioService
{
    public function __construct(
        private FolioRepositoryInterface $folioRepository
    ) {}

    public function getByReservation(int $reservationId)
    {
        return $this->folioRepository->getByReservation($reservationId);
    }

    public function create(array $data)
    {
        $existingFolio = $this->folioRepository->findByReservation(
            $data['reservation_id']
        );

        if ($existingFolio) {
            throw ValidationException::withMessages([
                'reservation' => [
                    'A folio already exists for this reservation.'
                ],
            ]);
        }

        return $this->folioRepository->create($data);
    }

    public function findById(int $id)
    {
        return $this->folioRepository->findById($id);
    }

    public function update(int $id, array $data)
    {
        return $this->folioRepository->update($id, $data);
    }
}
