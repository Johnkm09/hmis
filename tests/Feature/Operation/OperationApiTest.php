<?php

use App\Models\Contract\Operation;
use App\Models\Guest\Guest;
use App\Models\Reservation\Reservation;
use App\Models\Room\Room;
use App\Models\RoomType\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Test Helpers
|--------------------------------------------------------------------------
*/

function operationUser(): User
{
    // Create the permissions required by the Operations policy.
    Permission::findOrCreate('view operations', 'web');
    Permission::findOrCreate('create operations', 'web');

    $user = User::factory()->create();

    $user->givePermissionTo([
        'view operations',
        'create operations',
    ]);

    return $user;
}

function operationRoom(string $status = 'available'): Room
{
    $roomType = RoomType::factory()->create();

    return Room::factory()->create([
        'room_type_id' => $roomType->id,
        'status' => $status,
        'is_active' => true,
    ]);
}

function operationGuest(): Guest
{
    return Guest::factory()->create();
}

function operationReservation(
    Guest $guest,
    Room $room,
    string $status = 'pending'
): Reservation {
    return Reservation::factory()->create([
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => '2026-09-13',
        'check_out' => '2026-09-15',
        'number_of_guests' => 2,
        'status' => $status,
    ]);
}

/*
|--------------------------------------------------------------------------
| Walk-In Tests
|--------------------------------------------------------------------------
*/

test('authenticated user can complete a walk-in', function () {
    $user = operationUser();
    $roomType = RoomType::factory()->create([
        'max_occupancy' => 2,
    ]);

    $room = Room::factory()->create([
        'room_type_id' => $roomType->id,
        'status' => 'available',
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/walk-in', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'id_number' => 'ID123456',
        'phone_number' => '0712345678',
        'email' => 'john@example.com',
        'country' => 'Kenya',
        'city' => 'Nairobi',
        'address' => '123 Main Street',
        'room_id' => $room->id,
        'check_in' => '2026-09-13',
        'check_out' => '2026-09-15',
        'number_of_guests' => 2,
        'notes' => 'Walk-in guest.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.type', 'check_in')
        ->assertJsonPath('data.notes', 'Walk-in guest.');

    $this->assertDatabaseHas('guests', [
        'id_number' => 'ID123456',
    ]);

    $this->assertDatabaseHas('reservations', [
        'room_id' => $room->id,
        'status' => 'checked_in',
    ]);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'status' => 'occupied',
    ]);

    $this->assertDatabaseHas('operations', [
        'type' => 'check_in',
        'performed_by' => $user->id,
    ]);
});

test('walk-in reuses an existing guest', function () {
    $user = operationUser();
    $roomType = RoomType::factory()->create([
        'max_occupancy' => 2,
    ]);

    $room = Room::factory()->create([
        'room_type_id' => $roomType->id,
        'status' => 'available',
        'is_active' => true,
    ]);

    $guest = Guest::factory()->create([
        'id_number' => 'EXISTING123',
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/walk-in', [
        'first_name' => $guest->first_name,
        'last_name' => $guest->last_name,
        'id_number' => $guest->id_number,
        'phone_number' => $guest->phone_number,
        'email' => $guest->email,
        'country' => $guest->country,
        'city' => $guest->city,
        'address' => $guest->address,
        'room_id' => $room->id,
        'check_in' => '2026-09-13',
        'check_out' => '2026-09-15',
        'number_of_guests' => 2,
    ]);

    $response->assertCreated();

    expect(Guest::where('id_number', 'EXISTING123')->count())->toBe(1);

    $this->assertDatabaseHas('reservations', [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'status' => 'checked_in',
    ]);
});

test('walk-in rejects an unavailable room', function () {
    $user = operationUser();
    $room = operationRoom('occupied');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/walk-in', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'id_number' => 'ID123456',
        'phone_number' => '0712345678',
        'country' => 'Kenya',
        'city' => 'Nairobi',
        'room_id' => $room->id,
        'check_in' => '2026-09-13',
        'check_out' => '2026-09-15',
        'number_of_guests' => 2,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['room_id']);
});

