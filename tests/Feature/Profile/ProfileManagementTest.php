<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
});

test('guests cannot access profile endpoints', function () {
    $this->patchJson('/api/v1/profile', ['name' => 'Guest'])->assertUnauthorized();
    $this->postJson('/api/v1/profile/photo')->assertUnauthorized();
    $this->deleteJson('/api/v1/profile/photo')->assertUnauthorized();
    $this->patchJson('/api/v1/profile/password', [
        'current_password' => 'password',
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ])->assertUnauthorized();
    $this->deleteJson('/api/v1/profile/account', ['password' => 'password'])->assertUnauthorized();
});

test('authenticated user can update profile name phone and default address', function () {
    $user = User::factory()->create([
        'name' => 'Muhammad Ali',
        'phone' => null,
    ]);

    $response = $this->actingAs($user)->patchJson('/api/v1/profile', [
        'name' => 'Muhammad Ali Khan',
        'phone' => '03001234567',
        'address' => [
            'line1' => 'DHA Phase 7',
            'area' => 'DHA Phase 7',
            'city' => 'Karachi',
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Muhammad Ali Khan')
        ->assertJsonPath('data.phone', '03001234567')
        ->assertJsonPath('data.default_address.line1', 'DHA Phase 7')
        ->assertJsonPath('data.default_address.city', 'Karachi')
        ->assertJsonPath('message', 'Profile updated successfully.');

    $user->refresh();

    expect($user->name)->toBe('Muhammad Ali Khan')
        ->and($user->phone)->toBe('03001234567')
        ->and($user->defaultAddress)->not->toBeNull()
        ->and($user->defaultAddress->line1)->toBe('DHA Phase 7');
});

test('email change clears verification and enforces uniqueness', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $user = User::factory()->create([
        'email' => 'original@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)->patchJson('/api/v1/profile', [
        'email' => 'taken@example.com',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    $this->actingAs($user)->patchJson('/api/v1/profile', [
        'email' => 'updated@example.com',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.email', 'updated@example.com');

    $user->refresh();

    expect($user->email)->toBe('updated@example.com')
        ->and($user->hasVerifiedEmail())->toBeFalse();
});

test('user can change password with current password validation', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-1'),
    ]);

    $this->actingAs($user)->patchJson('/api/v1/profile/password', [
        'current_password' => 'wrong-password',
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);

    $this->actingAs($user)->patchJson('/api/v1/profile/password', [
        'current_password' => 'old-password-1',
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Password changed successfully.');

    $user->refresh();

    expect(Hash::check('new-password-1', $user->password))->toBeTrue();
});

test('user can upload replace and remove profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $upload = $this->actingAs($user)->post('/api/v1/profile/photo', [
        'photo' => UploadedFile::fake()->image('avatar.jpg', 400, 400),
    ], ['Accept' => 'application/json']);

    $upload
        ->assertSuccessful()
        ->assertJsonPath('message', 'Profile photo updated successfully.');

    $firstPath = $upload->json('data.profile_photo.path');

    expect($firstPath)->toStartWith('profile-photos/'.$user->id.'/')
        ->and(Storage::disk('public')->exists($firstPath))->toBeTrue();

    $replace = $this->actingAs($user)->post('/api/v1/profile/photo', [
        'photo' => UploadedFile::fake()->image('avatar-2.jpg', 400, 400),
    ], ['Accept' => 'application/json']);

    $replace->assertSuccessful();

    $secondPath = $replace->json('data.profile_photo.path');

    expect($secondPath)->not->toBe($firstPath)
        ->and(Storage::disk('public')->exists($firstPath))->toBeFalse()
        ->and(Storage::disk('public')->exists($secondPath))->toBeTrue();

    $this->actingAs($user)->deleteJson('/api/v1/profile/photo')
        ->assertSuccessful()
        ->assertJsonPath('data.profile_photo', null);

    expect(Storage::disk('public')->exists($secondPath))->toBeFalse();
});

test('customer can delete account when no active orders exist', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'email' => 'delete-me@example.com',
        'password' => Hash::make('password'),
    ]);

    Address::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Completed,
    ]);

    $this->actingAs($user)->post('/api/v1/profile/photo', [
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ], ['Accept' => 'application/json'])->assertSuccessful();

    $response = $this->actingAs($user)->deleteJson('/api/v1/profile/account', [
        'password' => 'password',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('message', 'Account deleted successfully.');

    $deleted = User::withTrashed()->find($user->id);

    expect($deleted)->not->toBeNull()
        ->and($deleted->trashed())->toBeTrue()
        ->and($deleted->email)->not->toBe('delete-me@example.com')
        ->and($deleted->name)->toBe('Deleted User')
        ->and(Address::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(Order::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('customer cannot delete account while an active order is in progress', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $this->actingAs($user)->deleteJson('/api/v1/profile/account', [
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);

    expect(User::query()->find($user->id))->not->toBeNull();
});

test('manager cannot delete account from profile', function () {
    $manager = User::factory()->manager()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($manager)->deleteJson('/api/v1/profile/account', [
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('super admin cannot delete account from profile', function () {
    $admin = User::factory()->superAdmin()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($admin)->deleteJson('/api/v1/profile/account', [
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('login and user endpoint include profile address data', function () {
    $user = User::factory()->create([
        'email' => 'profile@example.com',
        'password' => Hash::make('password'),
        'phone' => '03009998877',
    ]);

    Address::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
        'line1' => 'Clifton Block 5',
        'city' => 'Karachi',
    ]);

    $this->postJson('/api/login', [
        'email' => 'profile@example.com',
        'password' => 'password',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.phone', '03009998877')
        ->assertJsonPath('data.default_address.line1', 'Clifton Block 5');

    $this->actingAs($user)->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('data.default_address.city', 'Karachi');
});

test('manager profile exposes read only branch context', function () {
    $branch = Branch::factory()->create(['name' => 'DHA', 'is_active' => true]);
    $manager = User::factory()->manager($branch)->create([
        'phone' => '03001112233',
    ]);

    $this->actingAs($manager)->patchJson('/api/v1/profile', [
        'name' => 'Branch Manager',
        'phone' => '03004445566',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.role', UserRole::Manager->value)
        ->assertJsonPath('data.access_label', 'DHA')
        ->assertJsonPath('data.branch.name', 'DHA');

    $manager->refresh();

    expect($manager->branch_id)->toBe($branch->id);
});

test('super admin profile exposes all branches access label', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('data.role', UserRole::SuperAdmin->value)
        ->assertJsonPath('data.access_label', 'All Branches');
});
