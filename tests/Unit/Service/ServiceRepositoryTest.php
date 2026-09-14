<?php

namespace Tests\Unit\Service;

use App\Models\Service\Service;
use App\Repositories\Service\ServiceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ServiceRepository();
    }

    public function test_it_creates_a_service(): void
    {
        $service = $this->repository->create([
            'name' => 'Laundry',
            'description' => 'Laundry service',
            'price' => 1500,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Service::class, $service);

        $this->assertDatabaseHas('services', [
            'name' => 'Laundry',
            'description' => 'Laundry service',
            'price' => 1500,
            'is_active' => true,
        ]);
    }

    public function test_it_retrieves_paginated_services(): void
    {
        Service::factory()->count(15)->create();

        $result = $this->repository->getAll();

        $this->assertCount(10, $result->items());
        $this->assertSame(15, $result->total());
        $this->assertSame(10, $result->perPage());
    }

    public function test_it_filters_services_by_name(): void
    {
        Service::factory()->create([
            'name' => 'Laundry',
        ]);

        Service::factory()->create([
            'name' => 'Airport Transfer',
        ]);

        Service::factory()->create([
            'name' => 'Restaurant',
        ]);

        request()->merge([
            'filter' => [
                'name' => 'laund',
            ],
        ]);

        $result = $this->repository->getAll();

        $this->assertCount(1, $result->items());
        $this->assertSame('Laundry', $result->items()[0]->name);
    }

    public function test_it_filters_services_by_active_status(): void
    {
        Service::factory()->create([
            'name' => 'Laundry',
            'is_active' => true,
        ]);

        Service::factory()->create([
            'name' => 'Airport Transfer',
            'is_active' => false,
        ]);

        request()->merge([
            'filter' => [
                'is_active' => false,
            ],
        ]);

        $result = $this->repository->getAll();

        $this->assertCount(1, $result->items());
        $this->assertFalse($result->items()[0]->is_active);
        $this->assertSame('Airport Transfer', $result->items()[0]->name);
    }

    public function test_it_sorts_services_by_price(): void
    {
        Service::factory()->create([
            'name' => 'Laundry',
            'price' => 1500,
        ]);

        Service::factory()->create([
            'name' => 'Airport Transfer',
            'price' => 3000,
        ]);

        Service::factory()->create([
            'name' => 'Restaurant',
            'price' => 2500,
        ]);

        request()->merge([
            'sort' => 'price',
        ]);

        $result = $this->repository->getAll();

        $prices = collect($result->items())
            ->pluck('price')
            ->map(fn($price) => (float) $price)
            ->values()
            ->all();

        $this->assertSame(
            [1500.0, 2500.0, 3000.0],
            $prices
        );
    }

    public function test_it_finds_a_service_by_id(): void
    {
        $service = Service::factory()->create();

        $result = $this->repository->findById($service->id);

        $this->assertInstanceOf(Service::class, $result);
        $this->assertSame($service->id, $result->id);
    }

    public function test_it_throws_an_exception_when_service_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findById(999999);
    }

    public function test_it_updates_a_service(): void
    {
        $service = Service::factory()->create([
            'name' => 'Laundry',
            'description' => 'Basic laundry',
            'price' => 1500,
            'is_active' => true,
        ]);

        $updated = $this->repository->update($service->id, [
            'name' => 'Premium Laundry',
            'description' => 'Premium laundry service',
            'price' => 2000,
            'is_active' => false,
        ]);

        $this->assertSame($service->id, $updated->id);
        $this->assertSame('Premium Laundry', $updated->name);
        $this->assertSame(
            'Premium laundry service',
            $updated->description
        );
        $this->assertSame('2000.00', $updated->price);
        $this->assertFalse($updated->is_active);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Premium Laundry',
            'price' => 2000,
            'is_active' => false,
        ]);
    }

    public function test_it_deletes_a_service(): void
    {
        $service = Service::factory()->create();

        $this->repository->delete($service->id);

        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }

    public function test_it_throws_an_exception_when_deleting_nonexistent_service(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->delete(999999);
    }
}
