<?php

namespace App\Repositories\Service;

use App\Models\Service\Service;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function getAll()
    {
        return QueryBuilder::for(Service::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::exact('is_active'),
            ])
            ->allowedSorts([
                'name',
                'price',
                'created_at',
            ])
            ->latest()
            ->paginate(
                request('per_page', 10)
            );
    }

    public function create(array $data)
    {
        return Service::create($data);
    }

    public function findById(int $id)
    {
        return Service::findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $service = Service::findOrFail($id);

        $service->update($data);

        return $service;
    }

    public function delete(int $id): void
    {
        $service = Service::findOrFail($id);

        $service->delete();
    }
}
