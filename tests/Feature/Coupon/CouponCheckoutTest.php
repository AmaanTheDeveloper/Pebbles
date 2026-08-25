<?php

use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

function makeCouponProduct(array $attributes = []): Product
{
    $category = Category::factory()->create(['is_active' => true]);

    return Product::factory()->for($category)->create(array_merge([
        'base_price' => '1000.00',
        'is_active' => true,
        'is_available' => true,
        'name' => 'Coupon Burger',
    ], $attributes));
}

function seedCouponCart(User $user, ?Product $product = null, int $quantity = 1): array
{
    $product ??= makeCouponProduct();
    $branch = Branch::factory()->create(['is_active' => true]);

    test()->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => $quantity,
    ])->assertSuccessful();

    return [$product, $branch];
}

test('coupons are never auto-applied on cart fetch', function () {
    $user = User::factory()->create();
    Coupon::factory()->fixed(100)->create(['code' => 'PEBBLES100']);
    seedCouponCart($user);

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.applied_coupon', null)
        ->assertJsonPath('data.coupon_discount_amount', '0.00');
});

test('customer can apply a fixed coupon case-insensitively via ajax', function () {
    $user = User::factory()->create();
    Coupon::factory()->fixed(100)->create(['code' => 'PEBBLES100']);
    [, $branch] = seedCouponCart($user);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', [
            'coupon_code' => 'pebbles100',
            'branch_id' => $branch->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.applied_coupon.code', 'PEBBLES100')
        ->assertJsonPath('data.coupon_discount_amount', '100.00')
        ->assertJsonPath('data.total', '900.00');
});

test('customer can remove an applied coupon via ajax', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->fixed(100)->create(['code' => 'REMOVE100']);
    [, $branch] = seedCouponCart($user);

    $this->actingAs($user)->postJson('/api/v1/cart/coupon', [
        'coupon_code' => 'REMOVE100',
        'branch_id' => $branch->id,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->deleteJson('/api/v1/cart/coupon')
        ->assertSuccessful()
        ->assertJsonPath('data.applied_coupon', null)
        ->assertJsonPath('data.coupon_discount_amount', '0.00');

    expect(Cart::query()->where('user_id', $user->id)->value('coupon_id'))->toBeNull();
    expect($coupon->fresh()->usage_count)->toBe(0);
});

test('invalid coupon code is rejected with a clear message', function () {
    $user = User::factory()->create();
    seedCouponCart($user);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', ['coupon_code' => 'ABC123'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['coupon_code'])
        ->assertJsonPath('errors.coupon_code.0', 'Invalid coupon code.');
});

test('expired coupon is rejected', function () {
    $user = User::factory()->create();
    Coupon::factory()->fixed(100)->create([
        'code' => 'EXPIRED',
        'expires_at' => now()->subDay(),
    ]);
    seedCouponCart($user);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', ['coupon_code' => 'EXPIRED'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.coupon_code.0', 'This coupon has expired.');
});

test('scheduled coupon is rejected', function () {
    $user = User::factory()->create();
    Coupon::factory()->fixed(100)->create([
        'code' => 'FUTURE',
        'starts_at' => now()->addDay(),
    ]);
    seedCouponCart($user);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', ['coupon_code' => 'FUTURE'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.coupon_code.0', 'This coupon is not active yet.');
});

test('minimum order amount is enforced', function () {
    $user = User::factory()->create();
    Coupon::factory()->fixed(100)->create([
        'code' => 'MIN1000',
        'minimum_order_amount' => '1000.00',
    ]);
    seedCouponCart($user, makeCouponProduct(['base_price' => '600.00']));

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', ['coupon_code' => 'MIN1000'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.coupon_code.0', 'This coupon requires a minimum order of Rs. 1,000.');
});

test('percentage coupon respects maximum discount', function () {
    $user = User::factory()->create();
    Coupon::factory()->percentage(10, 300)->create(['code' => 'CAP10']);
    seedCouponCart($user, makeCouponProduct(['base_price' => '5000.00']));

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', ['coupon_code' => 'CAP10'])
        ->assertSuccessful()
        ->assertJsonPath('data.coupon_discount_amount', '300.00');
});

test('branch-specific coupon rejects other branches', function () {
    $user = User::factory()->create();
    $dha = Branch::factory()->create(['is_active' => true, 'name' => 'DHA']);
    $gulberg = Branch::factory()->create(['is_active' => true, 'name' => 'Gulberg']);
    Coupon::factory()->fixed(100)->create([
        'code' => 'DHAONLY',
        'branch_id' => $dha->id,
    ]);
    seedCouponCart($user);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', [
            'coupon_code' => 'DHAONLY',
            'branch_id' => $gulberg->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.coupon_code.0', 'This coupon is not available for the selected branch.');
});

