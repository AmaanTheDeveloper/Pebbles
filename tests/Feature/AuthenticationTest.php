<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
});

test('users can register with the user role and start a session', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.email', 'ada@example.com')
        ->assertJsonPath('data.role', UserRole::User->value);

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'ada@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->hasVerifiedEmail())->toBeFalse();
});

test('registration ignores attempted role escalation to superadmin', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Mallory',
        'email' => 'mallory@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => UserRole::SuperAdmin->value,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.role', UserRole::User->value);

    expect(User::query()->where('email', 'mallory@example.com')->value('role'))
        ->toBe(UserRole::User);
});

test('registration rejects duplicate emails', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Duplicate',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('users can authenticate with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'login@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id);

    $this->assertAuthenticatedAs($user);
});

test('users cannot authenticate with invalid credentials', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'login@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable();
    $this->assertGuest();
});

test('authenticated users can fetch their profile', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->getJson('/api/user');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.role', UserRole::User->value);
});

test('authenticated users can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/logout')
        ->assertNoContent();

    $this->assertGuest();
});
