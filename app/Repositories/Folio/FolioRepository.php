<?php

namespace App\Repositories\Folio;

use App\Models\Folio\Folio;

class FolioRepository implements FolioRepositoryInterface
{
    public function getByReservation(int $reservationId)
    {
        return Folio::where('reservation_id', $reservationId)->firstOrFail();
    }

    public function create(array $data)
    {
        return Folio::create($data);
    }

    public function findById(int $id)
    {
        return Folio::findOrFail($id);
    }

    public function findByReservation(int $reservationId)
    {
        return Folio::where('reservation_id', $reservationId)->first();
    }

    public function update(int $id, array $data)
    {
        $folio = Folio::findOrFail($id);

        $folio->update($data);

        return $folio;
    }
}
