<?php

namespace App\Repositories\Room;

use App\Models\Room\Room;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoomRepository implements RoomRepositoryInterface
{
    public function getAll()
    {
        return QueryBuilder::for(Room::class)
            ->allowedFilters([
                AllowedFilter::partial('room_number'),
                AllowedFilter::exact('room_type_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('floor_no'),
                AllowedFilter::exact('is_active'),
            ])
            ->allowedSorts([
                'room_number',
                'floor_no',
                'price',
                'status',
                'created_at',
            ])
            ->latest()
            ->paginate(
                request('per_page', 10)
            );
    }

    public function create(array $data)
    {
        return Room::create($data);
    }

    public function findById(int $id)
    {
        return Room::findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $room = Room::findOrFail($id);
        $room->update($data);
        return $room;
    }

    public function delete(int $id): void
    {
         $room = Room::findOrFail($id);
        $room->delete();
    }
}