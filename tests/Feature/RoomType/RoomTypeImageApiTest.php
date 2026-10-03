<?php

use App\Models\RoomType\RoomType;
use App\Models\RoomType\RoomTypeImage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsRoomTypeImageUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

function fakeRoomTypeImage(string $name = 'room.png')
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

test('authorized user can upload a room type image', function () {

    actingAsRoomTypeImageUser('manager');

    Storage::fake('public');

    $roomType = RoomType::factory()->create();

    $response = $this->post(
        "/api/v1/room-types/{$roomType->id}/images",
        [
            'image' => fakeRoomTypeImage(),
        ]
    );

    $response->assertStatus(201);

    $image = RoomTypeImage::first();

    expect($image)->not->toBeNull()
        ->and($image->room_type_id)->toBe($roomType->id)
        ->and($image->is_primary)->toBeTrue();

    Storage::disk('public')->assertExists($image->path);
});

test('first uploaded room type image becomes primary', function () {

    actingAsRoomTypeImageUser('manager');

    Storage::fake('public');

    $roomType = RoomType::factory()->create();

    $this->post(
        "/api/v1/room-types/{$roomType->id}/images",
        [
            'image' => fakeRoomTypeImage('first.png'),
        ]
    )->assertStatus(201);

    $image = RoomTypeImage::first();

    expect($image->is_primary)->toBeTrue();
});

test('additional room type images are not primary', function () {

    actingAsRoomTypeImageUser('manager');

    Storage::fake('public');

    $roomType = RoomType::factory()->create();

    $this->post(
        "/api/v1/room-types/{$roomType->id}/images",
        [
            'image' => fakeRoomTypeImage('first.png'),
        ]
    )->assertStatus(201);

    $this->post(
        "/api/v1/room-types/{$roomType->id}/images",
        [
            'image' => fakeRoomTypeImage('second.png'),
        ]
    )->assertStatus(201);

    expect(RoomTypeImage::where('room_type_id', $roomType->id)
        ->where('is_primary', true)
        ->count())->toBe(1);

    $secondImage = RoomTypeImage::where('room_type_id', $roomType->id)
        ->where('is_primary', false)
        ->first();

    expect($secondImage)->not->toBeNull();
});

test('room type cannot have more than eight images', function () {

    actingAsRoomTypeImageUser('manager');

    Storage::fake('public');

    $roomType = RoomType::factory()->create();

    foreach (range(1, 8) as $number) {
        $this->post(
            "/api/v1/room-types/{$roomType->id}/images",
            [
                'image' => fakeRoomTypeImage("room-{$number}.png"),
            ]
        )->assertStatus(201);
    }

    $response = $this->postJson(
        "/api/v1/room-types/{$roomType->id}/images",
        [
            'image' => fakeRoomTypeImage('ninth.png'),
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['images']);

    expect(RoomTypeImage::where('room_type_id', $roomType->id)->count())
        ->toBe(8);
});

test('authorized user can set another image as primary', function () {

    actingAsRoomTypeImageUser('manager');

    Storage::fake('public');

    $roomType = RoomType::factory()->create();

    $this->post(
        "/api/v1/room-types/{$roomType->id}/images",
        [
            'image' => fakeRoomTypeImage('first.png'),
        ]
    )->assertStatus(201);

    $this->post(
        "/api/v1/room-types/{$roomType->id}/images",
        [
            'image' => fakeRoomTypeImage('second.png'),
        ]
    )->assertStatus(201);

    $images = RoomTypeImage::where('room_type_id', $roomType->id)
        ->orderBy('id')
        ->get();

    $secondImage = $images->last();

    $response = $this->patchJson(
        "/api/v1/room-type-images/{$secondImage->id}/primary"
    );

    $response->assertStatus(200);

    expect($secondImage->fresh()->is_primary)->toBeTrue()
        ->and($images->first()->fresh()->is_primary)->toBeFalse();
});

test('deleting primary image promotes the oldest remaining image', function () {

    actingAsRoomTypeImageUser('manager');

    Storage::fake('public');

    $roomType = RoomType::factory()->create();

    foreach (['first.png', 'second.png'] as $filename) {
        $this->post(
            "/api/v1/room-types/{$roomType->id}/images",
            [
                'image' => fakeRoomTypeImage($filename),
            ]
        )->assertStatus(201);
    }

    $images = RoomTypeImage::where('room_type_id', $roomType->id)
        ->orderBy('id')
        ->get();

    $primary = $images->first();
    $replacement = $images->last();

    $response = $this->deleteJson(
        "/api/v1/room-type-images/{$primary->id}"
    );

    $response->assertStatus(200);

    expect($replacement->fresh()->is_primary)->toBeTrue();

    Storage::disk('public')->assertMissing($primary->path);
});

test('user role cannot upload room type images', function () {

    actingAsRoomTypeImageUser('user');

    Storage::fake('public');

    $roomType = RoomType::factory()->create();

    $response = $this->post(
        "/api/v1/room-types/{$roomType->id}/images",
        [
            'image' => fakeRoomTypeImage(),
        ]
    );

    $response->assertStatus(403);
});

test('room type image upload validates the image file', function () {

    actingAsRoomTypeImageUser('manager');

    Storage::fake('public');

    $roomType = RoomType::factory()->create();

    $response = $this->withHeader('Accept', 'application/json')->post(
        "/api/v1/room-types/{$roomType->id}/images",
        [
            'image' => UploadedFile::fake()->create(
                'document.pdf',
                100,
                'application/pdf'
            ),
        ]
    );

    $response->assertStatus(422);
});

test('authorized user can list room type images', function () {

    actingAsRoomTypeImageUser('user');

    Storage::fake('public');

    $roomType = RoomType::factory()->create();

    $roomType->images()->create([
        'path' => "room-types/{$roomType->id}/room.png",
        'hash' => hash('sha256', 'room-image'),
        'is_primary' => true,
    ]);

    $response = $this->getJson(
        "/api/v1/room-types/{$roomType->id}/images"
    );

    $response->assertStatus(200)
        ->assertJsonPath(
            'data.0.id',
            $roomType->images()->first()->id
        )
        ->assertJsonPath(
            'data.0.room_type_id',
            $roomType->id
        )
        ->assertJsonPath(
            'data.0.is_primary',
            true
        );
});
