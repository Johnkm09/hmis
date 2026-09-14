<?php

use App\Models\Folio\Folio;
use App\Models\Reservation\Reservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsFolioUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
};

/*
|--------------------------------------------------------------------------
| Open Folio
|--------------------------------------------------------------------------
*/

test('receptionist can open a folio for a reservation', function () {
    actingAsFolioUser('receptionist');

    $reservation = Reservation::factory()->create();

    $response = $this->postJson(
        "/api/v1/reservations/{$reservation->id}/folio"
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.reservation_id', $reservation->id)
        ->assertJsonPath('data.status', 'open');

    $this->assertDatabaseHas('folios', [
        'reservation_id' => $reservation->id,
        'status' => 'open',
    ]);
});

test('manager can open a folio for a reservation', function () {
    actingAsFolioUser('manager');

    $reservation = Reservation::factory()->create();

    $response = $this->postJson(
        "/api/v1/reservations/{$reservation->id}/folio"
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.reservation_id', $reservation->id)
        ->assertJsonPath('data.status', 'open');
});

test('user cannot open a folio for a reservation', function () {
    actingAsFolioUser('user');

    $reservation = Reservation::factory()->create();

    $response = $this->postJson(
        "/api/v1/reservations/{$reservation->id}/folio"
    );

    $response->assertForbidden();

    $this->assertDatabaseMissing('folios', [
        'reservation_id' => $reservation->id,
    ]);
});

test('unauthenticated user cannot open a folio', function () {
    $reservation = Reservation::factory()->create();

    $response = $this->postJson(
        "/api/v1/reservations/{$reservation->id}/folio"
    );

    $response->assertUnauthorized();

    $this->assertDatabaseMissing('folios', [
        'reservation_id' => $reservation->id,
    ]);
});

test('cannot open a second folio for the same reservation', function () {
    actingAsFolioUser('receptionist');

    $reservation = Reservation::factory()->create();

    Folio::factory()->create([
        'reservation_id' => $reservation->id,
        'status' => 'open',
    ]);

    $response = $this->postJson(
        "/api/v1/reservations/{$reservation->id}/folio"
    );

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reservation');

    expect(Folio::where('reservation_id', $reservation->id)->count())
        ->toBe(1);
});

test('opening a folio for a nonexistent reservation returns not found', function () {
    actingAsFolioUser('receptionist');

    $response = $this->postJson('/api/v1/reservations/999999/folio');

    $response->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Get Folio By Reservation
|--------------------------------------------------------------------------
*/

test('receptionist can view a folio by reservation', function () {
    actingAsFolioUser('receptionist');

    $reservation = Reservation::factory()->create();

    $folio = Folio::factory()->create([
        'reservation_id' => $reservation->id,
        'status' => 'open',
    ]);

    $response = $this->getJson(
        "/api/v1/reservations/{$reservation->id}/folio"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $folio->id)
        ->assertJsonPath('data.reservation_id', $reservation->id)
        ->assertJsonPath('data.status', 'open');
});

test('user cannot view a folio by reservation', function () {
    actingAsFolioUser('user');

    $reservation = Reservation::factory()->create();

    Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    $response = $this->getJson(
        "/api/v1/reservations/{$reservation->id}/folio"
    );

    $response->assertForbidden();
});

test('unauthenticated user cannot view a folio by reservation', function () {
    $reservation = Reservation::factory()->create();

    Folio::factory()->create([
        'reservation_id' => $reservation->id,
    ]);

    $response = $this->getJson(
        "/api/v1/reservations/{$reservation->id}/folio"
    );

    $response->assertUnauthorized();
});

test('viewing a reservation without a folio returns not found', function () {
    actingAsFolioUser('receptionist');

    $reservation = Reservation::factory()->create();

    $response = $this->getJson(
        "/api/v1/reservations/{$reservation->id}/folio"
    );

    $response->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Show Folio
|--------------------------------------------------------------------------
*/

test('receptionist can view a specific folio', function () {
    actingAsFolioUser('receptionist');

    $folio = Folio::factory()->create();

    $response = $this->getJson(
        "/api/v1/folios/{$folio->id}"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $folio->id)
        ->assertJsonPath('data.reservation_id', $folio->reservation_id)
        ->assertJsonPath('data.status', $folio->status);
});

test('user cannot view a specific folio', function () {
    actingAsFolioUser('user');

    $folio = Folio::factory()->create();

    $response = $this->getJson(
        "/api/v1/folios/{$folio->id}"
    );

    $response->assertForbidden();
});

test('unauthenticated user cannot view a specific folio', function () {
    $folio = Folio::factory()->create();

    $response = $this->getJson(
        "/api/v1/folios/{$folio->id}"
    );

    $response->assertUnauthorized();
});

test('viewing a nonexistent folio returns not found', function () {
    actingAsFolioUser('receptionist');

    $response = $this->getJson('/api/v1/folios/999999');

    $response->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Close Folio
|--------------------------------------------------------------------------
*/

test('receptionist can close a folio', function () {
    actingAsFolioUser('receptionist');

    $folio = Folio::factory()->create([
        'status' => 'open',
        'closed_at' => null,
    ]);

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/close"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $folio->id)
        ->assertJsonPath('data.status', 'closed');

    $this->assertDatabaseHas('folios', [
        'id' => $folio->id,
        'status' => 'closed',
    ]);

    expect(Folio::find($folio->id)->closed_at)->not->toBeNull();
});

test('manager can close a folio', function () {
    actingAsFolioUser('manager');

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/close"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'closed');
});

test('user cannot close a folio', function () {
    actingAsFolioUser('user');

    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/close"
    );

    $response->assertForbidden();

    $this->assertDatabaseHas('folios', [
        'id' => $folio->id,
        'status' => 'open',
    ]);
});

test('unauthenticated user cannot close a folio', function () {
    $folio = Folio::factory()->create([
        'status' => 'open',
    ]);

    $response = $this->postJson(
        "/api/v1/folios/{$folio->id}/close"
    );

    $response->assertUnauthorized();

    $this->assertDatabaseHas('folios', [
        'id' => $folio->id,
        'status' => 'open',
    ]);
});

test('closing a nonexistent folio returns not found', function () {
    actingAsFolioUser('receptionist');

    $response = $this->postJson(
        '/api/v1/folios/999999/close'
    );

    $response->assertNotFound();
});
