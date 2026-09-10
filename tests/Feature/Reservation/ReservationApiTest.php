<?php

use App\Models\Guest\Guest;
use App\Models\Reservation\Reservation;
use App\Models\Room\Room;
use App\Models\RoomType\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function reservationUser(string $permission = 'view reservations'): User
{
    Permission::findOrCreate($permission, 'web');

    $user = User::factory()->create();

    $user->givePermissionTo($permission);

    return $user;
}

function reservationRoom(array $attributes = []): Room
{
    $roomType = RoomType::factory()->create([
        'max_occupancy' => 2,
    ]);

    return Room::factory()->create(array_merge([
        'room_type_id' => $roomType->id,
        'price' => '200.00',
        'is_active' => true,
    ], $attributes));
}

test('authenticated user can retrieve reservations', function () {
    $user = reservationUser();

    $guest = Guest::factory()->create();
    $room = reservationRoom();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/reservations');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data',
        ]);
});

test('unauthenticated user cannot retrieve reservations', function () {
    $response = $this->getJson('/api/v1/reservations');

    $response->assertUnauthorized();
});

test('user without reservation permission cannot retrieve reservations', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/reservations');

    $response->assertForbidden();
});

test('user with create permission can create a reservation', function () {
    $user = reservationUser('create reservations');

    $guest = Guest::factory()->create();

    $room = reservationRoom([
        'price' => '200.00',
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/reservations', [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-10',
        'check_out' => '2026-10-15',
        'number_of_guests' => 2,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.guest_id', $guest->id)
        ->assertJsonPath('data.room_id', $room->id)
        ->assertJsonPath('data.number_of_guests', 2)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.nightly_rate', '200.00')
        ->assertJsonPath('data.total_amount', '1000.00');

    $this->assertDatabaseHas('reservations', [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-10 00:00:00',
        'check_out' => '2026-10-15 00:00:00',
        'number_of_guests' => 2,
        'nightly_rate' => '200.00',
        'total_amount' => '1000.00',
        'status' => 'pending',
    ]);
});

test('creating a reservation requires valid data', function () {
    $user = reservationUser('create reservations');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/reservations', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'guest_id',
            'room_id',
            'check_in',
            'check_out',
            'number_of_guests',
        ]);
});

test('reservation creation rejects an invalid date range', function () {
    $user = reservationUser('create reservations');

    $guest = Guest::factory()->create();
    $room = reservationRoom();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/reservations', [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-15',
        'check_out' => '2026-10-10',
        'number_of_guests' => 2,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['check_out']);
});

test('reservation creation rejects guests exceeding room capacity', function () {
    $user = reservationUser('create reservations');

    $guest = Guest::factory()->create();

    $roomType = RoomType::factory()->create([
        'max_occupancy' => 2,
    ]);

    $room = Room::factory()->create([
        'room_type_id' => $roomType->id,
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/reservations', [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-10',
        'check_out' => '2026-10-15',
        'number_of_guests' => 3,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['number_of_guests']);
});

test('reservation creation rejects an inactive room', function () {
    $user = reservationUser('create reservations');

    $guest = Guest::factory()->create();

    $room = reservationRoom([
        'is_active' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/reservations', [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-10',
        'check_out' => '2026-10-15',
        'number_of_guests' => 2,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['room_id']);
});

test('reservation creation rejects overlapping dates', function () {
    $user = reservationUser('create reservations');

    $guest = Guest::factory()->create();
    $room = reservationRoom();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-10',
        'check_out' => '2026-10-15',
    ]);

    $newGuest = Guest::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/reservations', [
        'guest_id' => $newGuest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-12',
        'check_out' => '2026-10-18',
        'number_of_guests' => 2,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['room_id']);
});

test('reservation creation allows back to back dates', function () {
    $user = reservationUser('create reservations');

    $guest = Guest::factory()->create();
    $room = reservationRoom();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-10',
        'check_out' => '2026-10-15',
    ]);

    $newGuest = Guest::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/reservations', [
        'guest_id' => $newGuest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-15',
        'check_out' => '2026-10-20',
        'number_of_guests' => 2,
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('reservations', [
        'guest_id' => $newGuest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-15 00:00:00',
        'check_out' => '2026-10-20 00:00:00',
    ]);
});

test('user can retrieve a single reservation', function () {
    $user = reservationUser();

    $guest = Guest::factory()->create();
    $room = reservationRoom();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/reservations/{$reservation->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $reservation->id)
        ->assertJsonPath('data.guest_id', $guest->id)
        ->assertJsonPath('data.room_id', $room->id);
});

test('user can update a reservation', function () {
    $user = reservationUser('update reservations');

    $guest = Guest::factory()->create();
    $room = reservationRoom();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-10-10',
        'check_out' => '2026-10-15',
        'number_of_guests' => 1,
        'nightly_rate' => '200.00',
        'total_amount' => '1000.00',
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/v1/reservations/{$reservation->id}", [
        'check_in' => '2026-10-11',
        'check_out' => '2026-10-16',
        'number_of_guests' => 2,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.check_in', '2026-10-11T00:00:00.000000Z')
        ->assertJsonPath('data.check_out', '2026-10-16T00:00:00.000000Z')
        ->assertJsonPath('data.number_of_guests', 2)
        ->assertJsonPath('data.total_amount', '1000.00');
});

test('user can delete a reservation', function () {
    $user = reservationUser('delete reservations');

    $guest = Guest::factory()->create();
    $room = reservationRoom();

    $reservation = Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/v1/reservations/{$reservation->id}");

    $response->assertOk();

    $this->assertDatabaseMissing('reservations', [
        'id' => $reservation->id,
    ]);
});

test('reservations can be filtered by room', function () {
    $user = reservationUser();

    $guest = Guest::factory()->create();

    $room = reservationRoom();
    $otherRoom = reservationRoom();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $otherRoom->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson(
        "/api/v1/reservations?filter[room_id]={$room->id}"
    );

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.room_id', $room->id);
});

test('reservations can be filtered by status', function () {
    $user = reservationUser();

    $guest = Guest::factory()->create();
    $room = reservationRoom();

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'pending',
    ]);

    Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'confirmed',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson(
        '/api/v1/reservations?filter[status]=confirmed'
    );

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'confirmed');
});

test('reservations can be paginated', function () {
    $user = reservationUser();

    $guest = Guest::factory()->create();
    $room = reservationRoom();

    Reservation::factory()->count(3)->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson(
        '/api/v1/reservations?per_page=2'
    );

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data',
            'meta',
        ]);
});
