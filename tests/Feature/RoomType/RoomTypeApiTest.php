<?php

use App\Models\User;
use App\Models\RoomType\RoomType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAsUser(string $role)
{
    $user = User::factory()->create();

    $user->assignRole($role);

    test()->actingAs($user, 'sanctum');

    return $user;
}

function fakeRoomTypePolicyImage(string $name)
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

function roomTypeImages(int $count)
{
    return collect(range(1, $count))
        ->map(fn($number) => fakeRoomTypePolicyImage("image-{$number}.png"))
        ->all();
}

test('all roles can view room types list', function () {

    actingAsUser('user');

    $response = $this->getJson('/api/v1/room-types');

    $response->assertStatus(200);
});

test('user cannot create room type', function () {

    actingAsUser('user');

    $response = $this->postJson(
        '/api/v1/room-types',
        [
            'name' => 'Deluxe Room',
            'max_occupancy' => 2,
            'images' => roomTypeImages(5),
        ]
    );

    $response->assertStatus(403);
});

test('user cannot delete room type', function () {

    actingAsUser('user');

    $roomType = RoomType::factory()->create();

    $response = $this->deleteJson("/api/v1/room-types/{$roomType->id}");

    $response->assertStatus(403);
});

test('admin can delete room type', function () {

    actingAsUser('super_admin');

    $roomType = RoomType::factory()->create();

    $response = $this->deleteJson("/api/v1/room-types/{$roomType->id}");

    $response->assertStatus(200);
});

test('authorized user can create room type with 5 images', function () {

    actingAsUser('super_admin');

    $response = $this->post(
        '/api/v1/room-types',
        [
            'name' => 'Deluxe Room',
            'max_occupancy' => 2,
            'images' => roomTypeImages(5),
        ]
    );

    $response
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Deluxe Room')
        ->assertJsonCount(5, 'data.images');
});

test('room type creation requires at least 5 images', function () {

    actingAsUser('super_admin');

    $response = $this->postJson(
        '/api/v1/room-types',
        [
            'name' => 'Deluxe Room',
            'max_occupancy' => 2,
            'images' => roomTypeImages(4),
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['images']);
});

test('room type creation allows a maximum of 8 images', function () {

    actingAsUser('super_admin');

    $response = $this->postJson(
        '/api/v1/room-types',
        [
            'name' => 'Deluxe Room',
            'max_occupancy' => 2,
            'images' => roomTypeImages(9),
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['images']);
});

test('room type creation rejects duplicate image content', function () {

    actingAsUser('super_admin');

    $image = fakeRoomTypePolicyImage('image-1.png');

    $response = $this->postJson(
        '/api/v1/room-types',
        [
            'name' => 'Deluxe Room',
            'max_occupancy' => 2,
            'images' => [
                $image,
                fakeRoomTypePolicyImage('image-2.png'),
                fakeRoomTypePolicyImage('image-3.png'),
                fakeRoomTypePolicyImage('image-4.png'),
                $image,
            ],
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['images']);
});

test('room type can be updated without images', function () {

    actingAsUser('super_admin');

    $roomType = RoomType::factory()->create();

    $response = $this->putJson(
        "/api/v1/room-types/{$roomType->id}",
        [
            'name' => 'Updated Room Type',
        ]
    );

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Room Type');
});

test('room type can be updated with additional images', function () {

    actingAsUser('super_admin');

    $roomType = RoomType::factory()->create();

    foreach (roomTypeImages(5) as $image) {
        $roomType->images()->create([
            'path' => "room-types/{$roomType->id}/{$image->getClientOriginalName()}",
            'hash' => hash_file('sha256', $image->getRealPath()),
            'is_primary' => false,
        ]);
    }

    $response = $this->put(
        "/api/v1/room-types/{$roomType->id}",
        [
            'name' => 'Updated Room Type',
            'max_occupancy' => 3,
            'images' => [
                fakeRoomTypePolicyImage('image-6.png'),
            ],
        ]
    );

    $response
        ->assertStatus(200)
        ->assertJsonCount(6, 'data.images');
});

test('room type cannot exceed 8 total images during update', function () {

    actingAsUser('super_admin');

    $roomType = RoomType::factory()->create();

    foreach (roomTypeImages(8) as $image) {
        $roomType->images()->create([
            'path' => "room-types/{$roomType->id}/{$image->getClientOriginalName()}",
            'hash' => hash_file('sha256', $image->getRealPath()),
            'is_primary' => false,
        ]);
    }

    expect($roomType->images()->count())->toBe(8);

    $response = $this->putJson(
        "/api/v1/room-types/{$roomType->id}",
        [
            'name' => 'Updated Room Type',
            'images' => [
                fakeRoomTypePolicyImage('image-9.png'),
            ],
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['images']);
});
