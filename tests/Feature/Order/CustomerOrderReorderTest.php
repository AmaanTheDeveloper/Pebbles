<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;

function createHistoricalOrder(User $user, array $overrides = []): Order
{
    $branch = Branch::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '450.00',
    ]);

    $order = Order::factory()->for($user)->create(array_merge([
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway,
        'status' => OrderStatus::Completed,
        'subtotal' => '450.00',
        'grand_total' => '450.00',
        'delivery_fee' => '0.00',
    ], $overrides));

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => '450.00',
        'quantity' => 2,
        'line_total' => '900.00',
    ]);

    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
        'amount' => '900.00',
    ]);

    return $order->fresh(['items.addOns', 'payment']);
}

test('customer can reorder a completed order into the cart using current pricing', function () {
    $user = User::factory()->create();
    $order = createHistoricalOrder($user);
    $product = Product::query()->find($order->items->first()->product_id);
    $product->update(['base_price' => '500.00']);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/reorder', [
            'mode' => 'merge',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.summary.added_count', 1)
        ->assertJsonPath('data.summary.unavailable_count', 0)
        ->assertJsonPath('message', 'Items added to your cart.');

    expect(CartItem::query()->whereHas('cart', fn ($query) => $query->where('user_id', $user->id))->count())
        ->toBe(1);

    expect($order->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($order->fresh()->grand_total)->toBe('450.00');

    $cartItem = CartItem::query()->whereHas('cart', fn ($query) => $query->where('user_id', $user->id))->first();
    expect($cartItem?->quantity)->toBe(2);
});

test('reorder uses current cart pricing not historical order totals', function () {
    $user = User::factory()->create();
    $order = createHistoricalOrder($user);
    $product = Product::query()->find($order->items->first()->product_id);
    $product->update(['base_price' => '600.00']);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/reorder')
        ->assertSuccessful()
        ->assertJsonPath('data.cart.subtotal', '1200.00');
});

test('active orders cannot be reordered', function () {
    $user = User::factory()->create();
    $order = createHistoricalOrder($user, ['status' => OrderStatus::Preparing]);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/reorder')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['order']);
});

test('cancelled orders can be reordered', function () {
    $user = User::factory()->create();
    $order = createHistoricalOrder($user, [
        'status' => OrderStatus::Cancelled,
        'cancellation_reason' => 'Ordered by mistake',
        'cancelled_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/reorder')
        ->assertSuccessful()
        ->assertJsonPath('data.summary.added_count', 1);
});

test('reorder merge mode keeps existing cart items', function () {
    $user = User::factory()->create();
    $order = createHistoricalOrder($user);
    $existingProduct = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '300.00',
    ]);

    $cart = Cart::factory()->create(['user_id' => $user->id]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $existingProduct->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/reorder', [
            'mode' => 'merge',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.cart.item_count', 3);
});

test('reorder replace mode clears the existing cart first', function () {
    $user = User::factory()->create();
    $order = createHistoricalOrder($user);
    $existingProduct = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '300.00',
    ]);

    $cart = Cart::factory()->create(['user_id' => $user->id]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $existingProduct->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/reorder', [
            'mode' => 'replace',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.cart.item_count', 2);

    expect(
        CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $existingProduct->id)
            ->exists()
    )->toBeFalse();
});

test('reorder skips unavailable products and reports them', function () {
    $user = User::factory()->create();
    $order = createHistoricalOrder($user);
    Product::query()->find($order->items->first()->product_id)?->update(['is_available' => false]);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/reorder')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['order']);
});

test('customer cannot reorder another users order', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $order = createHistoricalOrder($owner);

    $this->actingAs($intruder)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/reorder')
        ->assertForbidden();
});
