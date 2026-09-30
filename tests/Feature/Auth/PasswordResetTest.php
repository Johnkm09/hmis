<?php

use App\Models\User;
use App\Notifications\QueuedResetPassword;
use Illuminate\Support\Facades\Notification;

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('api/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, QueuedResetPassword::class);
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('api/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, QueuedResetPassword::class, function (object $notification) use ($user) {
        $response = $this->post('api/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertStatus(200);

        return true;
    });
});
