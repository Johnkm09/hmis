<?php

use App\Models\Service\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsServiceUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

/*
|--------------------------------------------------------------------------
| View Services
|--------------------------------------------------------------------------
*/

test('authenticated user can view services list', function () {

    actingAsServiceUser('user');

    Service::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/services');

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data',
    ]);
});

test('authenticated user can view a specific service', function () {

    actingAsServiceUser('user');

    $service = Service::factory()->create();

    $response = $this->getJson("/api/v1/services/{$service->id}");

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $service->id);
    $response->assertJsonPath('data.name', $service->name);
    $response->assertJsonPath('data.price', $service->price);
});

test('unauthenticated user cannot view services list', function () {

    $response = $this->getJson('/api/v1/services');

    $response->assertStatus(401);
});

test('unauthenticated user cannot view a specific service', function () {

    $service = Service::factory()->create();

    $response = $this->getJson("/api/v1/services/{$service->id}");

    $response->assertStatus(401);
});

test('non-existent service returns 404', function () {

    actingAsServiceUser('user');

    $response = $this->getJson('/api/v1/services/999999');

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Create Services
|--------------------------------------------------------------------------
*/

test('manager can create service', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->make();

    $response = $this->postJson('/api/v1/services', [
        'name' => $service->name,
        'description' => $service->description,
        'price' => $service->price,
        'is_active' => $service->is_active,
    ]);

    $response->assertStatus(201);

    $response->assertJsonPath('data.name', $service->name);

    $this->assertDatabaseHas('services', [
        'name' => $service->name,
    ]);
});

test('user cannot create service', function () {

    actingAsServiceUser('user');

    $service = Service::factory()->make();

    $response = $this->postJson('/api/v1/services', [
        'name' => $service->name,
        'description' => $service->description,
        'price' => $service->price,
        'is_active' => $service->is_active,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('services', [
        'name' => $service->name,
    ]);
});

test('unauthenticated user cannot create service', function () {

    $service = Service::factory()->make();

    $response = $this->postJson('/api/v1/services', [
        'name' => $service->name,
        'description' => $service->description,
        'price' => $service->price,
        'is_active' => $service->is_active,
    ]);

    $response->assertStatus(401);

    $this->assertDatabaseMissing('services', [
        'name' => $service->name,
    ]);
});

test('service creation rejects missing name', function () {

    actingAsServiceUser('manager');

    $response = $this->postJson('/api/v1/services', [
        'description' => 'Laundry service',
        'price' => 1500,
        'is_active' => true,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'name',
    ]);
});

test('service creation rejects duplicate name', function () {

    actingAsServiceUser('manager');

    $existingService = Service::factory()->create();

    $response = $this->postJson('/api/v1/services', [
        'name' => $existingService->name,
        'description' => 'Another description',
        'price' => 2000,
        'is_active' => true,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'name',
    ]);
});

test('service creation rejects invalid price', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->make();

    $response = $this->postJson('/api/v1/services', [
        'name' => $service->name,
        'description' => $service->description,
        'price' => '-100.00',
        'is_active' => true,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'price',
    ]);
});

test('service creation rejects missing price', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->make();

    $response = $this->postJson('/api/v1/services', [
        'name' => $service->name,
        'description' => $service->description,
        'is_active' => true,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'price',
    ]);
});

test('service creation rejects invalid active status', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->make();

    $response = $this->postJson('/api/v1/services', [
        'name' => $service->name,
        'description' => $service->description,
        'price' => $service->price,
        'is_active' => 'invalid',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'is_active',
    ]);
});

test('service can be created without description', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->make();

    $response = $this->postJson('/api/v1/services', [
        'name' => $service->name,
        'price' => $service->price,
        'is_active' => true,
    ]);

    $response->assertStatus(201);

    $response->assertJsonPath('data.name', $service->name);

    $this->assertDatabaseHas('services', [
        'name' => $service->name,
        'description' => null,
    ]);
});

/*
|--------------------------------------------------------------------------
| Update Services
|--------------------------------------------------------------------------
*/

