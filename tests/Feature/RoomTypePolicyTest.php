<?php

use App\Models\User;
use App\Models\RoomType\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

function actingAsUser(string $role)
{
    $user = User::factory()->create([
        'role' => $role,
    ]);

    test()->actingAs($user, 'sanctum');

    return $user;
}

test('all roles can view room types list', function () {

    actingAsUser('user');

    $response = $this->getJson('/api/v1/room-types');

    $response->assertStatus(200);
});

test('user cannot create room type', function () {

    actingAsUser('user');

    $response = $this->postJson('/api/v1/room-types', [
        'name' => 'Deluxe Room'
    ]);

    $response->assertStatus(403);
});

test('user cannot delete room type', function(){
    actingAsUser('user');
    $roomType = RoomType::factory()->create();
    $response = $this->deleteJson("/api/v1/room-types/{$roomType->id}");
    
    $response->assertStatus(403);
});

test('admin can delete room type', function(){
    actingAsUser('super_admin');
    $roomType = RoomType::factory()->create();
    $response = $this->deleteJson("/api/v1/room-types/{$roomType->id}");
    
    $response->assertStatus(200);
});