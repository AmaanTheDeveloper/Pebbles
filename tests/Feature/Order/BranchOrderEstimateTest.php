<?php

use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\OrderEstimateResolver;
use App\Support\OrderEstimateSettings;

function seedCheckoutCart(User $user, Branch $branch, Product $product): void
{
    $cart = Cart::query()->firstOrCreate(
        ['user_id' => $user->id],
        ['branch_id' => $branch->id],
    );

    $cart->update(['branch_id' => $branch->id, 'coupon_id' => null]);
    $cart->items()->each(function (CartItem $item): void {
        $item->addOns()->delete();
        $item->delete();
    });

    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
}

test('branch preparation and delivery minutes produce different etas by branch', function () {
    Setting::setValue('order_estimated_minutes', 30, 'integer');

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '500.00',
    ]);

    $branchA = Branch::factory()->create([
        'is_active' => true,
        'preparation_minutes' => 10,
        'delivery_minutes' => 15,
    ]);
    $branchB = Branch::factory()->create([
        'is_active' => true,
        'preparation_minutes' => 25,
        'delivery_minutes' => 40,
    ]);

    expect(OrderEstimateResolver::minutesFor($branchA, OrderType::Delivery))->toBe(25)
        ->and(OrderEstimateResolver::minutesFor($branchB, OrderType::Delivery))->toBe(65);

    seedCheckoutCart($user, $branchA, $product);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branchA->id,
            'type' => OrderType::Takeaway->value,
            'payment_method' => 'cod',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.estimated_minutes', 10);

    seedCheckoutCart($user, $branchB, $product);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branchB->id,
            'type' => OrderType::Takeaway->value,
            'payment_method' => 'cod',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.estimated_minutes', 25);
});

test('delivery eta includes delivery minutes while takeaway and dine-in use preparation only', function () {
    $branch = Branch::factory()->create([
        'is_active' => true,
        'preparation_minutes' => 12,
        'delivery_minutes' => 18,
    ]);

    expect(OrderEstimateResolver::minutesFor($branch, OrderType::Delivery))->toBe(30)
        ->and(OrderEstimateResolver::minutesFor($branch, OrderType::Takeaway))->toBe(12)
        ->and(OrderEstimateResolver::minutesFor($branch, OrderType::DineIn))->toBe(12);

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '500.00',
    ]);
    $zone = DeliveryZone::factory()->for($branch)->create([
        'is_active' => true,
        'minimum_order_amount' => '0.00',
        'delivery_fee' => '100.00',
    ]);

    seedCheckoutCart($user, $branch, $product);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Delivery->value,
            'payment_method' => 'cod',
            'delivery_zone_id' => $zone->id,
            'delivery_recipient_name' => 'Ali',
            'delivery_phone' => '03001234567',
            'delivery_line1' => 'Street 1',
            'delivery_city' => 'Karachi',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.estimated_minutes', 30);

    seedCheckoutCart($user, $branch, $product);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::DineIn->value,
            'table_number' => '4',
            'payment_method' => 'cod',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.estimated_minutes', 12);
});

test('order estimated minutes snapshot does not change when branch eta is updated later', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create([
        'is_active' => true,
        'preparation_minutes' => 20,
        'delivery_minutes' => 30,
    ]);
    $product = Product::factory()->create([
        'is_active' => true,
        'is_available' => true,
        'base_price' => '500.00',
    ]);

    seedCheckoutCart($user, $branch, $product);

    $orderNumber = $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
            'payment_method' => 'cod',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.estimated_minutes', 20)
        ->json('data.order_number');

    $branch->update([
        'preparation_minutes' => 45,
        'delivery_minutes' => 60,
    ]);

    $order = Order::query()->where('public_order_number', $orderNumber)->firstOrFail();

    expect($order->estimated_minutes)->toBe(20);

    $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$orderNumber)
        ->assertSuccessful()
        ->assertJsonPath('data.estimated_minutes', 20);
});

test('super admin branch update rejects invalid eta minutes', function () {
    $admin = User::factory()->superAdmin()->create();
    $branch = Branch::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/branches/'.$branch->id, [
            'preparation_minutes' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['preparation_minutes']);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/branches/'.$branch->id, [
            'delivery_minutes' => 500,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['delivery_minutes']);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/branches/'.$branch->id, [
            'preparation_minutes' => 22,
            'delivery_minutes' => 33,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.preparation_minutes', 22)
        ->assertJsonPath('data.delivery_minutes', 33);
});

test('manager cannot update branch eta fields', function () {
    $branch = Branch::factory()->create([
        'is_active' => true,
        'preparation_minutes' => 20,
        'delivery_minutes' => 30,
    ]);
    $manager = User::factory()->manager($branch)->create();

    $this->actingAs($manager)
        ->patchJson('/api/v1/admin/branches/'.$branch->id, [
            'preparation_minutes' => 40,
        ])
        ->assertForbidden();

    expect($branch->fresh()->preparation_minutes)->toBe(20);
});

test('resolver falls back to global setting when branch minutes are zero', function () {
    Setting::setValue('order_estimated_minutes', 42, 'integer');

    $branch = Branch::factory()->make([
        'preparation_minutes' => 0,
        'delivery_minutes' => 0,
    ]);

    expect(OrderEstimateResolver::minutesFor($branch, OrderType::Delivery))->toBe(42)
        ->and(OrderEstimateResolver::minutesFor($branch, OrderType::Takeaway))->toBe(42)
        ->and(OrderEstimateSettings::minutes())->toBe(42);
});

test('catalog branches expose preparation and delivery minutes', function () {
    Branch::factory()->create([
        'is_active' => true,
        'slug' => 'eta-branch',
        'preparation_minutes' => 14,
        'delivery_minutes' => 21,
    ]);

    $this->getJson('/api/v1/catalog/branches')
        ->assertSuccessful()
        ->assertJsonFragment([
            'slug' => 'eta-branch',
            'preparation_minutes' => 14,
            'delivery_minutes' => 21,
        ]);
});
