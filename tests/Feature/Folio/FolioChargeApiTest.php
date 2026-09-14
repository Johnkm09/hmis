<?php

namespace Tests\Feature\Folio;

use App\Models\Folio\Folio;
use App\Models\Folio\FolioCharge;
use App\Models\Service\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolioChargeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsFolioChargeUser(string $role): User
    {
        $user = User::factory()->create();

        $user->assignRole($role);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_receptionist_can_get_all_charges_for_a_folio(): void
    {
        $user = $this->actingAsFolioChargeUser('receptionist');

        $folio = Folio::factory()->create();

        FolioCharge::factory()
            ->count(2)
            ->create([
                'folio_id' => $folio->id,
                'charged_by' => $user->id,
            ]);

        $response = $this->getJson(
            "/api/v1/folios/{$folio->id}/charges"
        );

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_without_view_permission_cannot_get_folio_charges(): void
    {
        $this->actingAsFolioChargeUser('user');

        $folio = Folio::factory()->create();

        $response = $this->getJson(
            "/api/v1/folios/{$folio->id}/charges"
        );

        $response->assertForbidden();
    }

    public function test_receptionist_can_create_a_service_charge(): void
    {
        $user = $this->actingAsFolioChargeUser('receptionist');

        $folio = Folio::factory()->create();

        $service = Service::factory()->create([
            'price' => 500,
            'is_active' => true,
        ]);

        $payload = [
            'type' => 'service',
            'service_id' => $service->id,
            'description' => 'Laundry service',
            'quantity' => 2,
            'unit_price' => 500,
        ];

        $response = $this->postJson(
            "/api/v1/folios/{$folio->id}/charges",
            $payload
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.type', 'service')
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('data.description', 'Laundry service')
            ->assertJsonPath('data.quantity', '2.00')
            ->assertJsonPath('data.unit_price', '500.00')
            ->assertJsonPath('data.amount', '1000.00')
            ->assertJsonPath('data.charged_by', $user->id);

        $this->assertDatabaseHas('folio_charges', [
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

    public function test_user_without_create_permission_cannot_create_a_charge(): void
    {
        $this->actingAsFolioChargeUser('user');

        $folio = Folio::factory()->create();

        $service = Service::factory()->create([
            'is_active' => true,
        ]);

        $payload = [
            'type' => 'service',
            'service_id' => $service->id,
            'description' => 'Laundry service',
            'quantity' => 1,
            'unit_price' => 500,
        ];

        $response = $this->postJson(
            "/api/v1/folios/{$folio->id}/charges",
            $payload
        );

        $response->assertForbidden();
    }

    public function test_service_charge_requires_a_service(): void
    {
        $this->actingAsFolioChargeUser('receptionist');

        $folio = Folio::factory()->create();

        $payload = [
            'type' => 'service',
            'description' => 'Laundry service',
            'quantity' => 1,
            'unit_price' => 500,
        ];

        $response = $this->postJson(
            "/api/v1/folios/{$folio->id}/charges",
            $payload
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('service_id');
    }

    public function test_service_charge_cannot_reference_an_inactive_service(): void
    {
        $this->actingAsFolioChargeUser('receptionist');

        $folio = Folio::factory()->create();

        $service = Service::factory()->create([
            'is_active' => false,
        ]);

        $payload = [
            'type' => 'service',
            'service_id' => $service->id,
            'description' => 'Inactive service',
            'quantity' => 1,
            'unit_price' => 500,
        ];

        $response = $this->postJson(
            "/api/v1/folios/{$folio->id}/charges",
            $payload
        );

        /*
         * The service currently throws a DomainException for this
         * business rule, which Laravel currently returns as 500.
         */
        $response->assertInternalServerError();
    }

    public function test_receptionist_can_create_an_accommodation_charge_without_a_service(): void
    {
        $this->actingAsFolioChargeUser('receptionist');

        $folio = Folio::factory()->create();

        $payload = [
            'type' => 'accommodation',
            'description' => 'Room accommodation',
            'quantity' => 2,
            'unit_price' => 5000,
        ];

        $response = $this->postJson(
            "/api/v1/folios/{$folio->id}/charges",
            $payload
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.type', 'accommodation')
            ->assertJsonPath('data.service_id', null)
            ->assertJsonPath('data.amount', '10000.00');
    }

    public function test_client_cannot_control_financial_audit_fields_when_creating_a_charge(): void
    {
        $user = $this->actingAsFolioChargeUser('receptionist');

        $folio = Folio::factory()->create();

        $service = Service::factory()->create([
            'is_active' => true,
        ]);

        $payload = [
            'type' => 'service',
            'service_id' => $service->id,
            'description' => 'Laundry service',
            'quantity' => 2,
            'unit_price' => 500,

            // These fields must not be client-controlled.
            'folio_id' => 999999,
            'amount' => 1,
            'charged_by' => 999999,
            'charged_at' => '2000-01-01 00:00:00',
        ];

        $response = $this->postJson(
            "/api/v1/folios/{$folio->id}/charges",
            $payload
        );

        $response->assertCreated();

        $this->assertDatabaseHas('folio_charges', [
            'folio_id' => $folio->id,
            'amount' => 1000,
            'charged_by' => $user->id,
        ]);

        $this->assertDatabaseMissing('folio_charges', [
            'folio_id' => 999999,
            'amount' => 1,
            'charged_by' => 999999,
        ]);
    }

    public function test_receptionist_can_view_a_single_charge(): void
    {
        $this->actingAsFolioChargeUser('receptionist');

        $charge = FolioCharge::factory()->create();

        $response = $this->getJson(
            "/api/v1/folio-charges/{$charge->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $charge->id);
    }

    public function test_manager_can_update_a_charge(): void
    {
        $this->actingAsFolioChargeUser('manager');

        $charge = FolioCharge::factory()->create([
            'quantity' => 2,
            'unit_price' => 500,
            'amount' => 1000,
        ]);

        $payload = [
            'description' => 'Updated laundry service',
            'quantity' => 3,
            'unit_price' => 600,
        ];

        $response = $this->putJson(
            "/api/v1/folio-charges/{$charge->id}",
            $payload
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.description',
                'Updated laundry service'
            )
            ->assertJsonPath('data.quantity', '3.00')
            ->assertJsonPath('data.unit_price', '600.00')
            ->assertJsonPath('data.amount', '1800.00');

        $this->assertDatabaseHas('folio_charges', [
            'id' => $charge->id,
            'description' => 'Updated laundry service',
            'quantity' => 3,
            'unit_price' => 600,
            'amount' => 1800,
        ]);
    }

    public function test_user_without_update_permission_cannot_update_a_charge(): void
    {
        $this->actingAsFolioChargeUser('user');

        $charge = FolioCharge::factory()->create();

        $payload = [
            'description' => 'Unauthorized update',
        ];

        $response = $this->putJson(
            "/api/v1/folio-charges/{$charge->id}",
            $payload
        );

        $response->assertForbidden();
    }

    public function test_charge_cannot_be_created_on_a_closed_folio(): void
    {
        $this->actingAsFolioChargeUser('receptionist');

        $folio = Folio::factory()->create([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $service = Service::factory()->create([
            'is_active' => true,
        ]);

        $payload = [
            'type' => 'service',
            'service_id' => $service->id,
            'description' => 'Laundry service',
            'quantity' => 1,
            'unit_price' => 500,
        ];

        $response = $this->postJson(
            "/api/v1/folios/{$folio->id}/charges",
            $payload
        );

        /*
         * The service currently throws a DomainException for this
         * business rule, which Laravel currently returns as 500.
         */
        $response->assertInternalServerError();
    }

    public function test_charge_cannot_be_updated_on_a_closed_folio(): void
    {
        $this->actingAsFolioChargeUser('manager');

        $folio = Folio::factory()->create([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $charge = FolioCharge::factory()->create([
            'folio_id' => $folio->id,
        ]);

        $payload = [
            'description' => 'Attempted update',
        ];

        $response = $this->putJson(
            "/api/v1/folio-charges/{$charge->id}",
            $payload
        );

        /*
         * The service currently throws a DomainException for this
         * business rule, which Laravel currently returns as 500.
         */
        $response->assertInternalServerError();
    }

    public function test_client_cannot_change_protected_charge_fields_when_updating(): void
    {
        $this->actingAsFolioChargeUser('manager');

        $charge = FolioCharge::factory()->create([
            'quantity' => 2,
            'unit_price' => 500,
            'amount' => 1000,
        ]);

        $originalFolioId = $charge->folio_id;
        $originalServiceId = $charge->service_id;
        $originalType = $charge->type;
        $originalChargedBy = $charge->charged_by;

        $payload = [
            'description' => 'Updated description',
            'quantity' => 3,
            'unit_price' => 600,

            // These fields must not be client-controlled.
            'folio_id' => 999999,
            'service_id' => 999999,
            'type' => 'other',
            'amount' => 1,
            'charged_by' => 999999,
            'charged_at' => '2000-01-01 00:00:00',
        ];

        $response = $this->putJson(
            "/api/v1/folio-charges/{$charge->id}",
            $payload
        );

        $response->assertOk();

        $this->assertDatabaseHas('folio_charges', [
            'id' => $charge->id,
            'folio_id' => $originalFolioId,
            'service_id' => $originalServiceId,
            'type' => $originalType,
            'quantity' => 3,
            'unit_price' => 600,
            'amount' => 1800,
            'charged_by' => $originalChargedBy,
        ]);

        $this->assertDatabaseMissing('folio_charges', [
            'id' => $charge->id,
            'folio_id' => 999999,
            'service_id' => 999999,
            'type' => 'other',
            'amount' => 1,
            'charged_by' => 999999,
        ]);
    }
}
