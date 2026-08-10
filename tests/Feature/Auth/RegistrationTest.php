<?php
use Spatie\Permission\Models\Role;

test('new users can register', function () {

    $this->seed();

    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertOk();
});