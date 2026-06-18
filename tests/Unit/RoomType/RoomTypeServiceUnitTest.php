<?php
use App\Services\RoomTypeService;
use App\Models\RoomType\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

test('it creates a room type correctly', function () {
    $service = app(RoomTypeService::class);

    $data = [
        'name' => 'Deluxe Room',
        'description' => 'Nice room'
    ];

    $roomType = $service->create($data);

    expect($roomType)->toBeInstanceOf(RoomType::class);

    $this->assertDatabaseHas('room_types', [
        'name' => 'Deluxe Room',
        'slug' => 'deluxe-room'
    ]);
});

test('it updates a room type correctly', function () {

    $service = app(RoomTypeService::class);

    $roomType = RoomType::factory()->create();

    $updated = $service->update($roomType->id, [
        'name' => 'Updated Name'
    ]);

    expect($updated->name)->toBe('Updated Name');

    $this->assertDatabaseHas('room_types', [
        'id' => $roomType->id,
        'name' => 'Updated Name'
    ]);
});

test('it deletes a room type correctly', function () {

    $service = app(RoomTypeService::class);

    $roomType = RoomType::factory()->create();

    $service->delete($roomType->id);

    $this->assertDatabaseMissing('room_types', [
        'id' => $roomType->id
    ]);
});

test('it finds room type by id', function () {

    $service = app(RoomTypeService::class);

    $roomType = RoomType::factory()->create();

    $found = $service->findById($roomType->id);

    expect($found->id)->toBe($roomType->id);
});