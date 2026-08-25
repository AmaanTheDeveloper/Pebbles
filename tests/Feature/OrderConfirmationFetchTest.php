<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

test('owner can fetch order confirmation payload by order number', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['name' => 'Pebbles Gulshan']);

    $order = Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'type' => OrderType::Delivery,
        'status' => OrderStatus::Pending,
        'estimated_minutes' => 35,
        'grand_total' => '1550.00',
        'public_order_number' => 'PB-CONFIRM-1',
    ]);

    Payment::factory()->for($order)->create([
        'status' => PaymentStatus::Pending,
        'method' => PaymentMethod::Cod,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/orders/PB-CONFIRM-1')
        ->assertSuccessful()
        ->assertJsonPath('data.order_number', 'PB-CONFIRM-1')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.fulfillment_type', 'delivery')
        ->assertJsonPath('data.estimated_minutes', 35)
        ->assertJsonPath('data.total', '1550.00')
        ->assertJsonPath('data.branch.name', 'Pebbles Gulshan')
        ->assertJsonPath('data.payment.requires_proof', false);
});

test('non owner cannot fetch another users order confirmation payload', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $branch = Branch::factory()->create();

    $order = Order::factory()->for($owner)->create([
        'branch_id' => $branch->id,
        'public_order_number' => 'PB-CONFIRM-2',
    ]);
    Payment::factory()->for($order)->create();

    $this->actingAs($intruder)
        ->getJson('/api/v1/orders/PB-CONFIRM-2')
        ->assertForbidden();
});

test('guest cannot fetch order confirmation payload', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();

    $order = Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'public_order_number' => 'PB-CONFIRM-3',
    ]);
    Payment::factory()->for($order)->create();

    $this->getJson('/api/v1/orders/PB-CONFIRM-3')->assertUnauthorized();
});