test('checkout revalidates coupon and records redemption', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->fixed(100)->create(['code' => 'CHECK100']);
    [, $branch] = seedCouponCart($user);

    $this->actingAs($user)->postJson('/api/v1/cart/coupon', [
        'coupon_code' => 'CHECK100',
        'branch_id' => $branch->id,
    ])->assertSuccessful();

    $response = $this->actingAs($user)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway->value,
        'payment_method' => 'cod',
    ])->assertSuccessful();

    $order = Order::query()->where('public_order_number', $response->json('data.order_number'))->first();

    expect($order)->not->toBeNull()
        ->and((float) $order->coupon_discount_amount)->toBe(100.0)
        ->and($order->coupon_code)->toBe('CHECK100')
        ->and((float) $order->discount_amount)->toBe(100.0)
        ->and((float) $order->grand_total)->toBe(900.0)
        ->and($coupon->fresh()->usage_count)->toBe(1)
        ->and(CouponRedemption::query()->where('order_id', $order->id)->exists())->toBeTrue()
        ->and(Cart::query()->where('user_id', $user->id)->value('coupon_id'))->toBeNull();
});

test('checkout rejects client-supplied discount and uses coupon from cart only', function () {
    $user = User::factory()->create();
    [, $branch] = seedCouponCart($user);

    $response = $this->actingAs($user)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway->value,
        'payment_method' => 'cod',
        'discount_amount' => 5000,
        'total' => 1,
    ])->assertSuccessful();

    $order = Order::query()->where('public_order_number', $response->json('data.order_number'))->first();

    expect((float) $order->discount_amount)->toBe(0.0)
        ->and((float) $order->grand_total)->toBe(1000.0)
        ->and($order->coupon_code)->toBeNull();
});

test('fixed coupon cannot create a negative total', function () {
    $user = User::factory()->create();
    Coupon::factory()->fixed(500)->create(['code' => 'BIG500']);
    seedCouponCart($user, makeCouponProduct(['base_price' => '300.00']));

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', ['coupon_code' => 'BIG500'])
        ->assertSuccessful()
        ->assertJsonPath('data.coupon_discount_amount', '300.00')
        ->assertJsonPath('data.total', '0.00');
});

test('per-customer limit is enforced', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->fixed(50)->create([
        'code' => 'ONCE',
        'per_customer_limit' => 1,
    ]);
    [, $branch] = seedCouponCart($user);

    $this->actingAs($user)->postJson('/api/v1/cart/coupon', [
        'coupon_code' => 'ONCE',
        'branch_id' => $branch->id,
    ])->assertSuccessful();

    $this->actingAs($user)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway->value,
        'payment_method' => 'cod',
    ])->assertSuccessful();

    seedCouponCart($user);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', [
            'coupon_code' => 'ONCE',
            'branch_id' => $branch->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.coupon_code.0', 'You have already used this coupon.');

    expect($coupon->fresh()->usage_count)->toBe(1);
});

test('usage limit blocks further applications', function () {
    $user = User::factory()->create();
    Coupon::factory()->fixed(50)->create([
        'code' => 'LIMIT1',
        'usage_limit' => 1,
        'usage_count' => 1,
    ]);
    seedCouponCart($user);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', ['coupon_code' => 'LIMIT1'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.coupon_code.0', 'This coupon has reached its usage limit.');
});

test('cart change that breaks minimum order removes coupon on next cart read', function () {
    $user = User::factory()->create();
    Coupon::factory()->fixed(100)->create([
        'code' => 'MINCART',
        'minimum_order_amount' => '1000.00',
    ]);
    $product = makeCouponProduct(['base_price' => '600.00']);
    [, $branch] = seedCouponCart($user, $product, 2);

    $this->actingAs($user)->postJson('/api/v1/cart/coupon', [
        'coupon_code' => 'MINCART',
        'branch_id' => $branch->id,
    ])->assertSuccessful();

    $itemId = Cart::query()->where('user_id', $user->id)->first()->items()->first()->id;

    $this->actingAs($user)
        ->patchJson('/api/v1/cart/items/'.$itemId, ['quantity' => 1])
        ->assertSuccessful()
        ->assertJsonPath('data.applied_coupon', null)
        ->assertJsonPath('data.coupon_message', 'Your coupon is no longer valid for this cart and was removed.');
});

test('reorder does not reuse the previous coupon', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->fixed(100)->create(['code' => 'REORDER']);
    [, $branch] = seedCouponCart($user);

    $this->actingAs($user)->postJson('/api/v1/cart/coupon', [
        'coupon_code' => 'REORDER',
        'branch_id' => $branch->id,
    ])->assertSuccessful();

    $checkout = $this->actingAs($user)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway->value,
        'payment_method' => 'cod',
    ])->assertSuccessful();

    $order = Order::query()->where('public_order_number', $checkout->json('data.order_number'))->first();
    $order->update(['status' => 'completed']);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/reorder', [
            'mode' => 'replace',
        ])
        ->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.applied_coupon', null);

    expect(Cart::query()->where('user_id', $user->id)->value('coupon_id'))->toBeNull()
        ->and($coupon->fresh()->usage_count)->toBe(1);
});
