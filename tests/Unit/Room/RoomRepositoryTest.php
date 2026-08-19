<?php

use App\Models\Room\Room;
use App\Repositories\Room\RoomRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


beforeEach(function () {
    $this->repository = new RoomRepository();
});


test('repository can get all rooms', function () {

    Room::factory()->count(3)->create();

    $result = $this->repository->getAll();

    expect($result->total())->toBe(3);
});


test('repository can create a room', function () {

    $room = Room::factory()->make();

    $result = $this->repository->create([
        'room_type_id' => $room->room_type_id,
        'room_number' => $room->room_number,
        'floor_no' => $room->floor_no,
        'status' => $room->status,
        'price' => $room->price,
        'is_active' => $room->is_active,
    ]);

    expect($result)->toBeInstanceOf(Room::class);

    $this->assertDatabaseHas('rooms', [
        'room_number' => $room->room_number,
    ]);
});


test('repository can find room by id', function () {

    $room = Room::factory()->create();

    $result = $this->repository->findById($room->id);

    expect($result)
        ->toBeInstanceOf(Room::class)
        ->and($result->id)->toBe($room->id);
});


test('repository can update a room', function () {

    $room = Room::factory()->create();

    $result = $this->repository->update($room->id, [
        'price' => '300.00',
    ]);

    expect($result)
        ->toBeInstanceOf(Room::class)
        ->and($result->price)->toBe('300.00');

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'price' => '300.00',
    ]);
});


test('repository can delete a room', function () {

    $room = Room::factory()->create();

    $this->repository->delete($room->id);

    $this->assertSoftDeleted('rooms', [
        'id' => $room->id,
    ]);
});

test('repository can filter rooms by room number', function () {

    Room::factory()->create([
        'room_number' => '101',
    ]);

    Room::factory()->create([
        'room_number' => '202',
    ]);

    $this->getJson('/api/v1/rooms?filter[room_number]=101');

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->room_number)->toBe('101');
});

test('repository can filter rooms by status', function () {

    Room::factory()->create([
        'status' => 'available',
    ]);

    Room::factory()->create([
        'status' => 'occupied',
    ]);

    $this->getJson('/api/v1/rooms?filter[status]=available');

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->status)->toBe('available');
});

test('repository can filter rooms by active status', function () {

    Room::factory()->create([
        'is_active' => true,
    ]);

    Room::factory()->create([
        'is_active' => false,
    ]);

    $this->getJson('/api/v1/rooms?filter[is_active]=1');

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->is_active)->toBeTrue();
});

test('repository can filter rooms by floor', function () {

    Room::factory()->create([
        'floor_no' => 1,
    ]);

    Room::factory()->create([
        'floor_no' => 2,
    ]);

    $this->getJson('/api/v1/rooms?filter[floor_no]=1');

    $result = $this->repository->getAll();

    expect($result->total())->toBe(1)
        ->and($result->first()->floor_no)->toBe(1);
});

test('repository can sort rooms by price', function () {

    Room::factory()->create([
        'price' => '300.00',
    ]);

    Room::factory()->create([
        'price' => '100.00',
    ]);

    $this->getJson('/api/v1/rooms?sort=price');

    $result = $this->repository->getAll();

    expect($result->first()->price)->toBe('100.00');
});

test('repository can sort rooms by price descending', function () {

    Room::factory()->create([
        'price' => '100.00',
    ]);

    Room::factory()->create([
        'price' => '300.00',
    ]);

    $this->getJson('/api/v1/rooms?sort=-price');

    $result = $this->repository->getAll();

    expect($result->first()->price)->toBe('300.00');
});

test('repository can paginate rooms', function () {

    Room::factory()->count(15)->create();

    $this->getJson('/api/v1/rooms?per_page=5');

    $result = $this->repository->getAll();

    expect($result->total())->toBe(15)
        ->and($result->perPage())->toBe(5)
        ->and($result->count())->toBe(5);
});