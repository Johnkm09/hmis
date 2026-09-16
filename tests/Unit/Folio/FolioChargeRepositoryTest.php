<?php

namespace Tests\Unit\Folio;

use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Models\Service\Service;
use App\Models\User;
use App\Repositories\Folio\FolioChargeRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolioChargeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private FolioChargeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new FolioChargeRepository();
    }

    public function test_it_gets_all_charges_for_a_folio_with_relationships(): void
    {
        $folio = Folio::factory()->create();

        $charge = FolioCharge::factory()->create([
            'folio_id' => $folio->id,
        ]);

        FolioCharge::factory()->create();

        $charges = $this->repository->getByFolio($folio->id);

        $this->assertCount(1, $charges);
        $this->assertTrue($charges->first()->is($charge));
    }

    public function test_it_creates_a_folio_charge(): void
    {
        $folio = Folio::factory()->create();
        $service = Service::factory()->create();
        $user = User::factory()->create();

        $data = [
            'folio_id' => $folio->id,
            'service_id' => $service->id,
            'type' => 'service',
            'description' => 'Laundry service',
            'quantity' => 2,
            'unit_price' => 500,
            'amount' => 1000,
            'charged_at' => now(),
            'charged_by' => $user->id,
        ];

        $charge = $this->repository->create($data);

        $this->assertInstanceOf(FolioCharge::class, $charge);

        $this->assertDatabaseHas('folio_charges', [
            'id' => $charge->id,
            'folio_id' => $folio->id,
            'service_id' => $service->id,
            'type' => 'service',
            'description' => 'Laundry service',
            'quantity' => 2,
            'unit_price' => 500,
            'amount' => 1000,
            'charged_by' => $user->id,
        ]);
    }

    public function test_it_finds_a_charge_by_id_with_service_and_charged_by(): void
    {
        $charge = FolioCharge::factory()->create();

        $result = $this->repository->findById($charge->id);

        $this->assertTrue($result->is($charge));

        $this->assertTrue($result->relationLoaded('service'));
        $this->assertTrue($result->service->is($charge->service));

        $this->assertTrue($result->relationLoaded('chargedBy'));
        $this->assertTrue($result->chargedBy->is($charge->chargedBy));
    }

    public function test_it_updates_a_folio_charge(): void
    {
        $charge = FolioCharge::factory()->create([
            'description' => 'Original charge',
            'quantity' => 1,
            'unit_price' => 500,
            'amount' => 500,
        ]);

        $updated = $this->repository->update($charge->id, [
            'description' => 'Updated charge',
            'quantity' => 2,
            'unit_price' => 750,
            'amount' => 1500,
        ]);

        $this->assertTrue($updated->is($charge));
        $this->assertSame('Updated charge', $updated->description);
        $this->assertEquals('2.00', $updated->quantity);
        $this->assertEquals('750.00', $updated->unit_price);
        $this->assertEquals('1500.00', $updated->amount);

        $this->assertDatabaseHas('folio_charges', [
            'id' => $charge->id,
            'description' => 'Updated charge',
            'quantity' => 2,
            'unit_price' => 750,
            'amount' => 1500,
        ]);
    }

    public function test_it_throws_exception_when_finding_nonexistent_charge(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findById(999999);
    }
}
