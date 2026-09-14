<?php

namespace Tests\Unit\Service;

use App\Models\Service\Service;
use App\Repositories\Service\ServiceRepositoryInterface;
use App\Services\ServiceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class ServiceServiceTest extends TestCase
{
    private ServiceRepositoryInterface $repository;

    private ServiceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ServiceRepositoryInterface::class);

        $this->service = new ServiceService(
            $this->repository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_retrieves_all_services(): void
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->repository
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($paginator);

        $result = $this->service->getAll();

        $this->assertSame($paginator, $result);
    }

    public function test_it_creates_a_service(): void
    {
        $data = [
            'name' => 'Laundry',
            'description' => 'Laundry service',
            'price' => 1500,
            'is_active' => true,
        ];

        $service = new Service($data);

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($service);

        $result = $this->service->create($data);

        $this->assertSame($service, $result);
    }

    public function test_it_finds_a_service_by_id(): void
    {
        $service = new Service([
            'name' => 'Laundry',
            'description' => 'Laundry service',
            'price' => 1500,
            'is_active' => true,
        ]);

        $service->id = 10;

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(10)
            ->andReturn($service);

        $result = $this->service->findById(10);

        $this->assertSame($service, $result);
    }

    public function test_it_updates_a_service(): void
    {
        $data = [
            'name' => 'Premium Laundry',
            'description' => 'Premium laundry service',
            'price' => 2000,
            'is_active' => true,
        ];

        $service = new Service($data);

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with(10, $data)
            ->andReturn($service);

        $result = $this->service->update(10, $data);

        $this->assertSame($service, $result);
    }

    public function test_it_deletes_a_service(): void
    {
        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with(10);

        $this->service->delete(10);

        $this->assertTrue(true);
    }
}
