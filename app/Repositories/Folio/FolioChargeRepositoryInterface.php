<?php

namespace App\Repositories\Folio;

interface FolioChargeRepositoryInterface
{
    public function getByFolio(int $folioId);

    public function create(array $data);

    public function findById(int $id);

    public function update(int $id, array $data);
}
