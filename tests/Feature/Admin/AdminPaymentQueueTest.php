<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function createAdminPayableOrder(User $user, array $paymentOverrides = []): Order
{
    $branch = Branch::factory()->create(['is_active' => true]);
    $order = Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Pending,
        'subtotal' => '1000.00',
        'grand_total' => '1000.00',
        'delivery_fee' => '0.00',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_name' => 'Snapshot Burger',
        'unit_price' => '1000.00',
        'quantity' => 1,
        'line_total' => '1000.00',
    ]);

    Payment::factory()->for($order)->create(array_merge([
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
        'amount' => '1000.00',
    ], $paymentOverrides));

    return $order->fresh('payment');
}

test('superadmin can list payment verification queue', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();

    $submitted = createAdminPayableOrder($customer, [
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
    ]);
    createAdminPayableOrder($customer, [
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/payments?status=submitted')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.order_number', $submitted->public_order_number)
        ->assertJsonPath('data.0.status', PaymentStatus::Submitted->value);
});

test('superadmin can verify and reject via admin payment endpoints', function () {
    Storage::fake('local');
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();

    $verifyOrder = createAdminPayableOrder($customer, [
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
    ]);
    PaymentProof::factory()->for($verifyOrder->payment)->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/orders/'.$verifyOrder->public_order_number.'/payment/verify')
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Verified->value)
        ->assertJsonPath('data.status', OrderStatus::Pending->value);

    $rejectOrder = createAdminPayableOrder($customer, [
        'method' => PaymentMethod::JazzCash,
        'status' => PaymentStatus::Submitted,
    ]);
    PaymentProof::factory()->for($rejectOrder->payment)->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/orders/'.$rejectOrder->public_order_number.'/payment/reject', [
            'reason' => 'Blurry image',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Rejected->value);

    expect($rejectOrder->fresh()->status)->toBe(OrderStatus::Pending);
});

test('superadmin can download private payment proof via admin endpoint', function () {
    Storage::fake('local');
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = createAdminPayableOrder($customer, [
        'method' => PaymentMethod::EasyPaisa,
        'status' => PaymentStatus::Submitted,
    ]);

    Storage::disk('local')->put('payment-proofs/admin-demo.jpg', 'fake-bytes');
    $proof = PaymentProof::factory()->for($order->payment)->create([
        'disk' => 'local',
        'path' => 'payment-proofs/admin-demo.jpg',
        'original_filename' => 'admin-demo.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    $this->actingAs($admin)
        ->get('/api/v1/admin/orders/'.$order->public_order_number.'/payment/proof/'.$proof->id)
        ->assertSuccessful();

    expect(Storage::disk('public')->exists($proof->path))->toBeFalse();
});

test('customer cannot access admin payment queue or proof download', function () {
    Storage::fake('local');
    $customer = User::factory()->create();
    $order = createAdminPayableOrder($customer, [
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
    ]);
    Storage::disk('local')->put('payment-proofs/nope.jpg', 'x');
    $proof = PaymentProof::factory()->for($order->payment)->create([
        'disk' => 'local',
        'path' => 'payment-proofs/nope.jpg',
    ]);

    $this->actingAs($customer)
        ->getJson('/api/v1/admin/payments')
        ->assertForbidden();

    $this->actingAs($customer)
        ->get('/api/v1/admin/orders/'.$order->public_order_number.'/payment/proof/'.$proof->id)
        ->assertForbidden();
});
