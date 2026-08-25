<?php

use App\Enums\OfferType;
use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

function offerCouponProduct(string $price = '1000.00'): Product
{
    $category = Category::factory()->create(['is_active' => true]);

    return Product::factory()->for($category)->create([
        'base_price' => $price,
        'is_active' => true,
        'is_available' => true,
    ]);
}

function offerCouponBranch(): Branch
{
    return Branch::factory()->create(['is_active' => true]);
}

test('coupon applies after offer on the remaining amount', function () {
    $user = User::factory()->create();
    $branch = offerCouponBranch();
    $product = offerCouponProduct('1000.00');

    Offer::factory()->create([
        'title' => 'Ten Percent',
        'type' => OfferType::Discounts,
        'offer_value' => '10% off',
        'is_active' => true,
    ]);
    Coupon::factory()->fixed(100)->create(['code' => 'AFTEROFFER']);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', [
            'coupon_code' => 'AFTEROFFER',
            'branch_id' => $branch->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', '1000.00')
        ->assertJsonPath('data.offer_discount_amount', '100.00')
        ->assertJsonPath('data.coupon_discount_amount', '100.00')
        ->assertJsonPath('data.discount_amount', '200.00')
        ->assertJsonPath('data.total', '800.00');

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', '1000.00')
        ->assertJsonPath('data.discount_amount', '200.00')
        ->assertJsonPath('data.total', '800.00');

    $order = Order::query()->latest('id')->first();

    expect($order->discount_amount)->toBe('200.00')
        ->and($order->coupon_discount_amount)->toBe('100.00')
        ->and($order->grand_total)->toBe('800.00');
});

test('percentage coupon after offer uses remaining merchandise subtotal', function () {
    $user = User::factory()->create();
    $branch = offerCouponBranch();
    $product = offerCouponProduct('1000.00');

    Offer::factory()->create([
        'offer_value' => 'Rs 200 off',
        'is_active' => true,
    ]);
    Coupon::factory()->percentage(10)->create(['code' => 'PCTAFTER']);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    // Offer takes 200 → remaining 800 → 10% coupon = 80
    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', [
            'coupon_code' => 'PCTAFTER',
            'branch_id' => $branch->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.offer_discount_amount', '200.00')
        ->assertJsonPath('data.coupon_discount_amount', '80.00')
        ->assertJsonPath('data.total', '720.00');
});

test('targeted offer plus coupon only discounts eligible offer lines then coupon on remainder', function () {
    $user = User::factory()->create();
    $branch = offerCouponBranch();
    $burgers = Category::factory()->create(['is_active' => true]);
    $drinks = Category::factory()->create(['is_active' => true]);
    $burger = Product::factory()->for($burgers)->create([
        'base_price' => '500.00',
        'is_active' => true,
        'is_available' => true,
    ]);
    $drink = Product::factory()->for($drinks)->create([
        'base_price' => '500.00',
        'is_active' => true,
        'is_available' => true,
    ]);

    $offer = Offer::factory()->forCategories()->create([
        'offer_value' => '10% off',
        'is_active' => true,
    ]);
    $offer->categories()->sync([$burgers->id]);
    Coupon::factory()->fixed(50)->create(['code' => 'STACK50']);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $burger->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $drink->id,
        'quantity' => 1,
    ])->assertSuccessful();

    // Offer: 10% of burger 500 = 50. Coupon fixed 50 on remaining 950 = 50. Total discount 100.
    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', [
            'coupon_code' => 'STACK50',
            'branch_id' => $branch->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', '1000.00')
        ->assertJsonPath('data.offer_discount_amount', '50.00')
        ->assertJsonPath('data.coupon_discount_amount', '50.00')
        ->assertJsonPath('data.total', '900.00');
});
