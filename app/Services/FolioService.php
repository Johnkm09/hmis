<?php

namespace App\Services;

use App\Repositories\Reservation\ReservationInterface;
use App\Repositories\Folio\FolioRepositoryInterface;
use App\Repositories\Folio\FolioChargeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FolioService
{
    public function __construct(
        private FolioRepositoryInterface $folioRepository,
        private FolioChargeRepositoryInterface $folioChargeRepository,
        private ReservationInterface $reservationRepository
    ) {}

    public function getByReservation(int $reservationId)
    {
        return $this->folioRepository->getByReservation($reservationId);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

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

            $folio = $this->folioRepository->create($data);

            $reservation = $this->reservationRepository->findById(
                $data['reservation_id']
            );

            $nights = $reservation->check_in
                ->diffInDays($reservation->check_out);

            $this->folioChargeRepository->create([
                'folio_id' => $folio->id,
                'service_id' => null,
                'type' => 'accommodation',
                'description' => 'Room accommodation',
                'quantity' => $nights,
                'unit_price' => $reservation->nightly_rate,
                'amount' => $reservation->total_amount,
                'charged_at' => now(),
                'charged_by' => $data['charged_by'],
            ]);

            return $folio;
        });
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
