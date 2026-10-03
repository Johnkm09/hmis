<?php

use App\Services\RoomTypeService;
use App\Models\RoomType\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakeRoomTypeServiceImage(string $name)
{
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
    );

    $uniqueContent = $png . hash('sha256', $name, true);

    return UploadedFile::fake()->createWithContent(
        $name,
        $uniqueContent
    );
}

test('it creates a room type correctly', function () {

    Storage::fake('public');

    $service = app(RoomTypeService::class);

    $data = [
        'name' => 'Deluxe Room',
        'description' => 'Nice room',
        'max_occupancy' => 2,
        'images' => [
            fakeRoomTypeServiceImage('image-1.png'),
            fakeRoomTypeServiceImage('image-2.png'),
            fakeRoomTypeServiceImage('image-3.png'),
            fakeRoomTypeServiceImage('image-4.png'),
            fakeRoomTypeServiceImage('image-5.png'),
        ],
    ];

    $roomType = $service->create($data);

    expect($roomType)->toBeInstanceOf(RoomType::class);

    $this->assertDatabaseHas('room_types', [
        'name' => 'Deluxe Room',
        'slug' => 'deluxe-room',
    ]);

    expect($roomType->images)->toHaveCount(5);
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

    $this->assertSoftDeleted('room_types', [
        'id' => $roomType->id,
    ]);
});

test('it finds room type by id', function () {

    $service = app(RoomTypeService::class);

    $roomType = RoomType::factory()->create();

    $found = $service->findById($roomType->id);

    expect($found->id)->toBe($roomType->id);
});
