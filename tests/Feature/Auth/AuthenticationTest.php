<?php

use App\Models\User;

test('users can authenticate using the login endpoint', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Login successful.',
        ])
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
                'token',
                'token_type',
            ],
            'meta',
        ]);
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this
        ->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/logout');

    $response
        ->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Logout successful.',
        ]);
});