test('walk-in rejects an invalid date range', function () {
    $user = operationUser();
    $room = operationRoom();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/walk-in', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'id_number' => 'ID123456',
        'phone_number' => '0712345678',
        'country' => 'Kenya',
        'city' => 'Nairobi',
        'room_id' => $room->id,
        'check_in' => '2026-09-15',
        'check_out' => '2026-09-13',
        'number_of_guests' => 2,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['check_out']);
});

/*
|--------------------------------------------------------------------------
| Check-In Tests
|--------------------------------------------------------------------------
*/

test('authenticated user can check in a pending reservation', function () {
    $user = operationUser();

    $guest = operationGuest();
    $room = operationRoom('reserved');

    $reservation = operationReservation($guest, $room);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/check-in', [
        'reservation_id' => $reservation->id,
        'notes' => 'Guest arrived.',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.type', 'check_in')
        ->assertJsonPath('data.reservation_id', $reservation->id)
        ->assertJsonPath('data.notes', 'Guest arrived.');

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'status' => 'checked_in',
    ]);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'status' => 'occupied',
    ]);

    $this->assertDatabaseHas('operations', [
        'reservation_id' => $reservation->id,
        'type' => 'check_in',
        'performed_by' => $user->id,
    ]);
});

test('check-in rejects a reservation that is not pending', function () {
    $user = operationUser();

    $guest = operationGuest();
    $room = operationRoom('reserved');

    $reservation = operationReservation($guest, $room, 'confirmed');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/check-in', [
        'reservation_id' => $reservation->id,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reservation']);
});

test('check-in rejects a room that is not reserved', function () {
    $user = operationUser();

    $guest = operationGuest();
    $room = operationRoom('available');

    $reservation = operationReservation($guest, $room);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/check-in', [
        'reservation_id' => $reservation->id,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['room_id']);
});

/*
|--------------------------------------------------------------------------
| Check-Out Tests
|--------------------------------------------------------------------------
*/

test('authenticated user can check out a checked-in reservation', function () {
    $user = operationUser();

    $guest = operationGuest();
    $room = operationRoom('occupied');

    $reservation = operationReservation($guest, $room, 'checked_in');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/check-out', [
        'reservation_id' => $reservation->id,
        'notes' => 'Guest checked out.',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.type', 'check_out')
        ->assertJsonPath('data.reservation_id', $reservation->id)
        ->assertJsonPath('data.notes', 'Guest checked out.');

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'status' => 'checked_out',
    ]);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'status' => 'available',
    ]);

    $this->assertDatabaseHas('operations', [
        'reservation_id' => $reservation->id,
        'type' => 'check_out',
        'performed_by' => $user->id,
    ]);
});

test('check-out rejects a reservation that is not checked in', function () {
    $user = operationUser();

    $guest = operationGuest();
    $room = operationRoom('reserved');

    $reservation = operationReservation($guest, $room, 'pending');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/check-out', [
        'reservation_id' => $reservation->id,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reservation']);
});

test('check-out rejects a room that is not occupied', function () {
    $user = operationUser();

    $guest = operationGuest();
    $room = operationRoom('available');

    $reservation = operationReservation($guest, $room, 'checked_in');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/check-out', [
        'reservation_id' => $reservation->id,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['room_id']);
});

/*
|--------------------------------------------------------------------------
| Authentication and Authorization Tests
|--------------------------------------------------------------------------
*/

test('unauthenticated user cannot perform an operation', function () {
    $room = operationRoom();

    $response = $this->postJson('/api/v1/operations/walk-in', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'id_number' => 'ID123456',
        'phone_number' => '0712345678',
        'country' => 'Kenya',
        'city' => 'Nairobi',
        'room_id' => $room->id,
        'check_in' => '2026-09-13',
        'check_out' => '2026-09-15',
        'number_of_guests' => 2,
    ]);

    $response->assertUnauthorized();
});

test('user without operation permission cannot perform an operation', function () {
    $user = User::factory()->create();

    $guest = operationGuest();
    $room = operationRoom('reserved');

    $reservation = operationReservation($guest, $room);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/operations/check-in', [
        'reservation_id' => $reservation->id,
    ]);

    $response->assertForbidden();
});
