<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\CustomerOrderActions;
use App\Support\OrderEstimatePayload;
use Illuminate\Support\Carbon;

test('pending order exposes cancel and modify but no estimated time countdown', function () {
    Setting::setValue('order_estimated_minutes', 30, 'integer');

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

    $orderNumber = $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => 'takeaway',
            'payment_method' => 'cod',
        ])
        ->assertSuccessful()
        ->json('data.order_number');

    $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$orderNumber)
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Pending->value)
        ->assertJsonPath('data.estimated_time', null)
        ->assertJsonPath('data.customer_actions.can_cancel', true)
        ->assertJsonPath('data.customer_actions.can_modify', true)
        ->assertJsonPath('data.customer_actions.changes_closed', false);
});

test('admin confirmation starts eta and closes customer changes immediately', function () {
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

    $this->travel(8)->minutes();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$orderNumber.'/status', [
            'status' => OrderStatus::Confirmed->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Confirmed->value);

    $detail = $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$orderNumber)
        ->assertSuccessful();

    expect($detail->json('data.confirmed_at'))->not->toBeNull()
        ->and($detail->json('data.estimated_time.minutes'))->toBe(30)
        ->and($detail->json('data.estimated_time.starts_at'))->toBe(now()->toIso8601String())
        ->and($detail->json('data.customer_actions.can_cancel'))->toBeFalse()
        ->and($detail->json('data.customer_actions.can_modify'))->toBeFalse()
        ->and($detail->json('data.customer_actions.changes_closed'))->toBeTrue();

    $deadline = Carbon::parse($detail->json('data.estimated_time.deadline_at'));
    expect((int) now()->diffInMinutes($deadline))->toBe(30);
});

test('eta continues from confirmation after refresh without resetting', function () {
    Setting::setValue('order_estimated_minutes', 30, 'integer');

    $user = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'estimated_minutes' => 30,
        'created_at' => now()->subMinutes(20),
    ]);

    $this->travelTo(now()->startOfMinute());

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$order->public_order_number.'/status', [
            'status' => OrderStatus::Confirmed->value,
        ])
        ->assertSuccessful();

    $this->travel(8)->minutes();

    $detail = $this->actingAs($user)
        ->getJson('/api/v1/orders/'.$order->public_order_number)
        ->assertSuccessful();

    $deadline = Carbon::parse($detail->json('data.estimated_time.deadline_at'));
    expect((int) now()->diffInMinutes($deadline))->toBe(22);
});

test('customer can cancel and modify while pending', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'estimated_minutes' => 30,
        'created_at' => now(),
    ]);

    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
        'amount' => $order->grand_total,
    ]);

    expect(CustomerOrderActions::canCancel($order))->toBeTrue()
        ->and(CustomerOrderActions::canModify($order))->toBeTrue();

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/cancel', [
            'cancellation_reason' => 'ordered_by_mistake',
        ])
        ->assertSuccessful();
});

test('customer changes close immediately when order is confirmed', function () {
    $user = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'estimated_minutes' => 30,
        'created_at' => now()->subMinutes(25),
    ]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$order->public_order_number.'/status', [
            'status' => OrderStatus::Confirmed->value,
        ])
        ->assertSuccessful();

    expect(CustomerOrderActions::canCancel($order->fresh()))->toBeFalse()
        ->and(CustomerOrderActions::canModify($order->fresh()))->toBeFalse()
        ->and(CustomerOrderActions::changesAreClosed($order->fresh()))->toBeTrue();

    $this->travel(21)->minutes();

    expect(CustomerOrderActions::canCancel($order->fresh()))->toBeFalse();
});

test('order estimate payload returns null until confirmation', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'estimated_minutes' => 30,
        'created_at' => now(),
    ]);

    expect(OrderEstimatePayload::for($order))->toBeNull();

    $order->update([
        'status' => OrderStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    expect(OrderEstimatePayload::for($order->fresh()))->not->toBeNull();
});

test('payment verification does not confirm pending order', function () {
    $customer = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Pending,
    ]);

    $payment = Payment::factory()->for($order)->create([
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
        'amount' => $order->grand_total,
    ]);

    PaymentProof::factory()->for($payment)->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/orders/'.$order->public_order_number.'/payment/verify')
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Verified->value)
        ->assertJsonPath('data.status', OrderStatus::Pending->value);

    expect($order->fresh()->confirmed_at)->toBeNull();
});
