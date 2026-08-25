<?php

use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\OrderEstimatePayload;
use App\Support\OrderEstimateSettings;
use Illuminate\Support\Carbon;

test('admin can configure estimated order time', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings', [
            'order_estimated_minutes' => 25,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.order_estimated_minutes', 25);

    expect(OrderEstimateSettings::minutes())->toBe(25);
});

test('estimated order time rejects invalid values', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings', [
            'order_estimated_minutes' => 3,
        ])
        ->assertUnprocessable();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings', [
            'order_estimated_minutes' => 300,
        ])
        ->assertUnprocessable();
});

test('checkout snapshots configured estimated minutes on the order', function () {
    Setting::setValue('order_estimated_minutes', 20, 'integer');

    $user = User::factory()->create();
    $branch = Branch::factory()->create([
        'is_active' => true,
        'preparation_minutes' => 0,
        'delivery_minutes' => 0,
    ]);
    $product = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '500.00',
    ]);

    $cart = Cart::factory()->create(['user_id' => $user->id, 'branch_id' => $branch->id]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => 'takeaway',
        'payment_method' => 'cod',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.estimated_minutes', 20);

    Setting::setValue('order_estimated_minutes', 40, 'integer');

    expect($response->json('data.estimated_minutes'))->toBe(20);
});

test('customer order detail exposes snapshotted estimated minutes after confirmation', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create([
        'is_active' => true,
        'preparation_minutes' => 0,
        'delivery_minutes' => 0,
    ]);
    $product = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '500.00',
    ]);

    Setting::setValue('order_estimated_minutes', 35, 'integer');

    $cart = Cart::factory()->create(['user_id' => $user->id, 'branch_id' => $branch->id]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $checkout = $this->actingAs($user)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => 'delivery',
        'payment_method' => 'cod',
        'delivery_zone_id' => DeliveryZone::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ])->id,
        'delivery_recipient_name' => 'Ali',
        'delivery_phone' => '03001234567',
        'delivery_line1' => 'Street 1',
        'delivery_city' => 'Karachi',
    ])->assertSuccessful();

    $orderNumber = $checkout->json('data.order_number');

    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$orderNumber.'/status', [
            'status' => 'confirmed',
        ])
        ->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$orderNumber)
        ->assertSuccessful()
        ->assertJsonPath('data.estimated_minutes', 35)
        ->assertJsonPath('data.estimated_time.minutes', 35)
        ->assertJsonStructure([
            'data' => [
                'estimated_time' => [
                    'minutes',
                    'starts_at',
                    'deadline_at',
                    'server_now',
                ],
            ],
        ]);
});

test('estimated time deadline remains stable after admin changes global setting', function () {
    Setting::setValue('order_estimated_minutes', 30, 'integer');

    $user = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $branch = Branch::factory()->create([
        'is_active' => true,
        'preparation_minutes' => 0,
        'delivery_minutes' => 0,
    ]);
    $product = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '500.00',
    ]);

    $cart = Cart::factory()->create(['user_id' => $user->id, 'branch_id' => $branch->id]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->travelTo(now()->startOfMinute());

    $orderNumber = $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => 'takeaway',
            'payment_method' => 'cod',
        ])
        ->assertSuccessful()
        ->json('data.order_number');

    Setting::setValue('order_estimated_minutes', 60, 'integer');

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$orderNumber.'/status', [
            'status' => 'confirmed',
        ])
        ->assertSuccessful();

    $this->travel(5)->minutes();

    $detail = $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$orderNumber)
        ->assertSuccessful();

    expect($detail->json('data.estimated_time.minutes'))->toBe(30);

    $startsAt = Carbon::parse($detail->json('data.estimated_time.starts_at'));
    $deadline = Carbon::parse($detail->json('data.estimated_time.deadline_at'));

    expect((int) $startsAt->diffInMinutes($deadline))->toBe(30);
});

test('order estimate payload helper computes deadline from confirmation time', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'created_at' => now()->startOfMinute()->subMinutes(10),
        'confirmed_at' => now()->startOfMinute(),
        'estimated_minutes' => 25,
    ]);

    $payload = OrderEstimatePayload::for($order);

    expect($payload)->not->toBeNull()
        ->and($payload['minutes'])->toBe(25)
        ->and($payload['starts_at'])->toBe($order->confirmed_at->toIso8601String())
        ->and($payload['deadline_at'])->toBe($order->confirmed_at->copy()->addMinutes(25)->toIso8601String());
});
