<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;

test('empty period returns zeros without errors', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/overview?preset=custom&from=2020-01-01&to=2020-01-31')
        ->assertSuccessful();

    expect($response->json('data.kpis.net_revenue'))->toBe('0.00')
        ->and($response->json('data.kpis.total_orders'))->toBe(0)
        ->and($response->json('data.kpis.average_order_value'))->toBe('0.00')
        ->and($response->json('data.comparison.revenue.direction'))->toBe('flat');

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/sales?preset=custom&from=2020-01-01&to=2020-01-31')
        ->assertSuccessful()
        ->assertJsonPath('data.series', []);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/products?preset=custom&from=2020-01-01&to=2020-01-31')
        ->assertSuccessful()
        ->assertJsonPath('data.products', [])
        ->assertJsonPath('data.add_ons', []);
});

test('only cancelled orders produce zero net revenue and cancellation metrics', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'UTC'));

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Cancelled,
        'grand_total' => '400.00',
        'created_at' => '2026-08-10 10:00:00',
    ]);
    Payment::factory()->for($order)->create(['status' => PaymentStatus::Pending]);

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/overview?preset=custom&from=2026-08-01&to=2026-08-15')
        ->assertSuccessful();

    expect($response->json('data.kpis.net_revenue'))->toBe('0.00')
        ->and($response->json('data.kpis.cancelled_orders'))->toBe(1)
        ->and($response->json('data.kpis.cancellation_revenue'))->toBe('400.00')
        ->and($response->json('data.kpis.average_order_value'))->toBe('0.00');

    CarbonImmutable::setTestNow();
});

test('comparison shows new when previous period has zero revenue', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC'));

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Completed,
        'grand_total' => '100.00',
        'discount_amount' => '0.00',
        'created_at' => '2026-08-18 10:00:00',
    ]);
    Payment::factory()->for($order)->create(['status' => PaymentStatus::Verified]);

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/overview?preset=custom&from=2026-08-16&to=2026-08-20')
        ->assertSuccessful();

    expect($response->json('data.comparison.revenue.direction'))->toBe('new')
        ->and($response->json('data.comparison.revenue.percent'))->toBeNull();

    CarbonImmutable::setTestNow();
});

test('csv export is authorized and streams content', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'UTC'));

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Completed,
        'public_order_number' => 'PB-EXPORT-1',
        'grand_total' => '50.00',
        'created_at' => '2026-08-10 10:00:00',
    ]);
    Payment::factory()->for($order)->create(['status' => PaymentStatus::Verified]);

    $response = $this->actingAs($admin)
        ->get('/api/v1/admin/analytics/export?preset=custom&from=2026-08-01&to=2026-08-15&report=orders');

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->streamedContent())->toContain('PB-EXPORT-1');

    CarbonImmutable::setTestNow();
});

test('single day range includes orders on that calendar day', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'UTC'));

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Completed,
        'grand_total' => '75.00',
        'discount_amount' => '0.00',
        'created_at' => '2026-08-15 23:30:00',
    ]);
    Payment::factory()->for($order)->create(['status' => PaymentStatus::Verified]);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/overview?preset=custom&from=2026-08-15&to=2026-08-15')
        ->assertSuccessful()
        ->assertJsonPath('data.kpis.total_orders', 1)
        ->assertJsonPath('data.kpis.net_revenue', '75.00');

    CarbonImmutable::setTestNow();
});
