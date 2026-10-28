<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;

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

test('dashboard sales today respects timezone boundaries and does not double count orders', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-26 12:00:00', 'UTC'));

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();

    // Order created yesterday - should NOT be included in sales today
    Order::factory()->for($customer)->create([
        'status' => OrderStatus::Completed,
        'grand_total' => '250.00',
        'created_at' => '2026-08-25 23:59:59',
    ]);

    // Order created today at start of day - should be included
    Order::factory()->for($customer)->create([
        'status' => OrderStatus::Completed,
        'grand_total' => '400.00',
        'created_at' => '2026-08-26 00:00:00',
    ]);

    // Order created today during day - should be included
    Order::factory()->for($customer)->create([
        'status' => OrderStatus::Confirmed,
        'grand_total' => '600.00',
        'created_at' => '2026-08-26 10:00:00',
    ]);

    // Cancelled order created today - should NOT be counted in sales
    Order::factory()->for($customer)->create([
        'status' => OrderStatus::Cancelled,
        'grand_total' => '800.00',
        'created_at' => '2026-08-26 11:00:00',
    ]);

    // Order created tomorrow at exact 00:00:00 - should NOT be included in today's sales (exclusive upper bound)
    Order::factory()->for($customer)->create([
        'status' => OrderStatus::Completed,
        'grand_total' => '500.00',
        'created_at' => '2026-08-27 00:00:00',
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful();

    // Total non-cancelled sales today: 400 + 600 = 1000
    expect((float) $response->json('data.sales_today'))->toBe(1000.0)
        ->and($response->json('data.orders_today'))->toBe(3); // 400, 600, 800 (cancelled still counted in orders count today)

    CarbonImmutable::setTestNow();
});
