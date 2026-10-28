<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Mail\Orders\OrderConfirmedMail;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
});

test('critical flow: registration -> verification email -> login -> order confirmation email', function () {
    Notification::fake();
    Mail::fake();

    // 1. Registration
    $registerPayload = [
        'name' => '  Zainab Fatima  ',
        'email' => '  Zainab.Fatima@Example.Com  ',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ];

    $registerResponse = $this->postJson('/api/register', $registerPayload);

    $registerResponse
        ->assertCreated()
        ->assertJsonPath('data.name', 'Zainab Fatima')
        ->assertJsonPath('data.email', 'zainab.fatima@example.com')
        ->assertJsonPath('data.role', UserRole::User->value)
        ->assertJsonPath('data.email_verified_at', null);

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'zainab.fatima@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    // 2. Verification email notification is sent with frontend signed URL
    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
        $mail = $notification->toMail($user);
        $url = $mail->actionUrl;

        expect($url)
            ->toContain('/verify-email/'.$user->id.'/')
            ->not->toContain('/api/email/verify/');

        return true;
    });

    // 3. Logout and verify Login with exact and case-insensitive email works
    $this->postJson('/api/logout')->assertNoContent();
    $this->assertGuest();

    $loginResponse = $this->postJson('/api/login', [
        'email' => '  ZAINAB.FATIMA@EXAMPLE.COM  ',
        'password' => 'SecurePass123!',
    ]);

    $loginResponse
        ->assertSuccessful()
        ->assertJsonPath('data.email', 'zainab.fatima@example.com');

    $this->assertAuthenticatedAs($user);

    // 4. Verify email using signed URL
    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]
    );

    $verifyResponse = $this->actingAs($user)->getJson($signedUrl);
    $verifyResponse
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    // 5. Place order as verified customer
    $branch = Branch::factory()->create(['name' => 'Pebbles Gulberg', 'is_active' => true]);
    $category = Category::factory()->create(['name' => 'Brownies', 'is_active' => true]);
    $product = Product::factory()->for($category)->create([
        'name' => 'Fudge Brownie',
        'base_price' => '450.00',
        'is_active' => true,
        'is_available' => true,
    ]);

    $this->actingAs($user->fresh())
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
        ->assertSuccessful();

    $checkoutResponse = $this->actingAs($user->fresh())
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => 'takeaway',
            'payment_method' => 'cod',
            'customer_name' => 'Zainab Fatima',
            'customer_phone' => '03001234567',
        ])
        ->assertSuccessful();

    $orderNumber = $checkoutResponse->json('data.order_number');
    expect($orderNumber)->not->toBeNull();

    $order = Order::query()->where('public_order_number', $orderNumber)->first();
    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Pending);

    // 6. Confirm order as manager/superadmin and verify order confirmation email is dispatched
    $admin = User::factory()->superAdmin()->create();

    $this->flushSession();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$orderNumber.'/status', [
            'status' => OrderStatus::Confirmed->value,
            'estimated_minutes' => 30,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Confirmed->value);

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);

    Mail::assertQueued(OrderConfirmedMail::class, function (OrderConfirmedMail $mail) use ($order, $user): bool {
        return $mail->order->is($order)
            && $mail->hasTo($user->email);
    });
});

test('password reset flow sends notification and allows login with new password', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'customer.reset@pebbles.test',
        'password' => 'OriginalPass123!',
    ]);

    // Request reset link
    $this->postJson('/api/forgot-password', [
        'email' => '  Customer.Reset@Pebbles.Test  ',
    ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'If that email is registered with Pebbles, we have sent password reset instructions.');

    $resetToken = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$resetToken) {
        $resetToken = $notification->token;

        return true;
    });

    expect($resetToken)->not->toBeNull();

    // Reset password
    $this->postJson('/api/reset-password', [
        'token' => $resetToken,
        'email' => '  CUSTOMER.RESET@PEBBLES.TEST  ',
        'password' => 'NewSecurePass456!',
        'password_confirmation' => 'NewSecurePass456!',
    ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Your password has been reset. You can sign in with your new password.');

    // Old password fails
    $this->postJson('/api/login', [
        'email' => 'customer.reset@pebbles.test',
        'password' => 'OriginalPass123!',
    ])->assertUnprocessable();

    // New password succeeds
    $this->postJson('/api/login', [
        'email' => 'Customer.Reset@Pebbles.Test',
        'password' => 'NewSecurePass456!',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id);

    $this->assertAuthenticatedAs($user);
});
