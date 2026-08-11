<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Room\Room;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsRoomUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

test('authenticated user can view rooms list', function () {

    actingAsRoomUser('user');

    $room = Room::factory()->create();

    $response = $this->getJson('/api/v1/rooms');

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data',
    ]);
});

test('authenticated user can view a specific room', function () {

    actingAsRoomUser('user');

    $room = Room::factory()->create();

    $response = $this->getJson("/api/v1/rooms/{$room->id}");

    $response->assertStatus(200);

    $response->assertJsonPath('data.id', $room->id);
    $response->assertJsonPath('data.room_number', $room->room_number);
});

test('unauthenticated user cannot view a specific room', function () {

    $room = Room::factory()->create();

    $response = $this->getJson("/api/v1/rooms/{$room->id}");

    $response->assertStatus(401);
});

test('unauthenticated user cannot view rooms list', function () {

    $response = $this->getJson('/api/v1/rooms');

    $response->assertStatus(401);
});

test('unauthenticated user cannot create room', function () {

    $room = Room::factory()->make();

    $response = $this->postJson('/api/v1/rooms', [
        'room_type_id' => $room->room_type_id,
        'room_number' => $room->room_number,
        'floor_no' => $room->floor_no,
        'status' => $room->status,
        'price' => $room->price,
        'is_active' => $room->is_active,
    ]);

    $response->assertStatus(401);
});

test('user cannot create room', function () {

    actingAsRoomUser('user');

    $room = Room::factory()->make();

    $response = $this->postJson('/api/v1/rooms', [
        'room_type_id' => $room->room_type_id,
        'room_number' => $room->room_number,
        'floor_no' => $room->floor_no,
        'status' => $room->status,
        'price' => $room->price,
        'is_active' => $room->is_active,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('rooms', [
        'room_number' => $room->room_number,
    ]);
});

test('non-existent room returns 404', function () {

    actingAsRoomUser('user');

    $response = $this->getJson('/api/v1/rooms/999999');

    $response->assertStatus(404);
});


test('room creation rejects invalid room type', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->make();

    $response = $this->postJson('/api/v1/rooms', [
        'room_type_id' => 999999,
        'room_number' => $room->room_number,
        'floor_no' => $room->floor_no,
        'status' => $room->status,
        'price' => $room->price,
        'is_active' => $room->is_active,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'room_type_id',
    ]);
});

test('room creation rejects duplicate room number', function () {

    actingAsRoomUser('manager');

    $existingRoom = Room::factory()->create();

    $room = Room::factory()->make();

    $response = $this->postJson('/api/v1/rooms', [
        'room_type_id' => $room->room_type_id,
        'room_number' => $existingRoom->room_number,
        'floor_no' => $room->floor_no,
        'status' => $room->status,
        'price' => $room->price,
        'is_active' => $room->is_active,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'room_number',
    ]);
});

test('room creation rejects invalid status', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->make();

    $response = $this->postJson('/api/v1/rooms', [
        'room_type_id' => $room->room_type_id,
        'room_number' => $room->room_number,
        'floor_no' => $room->floor_no,
        'status' => 'invalid_status',
        'price' => $room->price,
        'is_active' => $room->is_active,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'status',
    ]);
});

test('manager can update room', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->create();

    $response = $this->putJson("/api/v1/rooms/{$room->id}", [
        'room_type_id' => $room->room_type_id,
        'room_number' => '999',
        'floor_no' => 5,
        'status' => 'maintenance',
        'price' => '250.00',
        'is_active' => true,
    ]);

    $response->assertStatus(200);

    $response->assertJsonPath('data.room_number', '999');

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'room_number' => '999',
        'floor_no' => 5,
        'status' => 'maintenance',
        'price' => '250.00',
    ]);
});

test('user cannot update room', function () {

    actingAsRoomUser('user');

    $room = Room::factory()->create();

    $response = $this->putJson("/api/v1/rooms/{$room->id}", [
        'room_type_id' => $room->room_type_id,
        'room_number' => '999',
        'floor_no' => 5,
        'status' => 'maintenance',
        'price' => '250.00',
        'is_active' => true,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'room_number' => $room->room_number,
    ]);
});

test('manager can partially update room', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->create();

    $response = $this->patchJson("/api/v1/rooms/{$room->id}", [
        'price' => '300.00',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'price' => '300.00',
    ]);
});

test('non-existent room cannot be updated', function () {

    actingAsRoomUser('manager');

    $response = $this->putJson('/api/v1/rooms/999999', [
        'room_type_id' => Room::factory()->make()->room_type_id,
        'room_number' => '999',
        'floor_no' => 5,
        'status' => 'maintenance',
        'price' => '250.00',
        'is_active' => true,
    ]);

    $response->assertStatus(404);
});

