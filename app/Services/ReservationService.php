<?php

namespace App\Services;

use App\Models\Guest\Guest;
use App\Models\Room\Room;
use App\Repositories\Reservation\ReservationInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    protected ReservationInterface $reservationRepository;

    public function __construct(ReservationInterface $reservationRepository)
    {
        $this->reservationRepository = $reservationRepository;
    }

    public function getAll()
    {
        return $this->reservationRepository->getAll();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return $this->createReservation($data);
        });
    }

    public function createWithoutTransaction(array $data)
    {
        return $this->createReservation($data);
    }

    protected function createReservation(array $data)
    {
        Guest::findOrFail($data['guest_id']);

        $room = Room::with('roomType')
            ->where('id', $data['room_id'])
            ->lockForUpdate()
            ->firstOrFail();

        if (!$room->is_active) {
            throw ValidationException::withMessages([
                'room_id' => 'The selected room is not active.',
            ]);
        }

        if ($data['number_of_guests'] > $room->roomType->max_occupancy) {
            throw ValidationException::withMessages([
                'number_of_guests' => 'The number of guests exceeds the maximum occupancy of the selected room.',
            ]);
        }

        if ($this->reservationRepository->hasOverlappingReservation(
            $room->id,
            $data['check_in'],
            $data['check_out']
        )) {
            throw ValidationException::withMessages([
                'room_id' => 'The selected room is already reserved for the requested dates.',
            ]);
        }

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        $nights = $checkIn->diffInDays($checkOut);
        $nightlyRate = $room->price;
        $totalAmount = $nights * $nightlyRate;

        $data['nightly_rate'] = $nightlyRate;
        $data['total_amount'] = $totalAmount;

        return $this->reservationRepository->create($data);
    }

    public function findById(int $id)
    {
        return $this->reservationRepository->findById($id);
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {

            $reservation = $this->reservationRepository->findById($id);

            $roomId = $data['room_id'] ?? $reservation->room_id;

            $checkIn = $data['check_in']
                ?? $reservation->check_in->format('Y-m-d');

            $checkOut = $data['check_out']
                ?? $reservation->check_out->format('Y-m-d');

            $numberOfGuests = $data['number_of_guests']
                ?? $reservation->number_of_guests;

            $checkInDate = Carbon::parse($checkIn);
            $checkOutDate = Carbon::parse($checkOut);

            if ($checkOutDate->lessThanOrEqualTo($checkInDate)) {
                throw ValidationException::withMessages([
                    'check_out' => 'The check-out date must be after the check-in date.',
                ]);
            }

            if (isset($data['guest_id'])) {
                Guest::findOrFail($data['guest_id']);
            }

            $room = Room::with('roomType')
                ->where('id', $roomId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$room->is_active) {
                throw ValidationException::withMessages([
                    'room_id' => 'The selected room is not active.',
                ]);
            }

            if ($numberOfGuests > $room->roomType->max_occupancy) {
                throw ValidationException::withMessages([
                    'number_of_guests' => 'The number of guests exceeds the maximum occupancy of the selected room.',
                ]);
            }

            if ($this->reservationRepository->hasOverlappingReservation(
                $roomId,
                $checkIn,
                $checkOut,
                $reservation->id
            )) {
                throw ValidationException::withMessages([
                    'room_id' => 'The selected room is already reserved for the requested dates.',
                ]);
            }

            $nights = $checkInDate->diffInDays($checkOutDate);
            $nightlyRate = $room->price;
            $totalAmount = $nights * $nightlyRate;

            $data['nightly_rate'] = $nightlyRate;
            $data['total_amount'] = $totalAmount;

            return $this->reservationRepository->update($id, $data);
        });
    }

    public function delete(int $id)
    {
        return $this->reservationRepository->delete($id);
    }
}
