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

            // Verify guest exists
            Guest::findOrFail($data['guest_id']);

            /*
             * Lock the room row so simultaneous booking attempts
             * for the same room are processed one at a time.
             */
            $room = Room::with('roomType')
                ->where('id', $data['room_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Ensure the room is active
            if (!$room->is_active) {
                throw ValidationException::withMessages([
                    'room_id' => 'The selected room is not active.',
                ]);
            }

            // Ensure the number of guests does not exceed room capacity
            if ($data['number_of_guests'] > $room->roomType->max_occupancy) {
                throw ValidationException::withMessages([
                    'number_of_guests' => 'The number of guests exceeds the maximum occupancy of the selected room.',
                ]);
            }

            // Prevent overlapping reservations
            if ($this->reservationRepository->hasOverlappingReservation(
                $room->id,
                $data['check_in'],
                $data['check_out']
            )) {
                throw ValidationException::withMessages([
                    'room_id' => 'The selected room is already reserved for the requested dates.',
                ]);
            }

            // Calculate number of nights
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);

            $nights = $checkIn->diffInDays($checkOut);

            // Snapshot the current room price
            $nightlyRate = $room->price;

            // Calculate reservation total
            $totalAmount = $nights * $nightlyRate;

            $data['nightly_rate'] = $nightlyRate;
            $data['total_amount'] = $totalAmount;

            return $this->reservationRepository->create($data);
        });
    }

    public function findById(int $id)
    {
        return $this->reservationRepository->findById($id);
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {

            $reservation = $this->reservationRepository->findById($id);

            // Use existing values when fields are not included in the update request
            $roomId = $data['room_id'] ?? $reservation->room_id;

            $checkIn = $data['check_in']
                ?? $reservation->check_in->format('Y-m-d');

            $checkOut = $data['check_out']
                ?? $reservation->check_out->format('Y-m-d');

            $numberOfGuests = $data['number_of_guests']
                ?? $reservation->number_of_guests;

            // Ensure the effective date range is valid
            $checkInDate = Carbon::parse($checkIn);
            $checkOutDate = Carbon::parse($checkOut);

            if ($checkOutDate->lessThanOrEqualTo($checkInDate)) {
                throw ValidationException::withMessages([
                    'check_out' => 'The check-out date must be after the check-in date.',
                ]);
            }

            // Verify guest exists if guest_id is being changed
            if (isset($data['guest_id'])) {
                Guest::findOrFail($data['guest_id']);
            }

            /*
             * Lock the selected room so concurrent updates/bookings
             * cannot modify availability for the same room simultaneously.
             */
            $room = Room::with('roomType')
                ->where('id', $roomId)
                ->lockForUpdate()
                ->firstOrFail();

            // Ensure the room is active
            if (!$room->is_active) {
                throw ValidationException::withMessages([
                    'room_id' => 'The selected room is not active.',
                ]);
            }

            // Ensure the number of guests does not exceed room capacity
            if ($numberOfGuests > $room->roomType->max_occupancy) {
                throw ValidationException::withMessages([
                    'number_of_guests' => 'The number of guests exceeds the maximum occupancy of the selected room.',
                ]);
            }

            // Prevent overlapping reservations while excluding the current reservation
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

            // Calculate number of nights
            $nights = $checkInDate->diffInDays($checkOutDate);

            // Snapshot the current room price
            $nightlyRate = $room->price;

            // Recalculate reservation total
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
