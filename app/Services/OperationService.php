<?php

namespace App\Services;

use App\Repositories\Contracts\OperationInterface;
use App\Repositories\Room\RoomRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperationService
{
    public function __construct(
        protected GuestService $guestService,
        protected ReservationService $reservationService,
        protected RoomRepositoryInterface $roomRepository,
        protected OperationInterface $operationRepository
    ) {}

    /**
     * Perform check-in for an existing pending reservation.
     *
     * @throws ValidationException
     */
    public function checkIn(
        int $reservationId,
        int $performedBy,
        ?string $notes = null
    ): mixed {
        return DB::transaction(function () use (
            $reservationId,
            $performedBy,
            $notes
        ) {
            $reservation = $this->reservationService->findById($reservationId);

            // Lock the room.
            $room = $this->roomRepository->findAndLock($reservation->room_id);

            if ($reservation->status !== 'pending') {
                throw ValidationException::withMessages([
                    'reservation' => 'Only pending reservations can be checked in.',
                ]);
            }

            if ($room->status !== 'reserved') {
                throw ValidationException::withMessages([
                    'room_id' => 'The room is not currently reserved.',
                ]);
            }

            $reservation->update([
                'status' => 'checked_in',
            ]);

            $this->roomRepository->update($room->id, [
                'status' => 'occupied',
            ]);

            return $this->operationRepository->create([
                'reservation_id' => $reservation->id,
                'type' => 'check_in',
                'performed_at' => now(),
                'performed_by' => $performedBy,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Create a walk-in guest, reservation and immediate check-in.
     *
     * @throws ValidationException
     */
    public function walkIn(
        array $guestData,
        int $roomId,
        string $checkIn,
        string $checkOut,
        int $numberOfGuests,
        int $performedBy,
        ?string $notes = null
    ): mixed {
        return DB::transaction(function () use (
            $guestData,
            $roomId,
            $checkIn,
            $checkOut,
            $numberOfGuests,
            $performedBy,
            $notes
        ) {
            // Find existing guest or create a new one.
            $guest = $this->guestService->findByIdNumber(
                $guestData['id_number']
            ) ?? $this->guestService->create($guestData);

            // Lock the room.
            $room = $this->roomRepository->findAndLock($roomId);

            if ($room->status !== 'available') {
                throw ValidationException::withMessages([
                    'room_id' => 'The selected room is not available.',
                ]);
            }

            // Create reservation.
            $reservation = $this->reservationService->createWithoutTransaction([
                'guest_id' => $guest->id,
                'room_id' => $roomId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'number_of_guests' => $numberOfGuests,
            ]);

            // Immediately check in.
            $reservation->update([
                'status' => 'checked_in',
            ]);

            $this->roomRepository->update($roomId, [
                'status' => 'occupied',
            ]);

            return $this->operationRepository->create([
                'reservation_id' => $reservation->id,
                'type' => 'check_in',
                'performed_at' => now(),
                'performed_by' => $performedBy,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Perform check-out.
     *
     * @throws ValidationException
     */
    public function checkOut(
        int $reservationId,
        int $performedBy,
        ?string $notes = null
    ): mixed {
        return DB::transaction(function () use (
            $reservationId,
            $performedBy,
            $notes
        ) {
            $reservation = $this->reservationService->findById($reservationId);

            // Lock the room.
            $room = $this->roomRepository->findAndLock($reservation->room_id);

            if ($reservation->status !== 'checked_in') {
                throw ValidationException::withMessages([
                    'reservation' => 'Only checked-in reservations can be checked out.',
                ]);
            }

            if ($room->status !== 'occupied') {
                throw ValidationException::withMessages([
                    'room_id' => 'The room is not currently occupied.',
                ]);
            }

            $reservation->update([
                'status' => 'checked_out',
            ]);

            $this->roomRepository->update($room->id, [
                'status' => 'available',
            ]);

            return $this->operationRepository->create([
                'reservation_id' => $reservation->id,
                'type' => 'check_out',
                'performed_at' => now(),
                'performed_by' => $performedBy,
                'notes' => $notes,
            ]);
        });
    }
}
