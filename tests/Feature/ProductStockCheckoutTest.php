<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\StockStatus;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;

function makeStockProduct(array $attributes = []): Product
{
    $category = Category::factory()->create(['is_active' => true]);

    return Product::factory()->for($category)->create(array_merge([
        'base_price' => '500.00',
        'is_active' => true,
        'is_available' => true,
        'stock_quantity' => 10,
        'low_stock_threshold' => 5,
    ], $attributes));
}

function seedStockCheckoutBranch(): Branch
{
    $branch = Branch::factory()->create(['is_active' => true]);
    DeliveryZone::factory()->for($branch)->create([
        'is_active' => true,
        'delivery_fee' => '0.00',
        'minimum_order_amount' => '0.00',
    ]);

    return $branch;
}

function addProductToUserCart(User $user, Product $product, int $quantity = 1): void
{
    test()->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => $quantity,
    ])->assertSuccessful();
}

test('checkout with enough stock deducts quantity', function () {
    $user = User::factory()->create();
    $branch = seedStockCheckoutBranch();
    $product = makeStockProduct(['stock_quantity' => 10]);

    addProductToUserCart($user, $product, 3);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertSuccessful();

    expect($product->fresh()->stock_quantity)->toBe(7);
});

test('checkout with exact stock succeeds and leaves zero', function () {
    $user = User::factory()->create();
    $branch = seedStockCheckoutBranch();
    $product = makeStockProduct(['stock_quantity' => 2]);

    addProductToUserCart($user, $product, 2);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertSuccessful();

    expect($product->fresh()->stock_quantity)->toBe(0)
        ->and($product->fresh()->stockStatus())->toBe(StockStatus::OutOfStock);
});

test('checkout rejects insufficient stock', function () {
    $user = User::factory()->create();
    $branch = seedStockCheckoutBranch();
    $product = makeStockProduct(['stock_quantity' => 2]);

    $cart = Cart::factory()->for($user)->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);

    expect($product->fresh()->stock_quantity)->toBe(2)
        ->and(Order::query()->count())->toBe(0);
});

test('adding more than available stock to cart is rejected', function () {
    $user = User::factory()->create();
    $product = makeStockProduct(['stock_quantity' => 2]);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);
});

test('null stock quantity allows unlimited checkout', function () {
    $user = User::factory()->create();
    $branch = seedStockCheckoutBranch();
    $product = makeStockProduct(['stock_quantity' => null]);

    addProductToUserCart($user, $product, 20);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertSuccessful();

    expect($product->fresh()->stock_quantity)->toBeNull()
        ->and($product->fresh()->stockStatus())->toBe(StockStatus::Unlimited);
});

test('cancelling an order restores deducted stock', function () {
    Setting::setValue('customer_order_cancellation_enabled', true, 'boolean');

    $user = User::factory()->create();
    $branch = seedStockCheckoutBranch();
    $product = makeStockProduct(['stock_quantity' => 5]);

    addProductToUserCart($user, $product, 3);

    $response = $this->actingAs($user)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway->value,
    ])->assertSuccessful();

    expect($product->fresh()->stock_quantity)->toBe(2);

    $orderNumber = $response->json('data.order_number');

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$orderNumber.'/cancel', [
            'cancellation_reason' => 'ordered_by_mistake',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

    expect($product->fresh()->stock_quantity)->toBe(5);
});

test('second checkout fails when stock was taken by the first', function () {
    $branch = seedStockCheckoutBranch();
    $product = makeStockProduct(['stock_quantity' => 1]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    addProductToUserCart($userA, $product, 1);
    addProductToUserCart($userB, $product, 1);

    $this->actingAs($userA)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertSuccessful();

    expect($product->fresh()->stock_quantity)->toBe(0);

    $this->actingAs($userB)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
        ])
        ->assertUnprocessable();

    expect($product->fresh()->stock_quantity)->toBe(0)
        ->and(Order::query()->count())->toBe(1);
});

test('catalog marks zero stock products as unavailable with out_of_stock status', function () {
    $product = makeStockProduct([
        'stock_quantity' => 0,
        'is_available' => true,
        'slug' => 'stocked-out-shake',
    ]);

    $this->getJson('/api/v1/catalog/products/'.$product->slug)
        ->assertSuccessful()
        ->assertJsonPath('data.available', false)
        ->assertJsonPath('data.stock_status', StockStatus::OutOfStock->value);
});

test('product helpers report stock state', function () {
    $unlimited = makeStockProduct(['stock_quantity' => null]);
    $low = makeStockProduct(['stock_quantity' => 3, 'low_stock_threshold' => 5]);
    $out = makeStockProduct(['stock_quantity' => 0]);

    expect($unlimited->isInStock())->toBeTrue()
        ->and($unlimited->isLowStock())->toBeFalse()
        ->and($unlimited->availableQuantity())->toBeNull()
        ->and($unlimited->stockStatus())->toBe(StockStatus::Unlimited)
        ->and($low->isInStock())->toBeTrue()
        ->and($low->isLowStock())->toBeTrue()
        ->and($low->stockStatus())->toBe(StockStatus::LowStock)
        ->and($out->isInStock())->toBeFalse()
        ->and($out->stockStatus())->toBe(StockStatus::OutOfStock);
});
