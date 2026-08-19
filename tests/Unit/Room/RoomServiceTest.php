<?php

use App\Repositories\Room\RoomRepositoryInterface;
use App\Services\RoomService;
use App\Models\Room\Room;

beforeEach(function () {
    $this->repository = Mockery::mock(RoomRepositoryInterface::class);

    $this->service = new RoomService($this->repository);
});

afterEach(function () {
    Mockery::close();
});


test('service can get all rooms', function () {

    $rooms = collect([
        Room::factory()->make(),
        Room::factory()->make(),
    ]);

    $this->repository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn($rooms);

    $result = $this->service->getAll();

    expect($result)->toBe($rooms);
});


test('service can create a room', function () {

    $data = [
        'room_type_id' => 1,
        'room_number' => '101',
        'floor_no' => 1,
        'status' => 'available',
        'price' => '150.00',
        'is_active' => true,
    ];

    $room = Room::factory()->make($data);

    $this->repository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($room);

    $result = $this->service->create($data);

    expect($result)->toBe($room);
});


test('service can find room by id', function () {

    $room = Room::factory()->make([
        'id' => 1,
    ]);

    $this->repository
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($room);

    $result = $this->service->findById(1);

    expect($result)->toBe($room);
});


test('service can update a room', function () {

    $data = [
        'price' => '300.00',
    ];

    $room = Room::factory()->make([
        'id' => 1,
    ]);

    $this->repository
        ->shouldReceive('update')
        ->once()
        ->with(1, $data)
        ->andReturn($room);

    $result = $this->service->update(1, $data);

    expect($result)->toBe($room);
});


test('service can delete a room', function () {

    $this->repository
        ->shouldReceive('delete')
        ->once()
        ->with(1);

    $result = $this->service->delete(1);

    expect($result)->toBeNull();
});