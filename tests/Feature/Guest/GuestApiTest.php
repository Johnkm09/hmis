```php
<?php

use App\Models\User;
use App\Models\Guest\Guest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsGuestUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

test('authenticated user can view guests list', function () {

    actingAsGuestUser('user');

    Guest::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/guests');

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data',
    ]);
});

test('authenticated user can view a specific guest', function () {

    actingAsGuestUser('user');

    $guest = Guest::factory()->create();

    $response = $this->getJson("/api/v1/guests/{$guest->id}");

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $guest->id);
    $response->assertJsonPath('data.first_name', $guest->first_name);
    $response->assertJsonPath('data.last_name', $guest->last_name);
    $response->assertJsonPath('data.id_number', $guest->id_number);
});

test('unauthenticated user cannot view guests list', function () {

    $response = $this->getJson('/api/v1/guests');

    $response->assertStatus(401);
});

test('unauthenticated user cannot view a specific guest', function () {

    $guest = Guest::factory()->create();

    $response = $this->getJson("/api/v1/guests/{$guest->id}");

    $response->assertStatus(401);
});

test('unauthenticated user cannot create guest', function () {

    $guest = Guest::factory()->make();

    $response = $this->postJson('/api/v1/guests', [
        'first_name'   => $guest->first_name,
        'last_name'    => $guest->last_name,
        'id_number'    => $guest->id_number,
        'phone_number' => $guest->phone_number,
        'email'        => $guest->email,
        'country'      => $guest->country,
        'city'         => $guest->city,
        'address'      => $guest->address,
    ]);

    $response->assertStatus(401);
});

test('user cannot create guest', function () {

    actingAsGuestUser('user');

    $guest = Guest::factory()->make();

    $response = $this->postJson('/api/v1/guests', [
        'first_name'   => $guest->first_name,
        'last_name'    => $guest->last_name,
        'id_number'    => $guest->id_number,
        'phone_number' => $guest->phone_number,
        'email'        => $guest->email,
        'country'      => $guest->country,
        'city'         => $guest->city,
        'address'      => $guest->address,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('guests', [
        'first_name' => $guest->first_name,
        'last_name'  => $guest->last_name,
    ]);
});

test('manager can create guest', function () {

    actingAsGuestUser('manager');

    $guest = Guest::factory()->make();

    $response = $this->postJson('/api/v1/guests', [
        'first_name'   => $guest->first_name,
        'last_name'    => $guest->last_name,
        'id_number'    => $guest->id_number,
        'phone_number' => $guest->phone_number,
        'email'        => $guest->email,
        'country'      => $guest->country,
        'city'         => $guest->city,
        'address'      => $guest->address,
    ]);

    $response->assertStatus(201);

    $response->assertJsonPath('data.first_name', $guest->first_name);
    $response->assertJsonPath('data.last_name', $guest->last_name);
    $response->assertJsonPath('data.id_number', $guest->id_number);

    $this->assertDatabaseHas('guests', [
        'first_name'   => $guest->first_name,
        'last_name'    => $guest->last_name,
        'id_number'    => $guest->id_number,
        'phone_number' => $guest->phone_number,
    ]);
});

test('guest creation rejects missing required fields', function () {

    actingAsGuestUser('manager');

    $response = $this->postJson('/api/v1/guests', []);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'first_name',
        'last_name',
        'id_number',
        'phone_number',
        'country',
        'city',
    ]);
});

test('guest creation rejects duplicate id number', function () {

    actingAsGuestUser('manager');

    $guest = Guest::factory()->create();

    $response = $this->postJson('/api/v1/guests', [
        'first_name'   => 'Jane',
        'last_name'    => 'Doe',
        'id_number'    => $guest->id_number,
        'phone_number' => '0712345678',
        'country'      => 'Kenya',
        'city'         => 'Nairobi',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'id_number',
    ]);
});

test('guest creation rejects invalid email', function () {

    actingAsGuestUser('manager');

    $response = $this->postJson('/api/v1/guests', [
        'first_name'   => 'John',
        'last_name'    => 'Doe',
        'id_number'    => 'A1234567',
        'phone_number' => '0712345678',
        'email'        => 'invalid-email',
        'country'      => 'Kenya',
        'city'         => 'Nairobi',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'email',
    ]);
});

test('guest creation rejects invalid field types', function () {

    actingAsGuestUser('manager');

    $response = $this->postJson('/api/v1/guests', [
        'first_name'   => 123,
        'last_name'    => 456,
        'id_number'    => 789,
        'phone_number' => 789,
        'email'        => 'john@example.com',
        'country'      => 123,
        'city'         => 456,
        'address'      => 789,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'first_name',
        'last_name',
        'id_number',
        'phone_number',
        'country',
        'city',
        'address',
    ]);
});

test('authenticated user cannot update guest', function () {

    actingAsGuestUser('user');

    $guest = Guest::factory()->create();

    $response = $this->patchJson("/api/v1/guests/{$guest->id}", [
        'first_name' => 'Updated',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('guests', [
        'id'         => $guest->id,
        'first_name' => $guest->first_name,
    ]);
});

test('manager can update guest', function () {

    actingAsGuestUser('manager');

    $guest = Guest::factory()->create();

    $response = $this->patchJson("/api/v1/guests/{$guest->id}", [
        'first_name' => 'Updated',
        'city'       => 'Nairobi',
    ]);

    $response->assertStatus(200);

    $response->assertJsonPath('data.first_name', 'Updated');
    $response->assertJsonPath('data.city', 'Nairobi');
    $response->assertJsonPath('data.id_number', $guest->id_number);

    $this->assertDatabaseHas('guests', [
        'id'         => $guest->id,
        'first_name' => 'Updated',
        'city'       => 'Nairobi',
    ]);
});

test('manager can update guest id number while keeping it unique', function () {

    actingAsGuestUser('manager');

    $guest = Guest::factory()->create();

    $response = $this->patchJson("/api/v1/guests/{$guest->id}", [
        'id_number' => 'P9876543',
    ]);

    $response->assertStatus(200);

    $response->assertJsonPath('data.id_number', 'P9876543');

    $this->assertDatabaseHas('guests', [
        'id'        => $guest->id,
        'id_number' => 'P9876543',
    ]);
});

test('guest cannot be updated with another guests id number', function () {

    actingAsGuestUser('manager');

    $guest = Guest::factory()->create();
    $anotherGuest = Guest::factory()->create();

    $response = $this->patchJson("/api/v1/guests/{$guest->id}", [
        'id_number' => $anotherGuest->id_number,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'id_number',
    ]);
});

test('unauthenticated user cannot update guest', function () {

    $guest = Guest::factory()->create();

    $response = $this->patchJson("/api/v1/guests/{$guest->id}", [
        'first_name' => 'Updated',
    ]);

    $response->assertStatus(401);
});

test('non-existent guest cannot be updated', function () {

    actingAsGuestUser('manager');

    $response = $this->patchJson('/api/v1/guests/999999', [
        'first_name' => 'Updated',
    ]);

    $response->assertStatus(404);
});

test('guest update rejects invalid email', function () {

    actingAsGuestUser('manager');

    $guest = Guest::factory()->create();

    $response = $this->patchJson("/api/v1/guests/{$guest->id}", [
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'email',
    ]);
});

test('guest update rejects invalid field types', function () {

    actingAsGuestUser('manager');

    $guest = Guest::factory()->create();

    $response = $this->patchJson("/api/v1/guests/{$guest->id}", [
        'first_name' => 123,
        'city'       => 456,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'first_name',
        'city',
    ]);
});

test('authenticated user cannot delete guest', function () {

    actingAsGuestUser('user');

    $guest = Guest::factory()->create();

    $response = $this->deleteJson("/api/v1/guests/{$guest->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('guests', [
        'id'         => $guest->id,
        'deleted_at' => null,
    ]);
});

test('manager can delete guest', function () {

    actingAsGuestUser('manager');

    $guest = Guest::factory()->create();

    $response = $this->deleteJson("/api/v1/guests/{$guest->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('guests', [
        'id' => $guest->id,
    ]);
});

test('unauthenticated user cannot delete guest', function () {

    $guest = Guest::factory()->create();

    $response = $this->deleteJson("/api/v1/guests/{$guest->id}");

    $response->assertStatus(401);

    $this->assertDatabaseHas('guests', [
        'id'         => $guest->id,
        'deleted_at' => null,
    ]);
});

test('non-existent guest cannot be deleted', function () {

    actingAsGuestUser('manager');

    $response = $this->deleteJson('/api/v1/guests/999999');

    $response->assertStatus(404);
});
