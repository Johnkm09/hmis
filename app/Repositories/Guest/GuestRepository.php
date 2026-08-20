<?php

namespace App\Repositories\Guest;

use App\Models\Guest\Guest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GuestRepository implements GuestRepositoryInterface
{    
    public function getAll()
    {
        return QueryBuilder::for(Guest::class)
        ->allowedFilters([
            AllowedFilter::partial('first_name'),
            AllowedFilter::partial('last_name'),
            AllowedFilter::partial('email'),
            AllowedFilter::exact('country'),
            AllowedFilter::exact('city'),
        ])->allowedSorts([
            'first_name',
            'last_name',
            'created_at'
        ])->latest()
        ->paginate(
            request('per_page', 10)
        );
    }

    public function create(array $data)
    {
        return Guest::create($data);
    }

    public function findById(int $id)
    {
        return Guest::findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $guest = Guest::findOrFail($id);

        $guest->update($data);

        return $guest;
    }

     public function delete(int $id): void
     {
        $guest = Guest::findOrFail($id);

        $guest->delete();
     }
}
