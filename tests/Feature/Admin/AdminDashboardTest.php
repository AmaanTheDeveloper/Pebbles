<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

test('dashboard aggregates are server derived', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();

    Order::factory()->for($customer)->create([
        'status' => OrderStatus::Pending,
        'grand_total' => '500.00',
        'created_at' => now(),
    ]);
    Order::factory()->for($customer)->create([
        'status' => OrderStatus::Preparing,
        'grand_total' => '300.00',
        'created_at' => now(),
    ]);
    Order::factory()->for($customer)->create([
        'status' => OrderStatus::Ready,
        'grand_total' => '200.00',
        'created_at' => now(),
    ]);
    Order::factory()->for($customer)->create([
        'status' => OrderStatus::Cancelled,
        'grand_total' => '999.00',
        'created_at' => now(),
    ]);

    $submittedOrder = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Pending,
        'grand_total' => '0.00',
    ]);
    Payment::factory()->for($submittedOrder)->create([
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful();

    expect($response->json('data.orders_today'))->toBe(5)
        ->and($response->json('data.pending_orders'))->toBe(2)
        ->and($response->json('data.preparing_orders'))->toBe(1)
        ->and($response->json('data.ready_orders'))->toBe(1)
        ->and($response->json('data.pending_payment_verification'))->toBe(1)
        ->and((float) $response->json('data.sales_today'))->toBe(1000.0);
});
