<?php

namespace App\Services;

use App\Models\Folio\FolioCharge;
use App\Repositories\Folio\FolioChargeRepositoryInterface;
use App\Repositories\Folio\FolioRepositoryInterface;
use App\Repositories\Service\ServiceRepositoryInterface;
use DomainException;

class FolioChargeService
{
    public function __construct(
        private FolioChargeRepositoryInterface $repository,
        private FolioRepositoryInterface $folioRepository,
        private ServiceRepositoryInterface $serviceRepository
    ) {}

    public function getByFolio(int $folioId)
    {
        return $this->repository->getByFolio($folioId);
    }

    public function create(array $data): FolioCharge
    {
        $folio = $this->folioRepository->findById($data['folio_id']);

        $this->ensureFolioIsOpen($folio->status);

        $this->validateServiceCharge($data);

        $data['amount'] = $this->calculateAmount(
            $data['quantity'],
            $data['unit_price']
        );

        return $this->repository->create($data);
    }

    public function findById(int $id): FolioCharge
    {
        return $this->repository->findById($id);
    }

    public function update(int $id, array $data): FolioCharge
    {
        $charge = $this->repository->findById($id);

        $folio = $this->folioRepository->findById($charge->folio_id);

        $this->ensureFolioIsOpen($folio->status);

        $quantity = $data['quantity'] ?? $charge->quantity;
        $unitPrice = $data['unit_price'] ?? $charge->unit_price;

        $data['amount'] = $this->calculateAmount(
            $quantity,
            $unitPrice
        );

        return $this->repository->update($id, $data);
    }

    private function ensureFolioIsOpen(string $status): void
    {
        if ($status !== 'open') {
            throw new DomainException(
                'Cannot modify charges on a closed folio.'
            );
        }
    }

    private function validateServiceCharge(array $data): void
    {
        if ($data['type'] === 'service') {
            if (empty($data['service_id'])) {
                throw new DomainException(
                    'A service is required for a service charge.'
                );
            }

            $service = $this->serviceRepository->findById(
                $data['service_id']
            );

            if (!$service->is_active) {
                throw new DomainException(
                    'Cannot charge an inactive service.'
                );
            }

            return;
        }

        if (!empty($data['service_id'])) {
            throw new DomainException(
                'Only service charges can reference a service.'
            );
        }
    }

    private function calculateAmount(
        float|int|string $quantity,
        float|int|string $unitPrice
    ): float {
        return round(
            (float) $quantity * (float) $unitPrice,
            2
        );
    }
}
