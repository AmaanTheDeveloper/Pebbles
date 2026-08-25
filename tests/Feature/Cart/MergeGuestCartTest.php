<?php

use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Product;
use App\Models\User;
use App\Support\GuestCartCookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
    $this->withCredentials();
});

function mergeGuestProduct(array $attributes = []): Product
{
    $category = Category::factory()->create(['is_active' => true]);

    return Product::factory()->for($category)->create(array_merge([
        'base_price' => '300.00',
        'is_active' => true,
        'is_available' => true,
    ], $attributes));
}

test('login merges guest cart items into the user cart and clears cookie', function () {
    $product = mergeGuestProduct();
    $branch = Branch::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'email' => 'merge-login@example.com',
        'password' => Hash::make('password'),
    ]);

    $guestToken = (string) Str::uuid();
    $guestCart = Cart::factory()->guest($guestToken)->create([
        'branch_id' => $branch->id,
    ]);
    CartItem::factory()->create([
        'cart_id' => $guestCart->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response = $this->withUnencryptedCookie(GuestCartCookie::NAME, $guestToken)
        ->postJson('/api/login', [
            'email' => 'merge-login@example.com',
            'password' => 'password',
        ]);

    $response
        ->assertSuccessful()
        ->assertCookieExpired(GuestCartCookie::NAME);

    $userCart = Cart::query()->where('user_id', $user->id)->first();

    expect($userCart)->not->toBeNull()
        ->and($userCart->branch_id)->toBe($branch->id)
        ->and($userCart->items()->sum('quantity'))->toBe(2)
        ->and(Cart::query()->where('guest_token', $guestToken)->exists())->toBeFalse();
});

test('register merges guest cart items into the new user cart', function () {
    $product = mergeGuestProduct(['base_price' => '150.00']);
    $guestToken = (string) Str::uuid();
    $guestCart = Cart::factory()->guest($guestToken)->create();
    CartItem::factory()->create([
        'cart_id' => $guestCart->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $response = $this->withUnencryptedCookie(GuestCartCookie::NAME, $guestToken)
        ->postJson('/api/register', [
            'name' => 'Guest Merger',
            'email' => 'guest-merger@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response
        ->assertCreated()
        ->assertCookieExpired(GuestCartCookie::NAME);

    $user = User::query()->where('email', 'guest-merger@example.com')->first();
    $userCart = Cart::query()->where('user_id', $user->id)->first();

    expect($userCart)->not->toBeNull()
        ->and($userCart->items()->sum('quantity'))->toBe(1)
        ->and(Cart::query()->where('guest_token', $guestToken)->exists())->toBeFalse();
});

test('guest coupon is kept when still valid for the logged-in user', function () {
    $product = mergeGuestProduct(['base_price' => '500.00']);
    $coupon = Coupon::factory()->fixed(100)->create(['code' => 'GUEST100']);
    $user = User::factory()->create([
        'email' => 'coupon-merge@example.com',
        'password' => Hash::make('password'),
    ]);

    $guestToken = (string) Str::uuid();
    $guestCart = Cart::factory()->guest($guestToken)->create([
        'coupon_id' => $coupon->id,
    ]);
    CartItem::factory()->create([
        'cart_id' => $guestCart->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->withUnencryptedCookie(GuestCartCookie::NAME, $guestToken)
        ->postJson('/api/login', [
            'email' => 'coupon-merge@example.com',
            'password' => 'password',
        ])
        ->assertSuccessful();

    $userCart = Cart::query()->where('user_id', $user->id)->first();

    expect($userCart)->not->toBeNull()
        ->and($userCart->coupon_id)->toBe($coupon->id);
});

test('guest coupon is cleared when invalid for the logged-in user', function () {
    $product = mergeGuestProduct(['base_price' => '500.00']);
    $coupon = Coupon::factory()->fixed(100)->create([
        'code' => 'ONCEONLY',
        'per_customer_limit' => 1,
    ]);
    $user = User::factory()->create([
        'email' => 'coupon-invalid@example.com',
        'password' => Hash::make('password'),
    ]);

    CouponRedemption::factory()->create([
        'coupon_id' => $coupon->id,
        'user_id' => $user->id,
    ]);

    $guestToken = (string) Str::uuid();
    $guestCart = Cart::factory()->guest($guestToken)->create([
        'coupon_id' => $coupon->id,
    ]);
    CartItem::factory()->create([
        'cart_id' => $guestCart->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->withUnencryptedCookie(GuestCartCookie::NAME, $guestToken)
        ->postJson('/api/login', [
            'email' => 'coupon-invalid@example.com',
            'password' => 'password',
        ])
        ->assertSuccessful();

    $userCart = Cart::query()->where('user_id', $user->id)->first();

    expect($userCart)->not->toBeNull()
        ->and($userCart->coupon_id)->toBeNull();
});

test('guest branch wins when guest cart has items', function () {
    $product = mergeGuestProduct();
    $guestBranch = Branch::factory()->create(['is_active' => true]);
    $userBranch = Branch::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'email' => 'branch-merge@example.com',
        'password' => Hash::make('password'),
    ]);

    Cart::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $userBranch->id,
    ]);

    $guestToken = (string) Str::uuid();
    $guestCart = Cart::factory()->guest($guestToken)->create([
        'branch_id' => $guestBranch->id,
    ]);
    CartItem::factory()->create([
        'cart_id' => $guestCart->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->withUnencryptedCookie(GuestCartCookie::NAME, $guestToken)
        ->postJson('/api/login', [
            'email' => 'branch-merge@example.com',
            'password' => 'password',
        ])
        ->assertSuccessful();

    $userCart = Cart::query()->where('user_id', $user->id)->first();

    expect($userCart->branch_id)->toBe($guestBranch->id)
        ->and($userCart->items()->sum('quantity'))->toBe(1);
});
