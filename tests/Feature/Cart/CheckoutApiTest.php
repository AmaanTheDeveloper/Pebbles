<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Branch;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

function makeCheckoutProduct(array $attributes = []): Product
{
    $category = Category::factory()->create(['is_active' => true]);

    return Product::factory()->for($category)->create(array_merge([
        'base_price' => '1000.00',
        'is_active' => true,
        'is_available' => true,
        'name' => 'Snapshot Burger',
    ], $attributes));
}

function seedBranchWithZone(): array
{
    $branch = Branch::factory()->create(['is_active' => true]);
    $zone = DeliveryZone::factory()->for($branch)->create([
        'is_active' => true,
        'delivery_fee' => '150.00',
        'minimum_order_amount' => '100.00',
    ]);

    return [$branch, $zone];
}

test('empty cart cannot checkout', function () {
    $user = User::factory()->create();
    [$branch] = seedBranchWithZone();

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cart']);
});

test('valid takeaway checkout creates order snapshots and clears cart', function () {
    $user = User::factory()->create();
    [$branch] = seedBranchWithZone();
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($category)->create([
        'base_price' => '1000.00',
        'is_active' => true,
        'is_available' => true,
        'name' => 'Snapshot Burger',
    ]);
    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Extra Patty',
        'price' => '200.00',
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'add_on_ids' => [$addOn->id],
        'quantity' => 1,
    ])->assertSuccessful();

    $response = $this->actingAs($user)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway->value,
        'pickup_notes' => 'Call on arrival',
        'subtotal' => 1,
        'total' => 1,
        'delivery_fee' => 1,
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Pending->value)
        ->assertJsonPath('data.fulfillment_type', OrderType::Takeaway->value)
        ->assertJsonPath('data.subtotal', '1200.00')
        ->assertJsonPath('data.delivery_fee', '0.00')
        ->assertJsonPath('data.total', '1200.00');

    $orderNumber = $response->json('data.order_number');
    expect($orderNumber)->toStartWith('PB-')
        ->and($response->json('data'))->not->toHaveKey('id');

    $order = Order::query()->where('public_order_number', $orderNumber)->first();
    $item = $order->items()->first();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($item->product_name)->toBe('Snapshot Burger')
        ->and($item->unit_price)->toBe('1000.00')
        ->and($item->line_total)->toBe('1200.00')
        ->and($item->addOns->first()->add_on_name)->toBe('Extra Patty')
        ->and($item->addOns->first()->unit_price)->toBe('200.00')
        ->and($order->payment->status)->toBe(PaymentStatus::Pending)
        ->and(CartItem::query()->count())->toBe(0);

    $product->update(['base_price' => '1200.00', 'name' => 'Renamed Burger']);
    $addOn->update(['price' => '999.00', 'name' => 'Renamed Add-On']);

    $item->refresh();
    expect($item->product_name)->toBe('Snapshot Burger')
        ->and($item->unit_price)->toBe('1000.00')
        ->and($item->addOns->first()->add_on_name)->toBe('Extra Patty')
        ->and($item->addOns->first()->unit_price)->toBe('200.00');
});

test('delivery checkout requires zone and address fields and applies delivery fee', function () {
    $user = User::factory()->create();
    [$branch, $zone] = seedBranchWithZone();
    $product = makeCheckoutProduct(['base_price' => '500.00']);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Delivery->value,
        ])
        ->assertUnprocessable();

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Delivery->value,
            'delivery_zone_id' => $zone->id,
            'delivery_recipient_name' => 'Ayesha',
            'delivery_phone' => '03001234567',
            'delivery_line1' => 'Street 1',
            'delivery_city' => 'Karachi',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.delivery_fee', '150.00')
        ->assertJsonPath('data.total', '650.00');
});

test('takeaway does not require delivery fields', function () {
    $user = User::factory()->create();
    [$branch] = seedBranchWithZone();
    $product = makeCheckoutProduct();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.delivery_fee', '0.00');
});

test('dine-in requires table number', function () {
    $user = User::factory()->create();
    [$branch] = seedBranchWithZone();
    $product = makeCheckoutProduct();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::DineIn->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['table_number']);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::DineIn->value,
            'table_number' => '12',
        ])
        ->assertSuccessful();
});

test('inactive branch blocks checkout', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['is_active' => false]);
    $product = makeCheckoutProduct();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['branch_id']);
});

test('stale unavailable product blocks checkout and creates no order', function () {
    $user = User::factory()->create();
    [$branch] = seedBranchWithZone();
    $product = makeCheckoutProduct();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $product->update(['is_available' => false]);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertUnprocessable();

    expect(Order::query()->count())->toBe(0)
        ->and(CartItem::query()->count())->toBe(1);
});

test('unavailable variant blocks checkout', function () {
    $user = User::factory()->create();
    [$branch] = seedBranchWithZone();
    $product = makeCheckoutProduct();
    $variant = ProductVariant::factory()->for($product)->create([
        'price' => '1100.00',
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $variant->update(['is_active' => false]);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertUnprocessable();

    expect(Order::query()->count())->toBe(0);
});

test('double checkout does not create a duplicate order from an empty cart', function () {
    $user = User::factory()->create();
    [$branch] = seedBranchWithZone();
    $product = makeCheckoutProduct();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $first = $this->actingAs($user)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway->value,
    ])->assertSuccessful();

    $this->actingAs($user)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway->value,
    ])->assertUnprocessable();

    expect(Order::query()->count())->toBe(1)
        ->and($first->json('data.order_number'))->toStartWith('PB-');
});

test('delivery zone from another branch is rejected', function () {
    $user = User::factory()->create();
    $branchA = Branch::factory()->create(['is_active' => true]);
    $branchB = Branch::factory()->create(['is_active' => true]);
    $zoneB = DeliveryZone::factory()->for($branchB)->create([
        'is_active' => true,
        'delivery_fee' => '99.00',
        'minimum_order_amount' => '0.00',
    ]);
    $product = makeCheckoutProduct();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branchA->id,
            'type' => OrderType::Delivery->value,
            'delivery_zone_id' => $zoneB->id,
            'delivery_recipient_name' => 'Ayesha',
            'delivery_phone' => '03001234567',
            'delivery_line1' => 'Street 1',
            'delivery_city' => 'Karachi',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['delivery_zone_id']);
});
