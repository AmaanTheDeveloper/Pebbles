<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\CustomerOrderActions;
use App\Support\OrderChangeSettings;

function createCustomerChangeOrder(User $user, array $overrides = []): Order
{
    $branch = Branch::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true, 'is_available' => true, 'base_price' => '500.00']);

    $order = Order::factory()->for($user)->create(array_merge([
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway,
        'status' => OrderStatus::Pending,
        'subtotal' => '500.00',
        'grand_total' => '500.00',
        'delivery_fee' => '0.00',
        'created_at' => now(),
    ], $overrides));

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => '500.00',
        'quantity' => 1,
        'line_total' => '500.00',
    ]);

    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
        'amount' => '500.00',
    ]);

    return $order->fresh(['items.addOns', 'payment', 'branch']);
}

beforeEach(function () {
    Setting::setValue('customer_order_cancellation_enabled', true, 'boolean');
    Setting::setValue('customer_order_modification_enabled', true, 'boolean');
});

test('customer order payload includes change action metadata for pending orders', function () {
    $user = User::factory()->create();
    $order = createCustomerChangeOrder($user);

    $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$order->public_order_number)
        ->assertSuccessful()
        ->assertJsonPath('data.customer_actions.can_cancel', true)
        ->assertJsonPath('data.customer_actions.can_modify', true)
        ->assertJsonPath('data.customer_actions.changes_closed', false)
        ->assertJsonStructure([
            'data' => [
                'customer_actions' => [
                    'can_cancel',
                    'can_modify',
                    'changes_closed',
                    'changes_closed_heading',
                    'changes_closed_message',
                ],
            ],
        ]);
});

test('customer can cancel pending order', function () {
    $user = User::factory()->create();
    $order = createCustomerChangeOrder($user);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/cancel', [
            'cancellation_reason' => 'ordered_by_mistake',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

test('customer cannot cancel confirmed order', function () {
    $user = User::factory()->create();
    $order = createCustomerChangeOrder($user, [
        'status' => OrderStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/cancel')
        ->assertForbidden();
});

test('customer cannot cancel order once preparing', function () {
    $user = User::factory()->create();
    $order = createCustomerChangeOrder($user, [
        'status' => OrderStatus::Preparing,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/cancel')
        ->assertForbidden();
});

test('customer cannot cancel another users order', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $order = createCustomerChangeOrder($owner);

    $this->actingAs($intruder)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/cancel')
        ->assertForbidden();
});

test('customer can modify pending order quantity', function () {
    $user = User::factory()->create();
    $order = createCustomerChangeOrder($user);
    $productId = $order->items->first()->product_id;

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/modify', [
            'items' => [
                [
                    'product_id' => $productId,
                    'quantity' => 2,
                    'add_on_ids' => [],
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.quantity', 2);

    expect($order->fresh()->items)->toHaveCount(1)
        ->and($order->fresh()->items->first()->quantity)->toBe(2);
});

test('customer cannot modify confirmed order', function () {
    $user = User::factory()->create();
    $order = createCustomerChangeOrder($user, [
        'status' => OrderStatus::Confirmed,
        'confirmed_at' => now(),
    ]);
    $productId = $order->items->first()->product_id;

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/modify', [
            'items' => [
                [
                    'product_id' => $productId,
                    'quantity' => 2,
                    'add_on_ids' => [],
                ],
            ],
        ])
        ->assertForbidden();
});

test('disabled cancellation setting blocks pending cancel', function () {
    OrderChangeSettings::update([
        'customer_order_cancellation_enabled' => false,
    ]);

    $user = User::factory()->create();
    $order = createCustomerChangeOrder($user);

    expect($order->isCancellable())->toBeFalse()
        ->and($order->isModifiable())->toBeTrue();
});

test('confirmed order exposes closed change window message', function () {
    $user = User::factory()->create();
    $order = createCustomerChangeOrder($user, [
        'status' => OrderStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$order->public_order_number)
        ->assertSuccessful()
        ->assertJsonPath('data.customer_actions.can_cancel', false)
        ->assertJsonPath('data.customer_actions.can_modify', false)
        ->assertJsonPath('data.customer_actions.changes_closed', true)
        ->assertJsonPath('data.customer_actions.changes_closed_heading', CustomerOrderActions::CHANGES_CLOSED_HEADING);
});

test('admin settings expose customer order change toggles only', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings', [
            'customer_order_cancellation_enabled' => false,
            'customer_order_modification_enabled' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.customer_order_cancellation_enabled', false)
        ->assertJsonPath('data.customer_order_modification_enabled', true)
        ->assertJsonMissingPath('data.order_cancellation_minutes');
});