test('manager can update service', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->create();

    $response = $this->putJson("/api/v1/services/{$service->id}", [
        'name' => 'Premium Laundry',
        'description' => 'Premium laundry service',
        'price' => '2500.00',
        'is_active' => true,
    ]);

    $response->assertStatus(200);

    $response->assertJsonPath('data.name', 'Premium Laundry');

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name' => 'Premium Laundry',
        'description' => 'Premium laundry service',
        'price' => '2500.00',
        'is_active' => true,
    ]);
});

test('user cannot update service', function () {

    actingAsServiceUser('user');

    $service = Service::factory()->create();

    $response = $this->putJson("/api/v1/services/{$service->id}", [
        'name' => 'Updated Service',
        'description' => 'Updated description',
        'price' => '3000.00',
        'is_active' => false,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name' => $service->name,
        'price' => $service->price,
    ]);
});

test('unauthenticated user cannot update service', function () {

    $service = Service::factory()->create();

    $response = $this->putJson("/api/v1/services/{$service->id}", [
        'name' => 'Updated Service',
        'price' => '3000.00',
    ]);

    $response->assertStatus(401);

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name' => $service->name,
    ]);
});

test('manager can partially update service', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->create();

    $response = $this->patchJson("/api/v1/services/{$service->id}", [
        'price' => '3000.00',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'price' => '3000.00',
    ]);
});

test('user cannot partially update service', function () {

    actingAsServiceUser('user');

    $service = Service::factory()->create();

    $response = $this->patchJson("/api/v1/services/{$service->id}", [
        'price' => '3000.00',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'price' => $service->price,
    ]);
});

test('non-existent service cannot be updated', function () {

    actingAsServiceUser('manager');

    $response = $this->putJson('/api/v1/services/999999', [
        'name' => 'Updated Service',
        'description' => 'Updated description',
        'price' => '2500.00',
        'is_active' => true,
    ]);

    $response->assertStatus(404);
});

test('service update rejects duplicate name', function () {

    actingAsServiceUser('manager');

    $existingService = Service::factory()->create();
    $service = Service::factory()->create();

    $response = $this->patchJson("/api/v1/services/{$service->id}", [
        'name' => $existingService->name,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'name',
    ]);
});

test('service can be updated without changing its own name', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->create();

    $response = $this->patchJson("/api/v1/services/{$service->id}", [
        'name' => $service->name,
        'price' => '3000.00',
    ]);

    $response->assertStatus(200);

    $response->assertJsonPath(
        'data.name',
        $service->name
    );

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name' => $service->name,
        'price' => '3000.00',
    ]);
});

test('service update rejects invalid price', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->create();

    $response = $this->patchJson("/api/v1/services/{$service->id}", [
        'price' => '-100.00',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'price',
    ]);
});

test('service update rejects invalid active status', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->create();

    $response = $this->patchJson("/api/v1/services/{$service->id}", [
        'is_active' => 'invalid',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'is_active',
    ]);
});

test('service update accepts nullable description', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->create([
        'description' => 'Original description',
    ]);

    $response = $this->patchJson("/api/v1/services/{$service->id}", [
        'description' => null,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'description' => null,
    ]);
});

/*
|--------------------------------------------------------------------------
| Delete Services
|--------------------------------------------------------------------------
*/

test('manager can delete service', function () {

    actingAsServiceUser('manager');

    $service = Service::factory()->create();

    $response = $this->deleteJson("/api/v1/services/{$service->id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('services', [
        'id' => $service->id,
    ]);
});

test('user cannot delete service', function () {

    actingAsServiceUser('user');

    $service = Service::factory()->create();

    $response = $this->deleteJson("/api/v1/services/{$service->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
    ]);
});

test('unauthenticated user cannot delete service', function () {

    $service = Service::factory()->create();

    $response = $this->deleteJson("/api/v1/services/{$service->id}");

    $response->assertStatus(401);

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
    ]);
});

test('non-existent service cannot be deleted', function () {

    actingAsServiceUser('manager');

    $response = $this->deleteJson('/api/v1/services/999999');

    $response->assertStatus(404);
});
