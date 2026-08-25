<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
});

test('password reset link can be requested for a registered email', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'reset@example.com']);

    $this->postJson('/api/forgot-password', [
        'email' => 'reset@example.com',
    ])
        ->assertSuccessful()
        ->assertJsonPath(
            'message',
            'If that email is registered with Pebbles, we have sent password reset instructions.',
        );

    Notification::assertSentTo($user, ResetPassword::class);
});

test('password reset request does not reveal whether an email exists', function () {
    Notification::fake();

    $this->postJson('/api/forgot-password', [
        'email' => 'missing@example.com',
    ])
        ->assertSuccessful()
        ->assertJsonPath(
            'message',
            'If that email is registered with Pebbles, we have sent password reset instructions.',
        );

    Notification::assertNothingSent();
});

test('password can be reset with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'newpass@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $this->postJson('/api/forgot-password', ['email' => $user->email])->assertSuccessful();

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    expect($token)->not->toBeNull();

    $this->postJson('/api/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Your password has been reset. You can sign in with your new password.');

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue()
        ->and(Hash::check('old-password', $user->fresh()->password))->toBeFalse();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'old-password',
    ])->assertUnprocessable();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'new-password',
    ])->assertSuccessful();
});

test('password reset rejects an invalid token', function () {
    $user = User::factory()->create(['email' => 'badtoken@example.com']);

    $this->postJson('/api/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('password reset rejects an expired token', function () {
    $user = User::factory()->create(['email' => 'expired@example.com']);

    $token = Password::broker()->createToken($user);

    DB::table('password_reset_tokens')
        ->where('email', $user->email)
        ->update(['created_at' => now()->subHours(2)]);

    $this->postJson('/api/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('password reset validates password confirmation and strength', function () {
    $user = User::factory()->create(['email' => 'weak@example.com']);
    $token = Password::broker()->createToken($user);

    $this->postJson('/api/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'short',
        'password_confirmation' => 'different',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('forgot password and reset password spa routes return http 200', function () {
    $this->get('/forgot-password')->assertOk()->assertSee('id="root"', false);
    $this->get('/reset-password')->assertOk()->assertSee('id="root"', false);
});