test('unauthenticated user cannot update room', function () {

    $room = Room::factory()->create();

    $response = $this->putJson("/api/v1/rooms/{$room->id}", [
        'room_type_id' => $room->room_type_id,
        'room_number' => '999',
        'floor_no' => 5,
        'status' => 'maintenance',
        'price' => '250.00',
        'is_active' => true,
    ]);

    $response->assertStatus(401);
});

test('room update rejects invalid status', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->create();

    $response = $this->patchJson("/api/v1/rooms/{$room->id}", [
        'status' => 'invalid_status',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'status',
    ]);
});

test('room update rejects duplicate room number', function () {

    actingAsRoomUser('manager');

    $existingRoom = Room::factory()->create();
    $room = Room::factory()->create();

    $response = $this->patchJson("/api/v1/rooms/{$room->id}", [
        'room_number' => $existingRoom->room_number,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'room_number',
    ]);
});

test('room update rejects invalid room type', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->create();

    $response = $this->patchJson("/api/v1/rooms/{$room->id}", [
        'room_type_id' => 999999,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'room_type_id',
    ]);
});

test('room update rejects invalid price', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->create();

    $response = $this->patchJson("/api/v1/rooms/{$room->id}", [
        'price' => '-100.00',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'price',
    ]);
});

test('room update rejects invalid active status', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->create();

    $response = $this->patchJson("/api/v1/rooms/{$room->id}", [
        'is_active' => 'invalid',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'is_active',
    ]);
});

test('room update rejects invalid floor number', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->create();

    $response = $this->patchJson("/api/v1/rooms/{$room->id}", [
        'floor_no' => 'invalid',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'floor_no',
    ]);
});

test('manager can update room without changing room number', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->create();

    $response = $this->patchJson("/api/v1/rooms/{$room->id}", [
        'room_number' => $room->room_number,
    ]);

    $response->assertStatus(200);

    $response->assertJsonPath(
        'data.room_number',
        $room->room_number
    );
});

test('user cannot partially update room', function () {

    actingAsRoomUser('user');

    $room = Room::factory()->create();

    $response = $this->patchJson("/api/v1/rooms/{$room->id}", [
        'price' => '300.00',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'price' => $room->price,
    ]);
});

test('manager can create room', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->make();

    $response = $this->postJson('/api/v1/rooms', [
        'room_type_id' => $room->room_type_id,
        'room_number' => $room->room_number,
        'floor_no' => $room->floor_no,
        'status' => $room->status,
        'price' => $room->price,
        'is_active' => $room->is_active,
    ]);

    $response->assertStatus(201);

    $response->assertJsonPath('data.room_number', $room->room_number);

    $this->assertDatabaseHas('rooms', [
        'room_number' => $room->room_number,
    ]);
});

test('room creation rejects invalid price', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->make();

    $response = $this->postJson('/api/v1/rooms', [
        'room_type_id' => $room->room_type_id,
        'room_number' => $room->room_number,
        'floor_no' => $room->floor_no,
        'status' => $room->status,
        'price' => '-100.00',
        'is_active' => $room->is_active,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'price',
    ]);
});

test('room creation rejects invalid active status', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->make();

    $response = $this->postJson('/api/v1/rooms', [
        'room_type_id' => $room->room_type_id,
        'room_number' => $room->room_number,
        'floor_no' => $room->floor_no,
        'status' => $room->status,
        'price' => $room->price,
        'is_active' => 'invalid',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'is_active',
    ]);
});

test('user cannot delete room', function () {

     actingAsRoomUser('user');

    $room = Room::factory()->create();

    $response = $this->deleteJson("/api/v1/rooms/{$room->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'deleted_at' => null,
    ]);
});

test('manager can delete room', function () {

    actingAsRoomUser('manager');

    $room = Room::factory()->create();

    $response = $this->deleteJson("/api/v1/rooms/{$room->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('rooms', [
        'id' => $room->id,
    ]);
});

test('non-existent room cannot be deleted', function () {

    actingAsRoomUser('manager');

    $response = $this->deleteJson('/api/v1/rooms/999999');

    $response->assertStatus(404);
});

test('unauthenticated user cannot delete room', function () {

    $room = Room::factory()->create();

    $response = $this->deleteJson("/api/v1/rooms/{$room->id}");

    $response->assertStatus(401);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'deleted_at' => null,
    ]);
});