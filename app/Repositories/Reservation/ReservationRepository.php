<?php

namespace App\Repositories\Reservation;

use App\Filters\Reservation\CheckInFromFilter;
use App\Filters\Reservation\CheckInToFilter;
use App\Filters\Reservation\CheckOutFromFilter;
use App\Filters\Reservation\CheckOutToFilter;
use App\Models\Reservation\Reservation;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReservationRepository implements ReservationInterface
{
    public function getAll()
    {
        return QueryBuilder::for(
            Reservation::with(['guest', 'room.roomType'])
        )
            ->allowedFilters([
                AllowedFilter::exact('guest_id'),
                AllowedFilter::exact('room_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::custom('check_in_from', new CheckInFromFilter()),
                AllowedFilter::custom('check_in_to', new CheckInToFilter()),
                AllowedFilter::custom('check_out_from', new CheckOutFromFilter()),
                AllowedFilter::custom('check_out_to', new CheckOutToFilter()),
                AllowedFilter::exact('number_of_guests'),
            ])
            ->allowedSorts([
                'check_in',
                'check_out',
                'number_of_guests',
                'nightly_rate',
                'total_amount',
                'status',
                'created_at',
            ])
            ->latest()
            ->paginate(request('per_page', 10));
    }

    public function create(array $data)
    {
        return Reservation::create($data)
            ->load(['guest', 'room.roomType']);
    }

    public function findById(int $id)
    {
        return Reservation::with(['guest', 'room.roomType'])
            ->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $reservation = Reservation::findOrFail($id);

        $reservation->update($data);

        return $reservation->load(['guest', 'room.roomType']);
    }

    public function delete(int $id): void
    {
        $reservation = Reservation::findOrFail($id);

        $reservation->delete();
    }

    public function hasOverlappingReservation(int $roomId, string $checkIn, string $checkOut, ?int $excludeReservationId = null): bool
    {
        $query = Reservation::where('room_id', $roomId)
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn);

        if ($excludeReservationId !== null) {
            $query->where('id', '!=', $excludeReservationId);
        }
        return $query->exists();
    }
}
