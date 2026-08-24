<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddOn;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;

test('analytics overview kpis match deterministic fixtures', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'UTC'));

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $branch = Branch::factory()->create();

    $completed = Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Completed,
        'type' => OrderType::Takeaway,
        'subtotal' => '1000.00',
        'discount_amount' => '100.00',
        'delivery_fee' => '0.00',
        'grand_total' => '900.00',
        'created_at' => '2026-08-10 10:00:00',
    ]);
    Payment::factory()->for($completed)->create([
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Verified,
        'amount' => '900.00',
    ]);
    OrderItem::factory()->for($completed)->create([
        'product_name' => 'Burger',
        'unit_price' => '900.00',
        'quantity' => 1,
        'line_total' => '900.00',
    ]);

    $pending = Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Pending,
        'type' => OrderType::Delivery,
        'subtotal' => '500.00',
        'discount_amount' => '0.00',
        'delivery_fee' => '100.00',
        'grand_total' => '600.00',
        'delivery_zone_name' => 'Zone A',
        'created_at' => '2026-08-12 11:00:00',
    ]);
    Payment::factory()->for($pending)->create([
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Rejected,
        'amount' => '600.00',
    ]);
    OrderItem::factory()->for($pending)->create([
        'product_name' => 'Fries',
        'unit_price' => '500.00',
        'quantity' => 1,
        'line_total' => '500.00',
    ]);

    $cancelled = Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Cancelled,
        'grand_total' => '300.00',
        'discount_amount' => '0.00',
        'created_at' => '2026-08-13 09:00:00',
        'cancellation_reason' => 'Changed mind',
    ]);
    Payment::factory()->for($cancelled)->create([
        'status' => PaymentStatus::Pending,
        'amount' => '300.00',
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/overview?preset=custom&from=2026-08-01&to=2026-08-15')
        ->assertSuccessful();

    // Gross = non-cancelled (grand_total + discount) = (900+100) + (600+0) = 1600
    // Net = non-cancelled grand_total = 900 + 600 = 1500
    // AOV = 1500 / 2 = 750
    expect($response->json('data.kpis.gross_revenue'))->toBe('1600.00')
        ->and($response->json('data.kpis.net_revenue'))->toBe('1500.00')
        ->and($response->json('data.kpis.total_orders'))->toBe(3)
        ->and($response->json('data.kpis.completed_orders'))->toBe(1)
        ->and($response->json('data.kpis.cancelled_orders'))->toBe(1)
        ->and($response->json('data.kpis.average_order_value'))->toBe('750.00')
        ->and($response->json('data.kpis.successful_payments'))->toBe(1)
        ->and($response->json('data.kpis.rejected_payments'))->toBe(1);

    $products = $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/products?preset=custom&from=2026-08-01&to=2026-08-15&product_sort=highest_revenue')
        ->assertSuccessful()
        ->json('data.products');

    expect(collect($products)->firstWhere('product', 'Burger')['revenue'])->toBe('900.00')
        ->and(collect($products)->firstWhere('product', 'Fries')['units_sold'])->toBe(1);

    $branches = $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/branches?preset=custom&from=2026-08-01&to=2026-08-15')
        ->assertSuccessful()
        ->json('data.branches.0');

    expect($branches['revenue'])->toBe('1500.00')
        ->and($branches['orders'])->toBe(3)
        ->and($branches['cancelled_orders'])->toBe(1);

    CarbonImmutable::setTestNow();
});

test('payment status distribution is independent of order status', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'UTC'));

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();

    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Completed,
        'grand_total' => '100.00',
        'created_at' => '2026-08-10 10:00:00',
    ]);
    Payment::factory()->for($order)->create([
        'status' => PaymentStatus::Rejected,
        'amount' => '100.00',
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/overview?preset=custom&from=2026-08-01&to=2026-08-15')
        ->assertSuccessful();

    $paymentStatuses = collect($response->json('data.payment_status_distribution'))
        ->pluck('count', 'status');

    expect($paymentStatuses['rejected'])->toBe(1)
        ->and($paymentStatuses['verified'])->toBe(0)
        ->and($response->json('data.kpis.completed_orders'))->toBe(1)
        ->and($response->json('data.kpis.successful_payments'))->toBe(0);

    CarbonImmutable::setTestNow();
});

test('add-on analytics use snapshot names and revenue', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'UTC'));

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Completed,
        'grand_total' => '1100.00',
        'created_at' => '2026-08-10 10:00:00',
    ]);
    $item = OrderItem::factory()->for($order)->create([
        'product_name' => 'Burger',
        'line_total' => '1000.00',
        'quantity' => 1,
    ]);
    OrderItemAddOn::factory()->for($item, 'orderItem')->create([
        'add_on_name' => 'Extra Cheese',
        'quantity' => 2,
        'line_total' => '100.00',
    ]);
    Payment::factory()->for($order)->create(['status' => PaymentStatus::Verified]);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/products?preset=custom&from=2026-08-01&to=2026-08-15')
        ->assertSuccessful()
        ->assertJsonPath('data.add_ons.0.add_on', 'Extra Cheese')
        ->assertJsonPath('data.add_ons.0.times_selected', 2)
        ->assertJsonPath('data.add_ons.0.revenue_contribution', '100.00');

    CarbonImmutable::setTestNow();
});
