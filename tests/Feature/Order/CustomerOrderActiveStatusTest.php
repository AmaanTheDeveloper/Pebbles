<?php

use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;

test('guest cannot fetch active order status', function () {
    $this->getJson('/api/v1/orders/active-status')
        ->assertUnauthorized();
});

test('customer with no orders has no active orders', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/orders/active-status')
        ->assertSuccessful()
        ->assertJsonPath('data.has_active_orders', false);
});

test('customer with only finalized orders has no active orders', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();

    Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Completed,
    ]);

    Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Cancelled,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/orders/active-status')
        ->assertSuccessful()
        ->assertJsonPath('data.has_active_orders', false);
});

test('customer active order status reflects non-final order statuses', function (OrderStatus $status) {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();

    Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'status' => $status,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/orders/active-status')
        ->assertSuccessful()
        ->assertJsonPath('data.has_active_orders', true);
})->with([
    'pending' => OrderStatus::Pending,
    'confirmed' => OrderStatus::Confirmed,
    'preparing' => OrderStatus::Preparing,
    'ready' => OrderStatus::Ready,
    'out for delivery' => OrderStatus::OutForDelivery,
]);

test('customer active order status ignores other customers orders', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $branch = Branch::factory()->create();

    Order::factory()->for($bob)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Preparing,
    ]);

    $this->actingAs($alice)
        ->getJson('/api/v1/orders/active-status')
        ->assertSuccessful()
        ->assertJsonPath('data.has_active_orders', false);
});

test('customer active order status is true when at least one active order exists among finalized orders', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();

    Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Completed,
    ]);

    Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Preparing,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/orders/active-status')
        ->assertSuccessful()
        ->assertJsonPath('data.has_active_orders', true);
});
