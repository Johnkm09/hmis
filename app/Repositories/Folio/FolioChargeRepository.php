<?php

namespace App\Repositories\Folio;

use App\Models\Folio\FolioCharge;

class FolioChargeRepository implements FolioChargeRepositoryInterface
{
    public function getByFolio(int $folioId)
    {
        return FolioCharge::where('folio_id', $folioId)
            ->with(['service', 'chargedBy'])
            ->latest('charged_at')
            ->get();
    }

    public function create(array $data)
    {
        return FolioCharge::create($data);
    }

    public function findById(int $id)
    {
        return FolioCharge::with(['service', 'chargedBy'])->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $charge = FolioCharge::findOrFail($id);

        $charge->update($data);

        return $charge->fresh('service');
    }
}
