<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\CustomerOrderLifecycle;

test('customer orders index separates active and recent orders', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['is_active' => true]);

    $active = Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Preparing,
        'type' => OrderType::Delivery,
    ]);

    $completed = Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Completed,
        'type' => OrderType::Delivery,
        'updated_at' => now()->subHour(),
    ]);

    $cancelled = Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Cancelled,
        'type' => OrderType::Takeaway,
        'cancellation_reason' => 'Ordered by mistake',
        'cancelled_at' => now()->subMinutes(30),
    ]);

    OrderItem::factory()->for($active)->create();
    OrderItem::factory()->for($completed)->create();
    OrderItem::factory()->for($cancelled)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/orders?scope=all');

    $response->assertSuccessful()
        ->assertJsonPath('data.active.0.order_number', $active->public_order_number)
        ->assertJsonPath('data.active.0.lifecycle.is_active', true)
        ->assertJsonPath('data.recent.0.lifecycle.is_final', true);

    $recentNumbers = collect($response->json('data.recent'))->pluck('order_number')->all();

    expect($recentNumbers)->toContain($completed->public_order_number, $cancelled->public_order_number)
        ->and($recentNumbers)->not->toContain($active->public_order_number);
});

test('completed delivery orders show delivered label', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Completed,
        'type' => OrderType::Delivery,
    ]);

    expect(CustomerOrderLifecycle::customerStatusLabel($order))->toBe('Delivered')
        ->and(CustomerOrderLifecycle::isFinal($order))->toBeTrue()
        ->and(CustomerOrderLifecycle::isActive($order))->toBeFalse();
});

test('cancelled orders distinguish customer and admin source', function () {
    $customerOrder = Order::factory()->create([
        'status' => OrderStatus::Cancelled,
        'cancellation_reason' => 'Ordered by mistake',
    ]);

    $adminOrder = Order::factory()->create([
        'status' => OrderStatus::Cancelled,
        'cancellation_reason' => 'Item unavailable',
    ]);

    expect(CustomerOrderLifecycle::cancellationSource($customerOrder))->toBe('customer')
        ->and(CustomerOrderLifecycle::customerStatusLabel($customerOrder))->toBe('Cancelled by Customer')
        ->and(CustomerOrderLifecycle::cancellationSource($adminOrder))->toBe('admin')
        ->and(CustomerOrderLifecycle::customerStatusLabel($adminOrder))->toBe('Cancelled by Admin');
});

test('customer order detail includes lifecycle metadata', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Completed,
        'type' => OrderType::Takeaway,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$order->public_order_number)
        ->assertSuccessful()
        ->assertJsonPath('data.lifecycle.is_final', true)
        ->assertJsonPath('data.lifecycle.customer_status_label', 'Picked Up')
        ->assertJsonPath('data.customer_actions.can_cancel', false)
        ->assertJsonPath('data.customer_actions.can_modify', false);
});
