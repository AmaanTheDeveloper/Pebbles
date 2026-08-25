<?php

use App\Enums\OfferType;
use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

function makeDiscountProduct(string $price = '500.00'): Product
{
    $category = Category::factory()->create(['is_active' => true]);

    return Product::factory()->for($category)->create([
        'base_price' => $price,
        'is_active' => true,
        'is_available' => true,
    ]);
}

function makeDiscountBranch(): array
{
    $branch = Branch::factory()->create(['is_active' => true]);
    $zone = DeliveryZone::factory()->for($branch)->create([
        'is_active' => true,
        'delivery_fee' => '100.00',
        'minimum_order_amount' => '100.00',
    ]);

    return [$branch, $zone];
}

test('percentage offer discounts cart and checkout totals', function () {
    $user = User::factory()->create();
    [$branch] = makeDiscountBranch();
    $product = makeDiscountProduct('500.00');

    Offer::factory()->create([
        'title' => 'Ten Percent',
        'type' => OfferType::Discounts,
        'offer_value' => '10% off',
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', '500.00')
        ->assertJsonPath('data.discount_amount', '50.00')
        ->assertJsonPath('data.total', '450.00')
        ->assertJsonPath('data.applied_offer.title', 'Ten Percent');

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', '500.00')
        ->assertJsonPath('data.discount_amount', '50.00')
        ->assertJsonPath('data.delivery_fee', '0.00')
        ->assertJsonPath('data.total', '450.00');

    $order = Order::query()->latest('id')->first();

    expect($order->discount_amount)->toBe('50.00')
        ->and($order->grand_total)->toBe('450.00')
        ->and($order->payment->amount)->toBe('450.00');
});

test('fixed offer discounts cart and checkout totals', function () {
    $user = User::factory()->create();
    [$branch] = makeDiscountBranch();
    $product = makeDiscountProduct('500.00');

    Offer::factory()->create([
        'title' => 'Hundred Off',
        'type' => OfferType::SeasonalDeals,
        'offer_value' => 'Rs 100 off',
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.discount_amount', '100.00')
        ->assertJsonPath('data.total', '400.00');

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.discount_amount', '100.00')
        ->assertJsonPath('data.total', '400.00');
});

test('inactive and expired offers do not discount', function () {
    $user = User::factory()->create();
    [$branch] = makeDiscountBranch();
    $product = makeDiscountProduct('500.00');

    Offer::factory()->inactive()->create([
        'offer_value' => '50% off',
        'is_active' => false,
    ]);
    Offer::factory()->expired()->create([
        'offer_value' => 'Rs 200 off',
    ]);
    Offer::factory()->create([
        'offer_value' => 'Special Event Weekend',
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.discount_amount', '0.00')
        ->assertJsonPath('data.total', '500.00');

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.discount_amount', '0.00')
        ->assertJsonPath('data.total', '500.00');
});

test('delivery checkout applies offer discount before delivery fee', function () {
    $user = User::factory()->create();
    [$branch, $zone] = makeDiscountBranch();
    $product = makeDiscountProduct('500.00');

    Offer::factory()->create([
        'offer_value' => '10% off',
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Delivery->value,
            'delivery_zone_id' => $zone->id,
            'delivery_recipient_name' => 'Ali',
            'delivery_phone' => '03001234567',
            'delivery_line1' => 'Street 1',
            'delivery_city' => 'Karachi',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', '500.00')
        ->assertJsonPath('data.discount_amount', '50.00')
        ->assertJsonPath('data.delivery_fee', '100.00')
        ->assertJsonPath('data.total', '550.00');
});

test('only the largest parseable offer discount is applied', function () {
    $user = User::factory()->create();
    makeDiscountBranch();
    $product = makeDiscountProduct('1000.00');

    Offer::factory()->create([
        'title' => 'Five Percent',
        'offer_value' => '5% off',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    Offer::factory()->create([
        'title' => 'Hundred Fixed',
        'offer_value' => 'Rs 100 off',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.discount_amount', '100.00')
        ->assertJsonPath('data.applied_offer.title', 'Hundred Fixed')
        ->assertJsonPath('data.total', '900.00');
});

test('maximum discount amount caps an entire-store percentage offer', function () {
    $user = User::factory()->create();
    [$branch] = makeDiscountBranch();
    $product = makeDiscountProduct('2000.00');

    Offer::factory()->create([
        'title' => 'Capped Twenty',
        'offer_value' => '20% off',
        'maximum_discount_amount' => '150.00',
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.offer_discount_amount', '150.00')
        ->assertJsonPath('data.total', '1850.00');

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.discount_amount', '150.00')
        ->assertJsonPath('data.total', '1850.00');
});

test('maximum discount amount caps percentage offer discounts', function () {
    $user = User::factory()->create();
    makeDiscountBranch();
    $product = makeDiscountProduct('1000.00');

    Offer::factory()->create([
        'offer_value' => '50% off',
        'maximum_discount_amount' => '75.00',
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.discount_amount', '75.00')
        ->assertJsonPath('data.total', '925.00');
});
