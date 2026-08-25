<?php

use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
});

test('coupon apply is rate limited to 10 per minute', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '500.00',
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    Coupon::factory()->fixed(50)->create(['code' => 'LIMITME']);

    $key = md5('coupon-apply'.$user->id);

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit($key, 60);
    }

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', ['coupon_code' => 'LIMITME'])
        ->assertStatus(429);
});

test('cart item mutations are rate limited to 60 per minute per ip', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '500.00',
    ]);

    $key = md5('cart-mutations'.'127.0.0.1');

    for ($i = 0; $i < 60; $i++) {
        RateLimiter::hit($key, 60);
    }

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
        ->assertStatus(429);
});
