<?php

namespace Tests\Unit\Services;

use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Models\Service\Service;
use App\Repositories\Folio\FolioChargeRepositoryInterface;
use App\Repositories\Folio\FolioRepositoryInterface;
use App\Repositories\Service\ServiceRepositoryInterface;
use App\Services\FolioChargeService;
use DomainException;
use Mockery;
use Tests\TestCase;

class FolioChargeServiceTest extends TestCase
{
    private FolioChargeRepositoryInterface $repository;

    private FolioRepositoryInterface $folioRepository;

    private ServiceRepositoryInterface $serviceRepository;

    private FolioChargeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(
            FolioChargeRepositoryInterface::class
        );

        $this->folioRepository = Mockery::mock(
            FolioRepositoryInterface::class
        );

        $this->serviceRepository = Mockery::mock(
            ServiceRepositoryInterface::class
        );

        $this->service = new FolioChargeService(
            $this->repository,
            $this->folioRepository,
            $this->serviceRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_gets_charges_for_a_folio(): void
    {
        $charges = collect([
            new FolioCharge(),
            new FolioCharge(),
        ]);

        $this->repository
            ->shouldReceive('getByFolio')
            ->once()
            ->with(1)
            ->andReturn($charges);

        $result = $this->service->getByFolio(1);

        $this->assertCount(2, $result);
        $this->assertSame($charges, $result);
    }

    public function test_it_creates_a_service_charge_with_calculated_amount(): void
    {
        $charge = new FolioCharge();

        $folio = new Folio([
            'id' => 1,
            'status' => 'open',
        ]);

        $service = new Service([
            'id' => 2,
            'is_active' => true,
        ]);

        $data = [
            'folio_id' => 1,
            'service_id' => 2,
            'type' => 'service',
            'description' => 'Laundry service',
            'quantity' => 2,
            'unit_price' => 500,
            'amount' => 1,
            'charged_at' => now(),
            'charged_by' => 3,
        ];

        $expectedData = $data;
        $expectedData['amount'] = 1000;

        $this->folioRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($folio);

        $this->serviceRepository
            ->shouldReceive('findById')
            ->once()
            ->with(2)
            ->andReturn($service);

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with($expectedData)
            ->andReturn($charge);

        $result = $this->service->create($data);

        $this->assertSame($charge, $result);
    }

    public function test_it_creates_an_accommodation_charge_without_service(): void
    {
        $charge = new FolioCharge();

        $folio = new Folio([
            'id' => 1,
            'status' => 'open',
        ]);

        $data = [
            'folio_id' => 1,
            'service_id' => null,
            'type' => 'accommodation',
            'description' => 'Room charge',
            'quantity' => 2,
            'unit_price' => 5000,
            'amount' => 1,
            'charged_at' => now(),
            'charged_by' => 3,
        ];

        $expectedData = $data;
        $expectedData['amount'] = 10000;

        $this->folioRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($folio);

        $this->serviceRepository
            ->shouldNotReceive('findById');

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with($expectedData)
            ->andReturn($charge);

        $result = $this->service->create($data);

        $this->assertSame($charge, $result);
    }

    public function test_it_cannot_create_a_charge_for_a_closed_folio(): void
    {
        $folio = new Folio([
            'id' => 1,
            'status' => 'closed',
        ]);

        $data = [
            'folio_id' => 1,
            'service_id' => 2,
            'type' => 'service',
            'description' => 'Laundry service',
            'quantity' => 2,
            'unit_price' => 500,
        ];

        $this->folioRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($folio);

        $this->serviceRepository
            ->shouldNotReceive('findById');

        $this->repository
            ->shouldNotReceive('create');

        $this->expectException(DomainException::class);

        $this->expectExceptionMessage(
            'Cannot modify charges on a closed folio.'
        );

        $this->service->create($data);
    }

    public function test_it_cannot_create_a_service_charge_without_a_service(): void
    {
        $folio = new Folio([
            'id' => 1,
            'status' => 'open',
        ]);

        $data = [
            'folio_id' => 1,
            'service_id' => null,
            'type' => 'service',
            'description' => 'Laundry service',
            'quantity' => 1,
            'unit_price' => 500,
        ];

        $this->folioRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($folio);

        $this->serviceRepository
            ->shouldNotReceive('findById');

        $this->repository
            ->shouldNotReceive('create');

        $this->expectException(DomainException::class);

        $this->expectExceptionMessage(
            'A service is required for a service charge.'
        );

        $this->service->create($data);
    }

    public function test_it_cannot_charge_an_inactive_service(): void
    {
        $folio = new Folio([
            'id' => 1,
            'status' => 'open',
        ]);

        $service = new Service([
            'id' => 2,
            'is_active' => false,
        ]);

        $data = [
            'folio_id' => 1,
            'service_id' => 2,
            'type' => 'service',
            'description' => 'Laundry service',
            'quantity' => 1,
            'unit_price' => 500,
        ];

        $this->folioRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($folio);

        $this->serviceRepository
            ->shouldReceive('findById')
            ->once()
            ->with(2)
            ->andReturn($service);

        $this->repository
            ->shouldNotReceive('create');

        $this->expectException(DomainException::class);

        $this->expectExceptionMessage(
            'Cannot charge an inactive service.'
        );

        $this->service->create($data);
    }

    public function test_it_cannot_reference_a_service_for_an_other_charge(): void
    {
        $folio = new Folio([
            'id' => 1,
            'status' => 'open',
        ]);

        $data = [
            'folio_id' => 1,
            'service_id' => 2,
            'type' => 'other',
            'description' => 'Miscellaneous charge',
            'quantity' => 1,
            'unit_price' => 500,
        ];

        $this->folioRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($folio);

        $this->serviceRepository
            ->shouldNotReceive('findById');

        $this->repository
            ->shouldNotReceive('create');

        $this->expectException(DomainException::class);

        $this->expectExceptionMessage(
            'Only service charges can reference a service.'
        );

        $this->service->create($data);
    }

    public function test_it_finds_a_folio_charge_by_id(): void
    {
        $charge = new FolioCharge();

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($charge);

        $result = $this->service->findById(1);

        $this->assertSame($charge, $result);
    }

    public function test_it_updates_a_folio_charge_with_calculated_amount(): void
    {
        $charge = new FolioCharge([
            'folio_id' => 1,
            'quantity' => 1,
            'unit_price' => 500,
        ]);

        $folio = new Folio([
            'id' => 1,
            'status' => 'open',
        ]);

        $data = [
            'description' => 'Updated charge',
            'quantity' => 2,
            'unit_price' => 750,
            'amount' => 1,
        ];

        $expectedData = $data;
        $expectedData['amount'] = 1500;

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($charge);

        $this->folioRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($folio);

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with(1, $expectedData)
            ->andReturn($charge);

        $result = $this->service->update(1, $data);

        $this->assertSame($charge, $result);
    }

    public function test_it_cannot_update_a_charge_on_a_closed_folio(): void
    {
        $charge = new FolioCharge([
            'folio_id' => 1,
            'quantity' => 1,
            'unit_price' => 500,
        ]);

        $folio = new Folio([
            'id' => 1,
            'status' => 'closed',
        ]);

        $data = [
            'description' => 'Updated charge',
            'quantity' => 2,
            'unit_price' => 750,
        ];

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($charge);

        $this->folioRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($folio);

        $this->repository
            ->shouldNotReceive('update');

        $this->expectException(DomainException::class);

        $this->expectExceptionMessage(
            'Cannot modify charges on a closed folio.'
        );

        $this->service->update(1, $data);
    }
}
