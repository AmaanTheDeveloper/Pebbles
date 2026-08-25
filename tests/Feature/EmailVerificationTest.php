<?php

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
});

test('unverified customer can use cart but cannot checkout', function () {
    $user = User::factory()->unverified()->create();
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->for($category)->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '500.00',
    ]);
    $branch = Branch::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.item_count', 1);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => 'takeaway',
        ])
        ->assertForbidden();
});

test('verified customer can access cart', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.item_count', 0);
});

test('unverified manager and super admin are not blocked on admin routes', function () {
    $manager = User::factory()->unverified()->manager()->create();
    $admin = User::factory()->unverified()->superAdmin()->create();

    $this->actingAs($manager)
        ->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful();
});

test('unverified customer can still fetch profile and user endpoints', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email_verified_at', null);
});

test('resend verification notification sends verify email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->postJson('/api/email/verification-notification')
        ->assertSuccessful()
        ->assertJsonPath('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        expect($url)
            ->toContain('/verify-email/'.$user->id.'/')
            ->not->toContain('/api/email/verify/');

        return true;
    });
});

test('resend verification returns no content when already verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/email/verification-notification')
        ->assertNoContent();

    Notification::assertNothingSent();
});

test('valid verification link marks email as verified', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]
    );

    $this->actingAs($user)
        ->getJson($verificationUrl)
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('already verified user can hit verification endpoint successfully', function () {
    $user = User::factory()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]
    );

    $this->actingAs($user)
        ->getJson($verificationUrl)
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('verification spa routes return http 200', function () {
    $this->get('/verify-email')->assertOk()->assertSee('id="root"', false);
    $this->get('/verify-email/1/abcdef')->assertOk()->assertSee('id="root"', false);
});

test('registration still creates an unverified user', function () {
    Notification::fake();

    $this->postJson('/api/register', [
        'name' => 'Verify Me',
        'email' => 'verify-me@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertCreated()
        ->assertJsonPath('data.role', UserRole::User->value);

    $user = User::query()->where('email', 'verify-me@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});
