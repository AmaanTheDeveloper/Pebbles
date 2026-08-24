<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddOn;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;

test('customer order list returns only owned orders', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $branch = Branch::factory()->create();

    $aliceOrder = Order::factory()->for($alice)->create([
        'branch_id' => $branch->id,
        'public_order_number' => 'PB-11111',
    ]);
    Payment::factory()->for($aliceOrder)->create(['status' => PaymentStatus::Pending]);

    $bobOrder = Order::factory()->for($bob)->create([
        'branch_id' => $branch->id,
        'public_order_number' => 'PB-22222',
    ]);
    Payment::factory()->for($bobOrder)->create();

    $response = $this->actingAs($alice)->getJson('/api/v1/orders?scope=all');

    $response->assertSuccessful()
        ->assertJsonPath('data.active.0.order_number', 'PB-11111');

    $activeNumbers = collect($response->json('data.active'))->pluck('order_number');
    $recentNumbers = collect($response->json('data.recent'))->pluck('order_number');

    expect($activeNumbers)->toContain('PB-11111')
        ->and($activeNumbers->merge($recentNumbers))->not->toContain('PB-22222');
});

test('customer can view own order detail with snapshots and status history', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['name' => 'Pebbles DHA']);
    $product = Product::factory()->create([
        'name' => 'Live Burger',
        'base_price' => '1200.00',
    ]);

    $order = Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway,
        'status' => OrderStatus::Pending,
        'subtotal' => '1000.00',
        'grand_total' => '1000.00',
    ]);

    $item = OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'product_name' => 'Historical Burger',
        'unit_price' => '1000.00',
        'quantity' => 1,
        'line_total' => '1000.00',
    ]);

    OrderItemAddOn::factory()->for($item, 'orderItem')->create([
        'add_on_name' => 'Historical Cheese',
        'unit_price' => '50.00',
        'line_total' => '50.00',
    ]);

    Payment::factory()->for($order)->create(['status' => PaymentStatus::Pending]);

    OrderStatusHistory::factory()->for($order)->create([
        'from_status' => null,
        'to_status' => OrderStatus::Pending,
        'changed_by' => $user->id,
    ]);

    $product->update(['name' => 'Renamed Burger', 'base_price' => '1500.00']);

    $detail = $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$order->public_order_number)
        ->assertSuccessful()
        ->assertJsonPath('data.order_number', $order->public_order_number)
        ->assertJsonPath('data.items.0.product_name', 'Historical Burger')
        ->assertJsonPath('data.items.0.unit_price', '1000.00')
        ->assertJsonPath('data.items.0.add_ons.0.name', 'Historical Cheese')
        ->assertJsonPath('data.branch.name', 'Pebbles DHA')
        ->assertJsonMissingPath('data.id');

    expect(json_encode($detail->json()))->not->toContain('Live Burger')
        ->and(json_encode($detail->json()))->not->toContain('1500.00')
        ->and(json_encode($detail->json()))->not->toContain('payment-proofs/')
        ->and(json_encode($detail->json()))->not->toContain('rejection_reason');

    $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$order->public_order_number.'/status-history')
        ->assertSuccessful()
        ->assertJsonPath('data.0.status', OrderStatus::Pending->value);
});

test('customer cannot view another users order detail', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $order = Order::factory()->for($owner)->create();
    Payment::factory()->for($order)->create();

    $this->actingAs($intruder)
        ->getJson('/api/v1/orders/'.$order->public_order_number)
        ->assertForbidden();
});

test('guests cannot list or view customer orders', function () {
    $order = Order::factory()->create();

    $this->getJson('/api/v1/orders')->assertUnauthorized();
    $this->getJson('/api/v1/orders/'.$order->public_order_number)->assertUnauthorized();
});
