<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;

test('superadmin can list filter and paginate orders', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create(['name' => 'Alice Customer']);
    $branch = Branch::factory()->create(['name' => 'DHA Branch']);

    $matching = Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'public_order_number' => 'PB-ADMIN-1',
        'status' => OrderStatus::Pending,
        'type' => OrderType::Delivery,
        'grand_total' => '1500.00',
    ]);
    Payment::factory()->for($matching)->create([
        'status' => PaymentStatus::Submitted,
        'method' => PaymentMethod::BankTransfer,
    ]);

    Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'public_order_number' => 'PB-OTHER',
        'status' => OrderStatus::Completed,
        'type' => OrderType::Takeaway,
        'grand_total' => '100.00',
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/orders?status=pending&search=ADMIN&sort=newest')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.order_number', 'PB-ADMIN-1')
        ->assertJsonPath('data.0.payment_status', PaymentStatus::Submitted->value)
        ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

test('superadmin can view order detail with snapshots and history', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $branch = Branch::factory()->create();
    $product = Product::factory()->create(['name' => 'Live Name', 'base_price' => '2000.00']);

    $order = Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Pending,
        'delivery_fee' => '150.00',
        'grand_total' => '1150.00',
    ]);
    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'product_name' => 'Snapshot Name',
        'unit_price' => '1000.00',
        'line_total' => '1000.00',
    ]);
    Payment::factory()->for($order)->create(['status' => PaymentStatus::Pending]);
    OrderStatusHistory::factory()->for($order)->create([
        'from_status' => null,
        'to_status' => OrderStatus::Pending,
        'changed_by' => $customer->id,
    ]);

    $product->update(['name' => 'Changed Name', 'base_price' => '9999.00']);

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/orders/'.$order->public_order_number)
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.product_name', 'Snapshot Name')
        ->assertJsonPath('data.items.0.unit_price', '1000.00')
        ->assertJsonPath('data.delivery_fee', '150.00')
        ->assertJsonPath('data.allowed_next_statuses.0', OrderStatus::Confirmed->value);

    expect(json_encode($response->json()))->not->toContain('Changed Name')
        ->and(json_encode($response->json()))->not->toContain('9999.00');
});

test('authorized status transition is recorded in history', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Pending]);
    Payment::factory()->for($order)->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$order->public_order_number.'/status', [
            'status' => OrderStatus::Confirmed->value,
            'note' => 'Kitchen accepted',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Confirmed->value);

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and(OrderStatusHistory::query()->where('order_id', $order->id)->where('to_status', OrderStatus::Confirmed)->exists())->toBeTrue();
});

test('invalid status transition is rejected', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Pending]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$order->public_order_number.'/status', [
            'status' => OrderStatus::Completed->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('customer cannot transition order status via admin api', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Pending]);

    $this->actingAs($customer)
        ->patchJson('/api/v1/admin/orders/'.$order->public_order_number.'/status', [
            'status' => OrderStatus::Confirmed->value,
        ])
        ->assertForbidden();
});
