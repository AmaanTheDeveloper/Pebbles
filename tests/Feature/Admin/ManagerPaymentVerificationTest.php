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

function createBranchPayableOrder(User $customer, Branch $branch, array $paymentOverrides = []): Order
{
    $order = Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Pending,
        'subtotal' => '1000.00',
        'grand_total' => '1000.00',
        'delivery_fee' => '0.00',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_name' => 'Branch Shake',
        'unit_price' => '1000.00',
        'quantity' => 1,
        'line_total' => '1000.00',
    ]);

    Payment::factory()->for($order)->create(array_merge([
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
        'amount' => '1000.00',
    ], $paymentOverrides));

    return $order->fresh(['payment']);
}

test('manager can verify payment for assigned branch', function () {
    Storage::fake('local');
    $branch = Branch::factory()->create(['is_active' => true]);
    $manager = User::factory()->manager($branch)->create();
    $customer = User::factory()->create();
    $order = createBranchPayableOrder($customer, $branch);
    PaymentProof::factory()->for($order->payment)->create();

    $this->actingAs($manager)
        ->postJson('/api/v1/admin/orders/'.$order->public_order_number.'/payment/verify')
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Verified->value);

    expect($order->fresh()->payment->verified_by)->toBe($manager->id);
});

test('manager can reject payment for assigned branch', function () {
    Storage::fake('local');
    $branch = Branch::factory()->create(['is_active' => true]);
    $manager = User::factory()->manager($branch)->create();
    $customer = User::factory()->create();
    $order = createBranchPayableOrder($customer, $branch, [
        'method' => PaymentMethod::JazzCash,
    ]);
    PaymentProof::factory()->for($order->payment)->create();

    $this->actingAs($manager)
        ->postJson('/api/v1/admin/orders/'.$order->public_order_number.'/payment/reject', [
            'reason' => 'Unclear receipt',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Rejected->value);
});

test('manager cannot verify payment for another branch', function () {
    $branchA = Branch::factory()->create(['is_active' => true]);
    $branchB = Branch::factory()->create(['is_active' => true]);
    $manager = User::factory()->manager($branchA)->create();
    $customer = User::factory()->create();
    $order = createBranchPayableOrder($customer, $branchB);
    PaymentProof::factory()->for($order->payment)->create();

    $this->actingAs($manager)
        ->postJson('/api/v1/admin/orders/'.$order->public_order_number.'/payment/verify')
        ->assertForbidden();

    expect($order->fresh()->payment->status)->toBe(PaymentStatus::Submitted);
});

test('manager cannot reject payment for another branch', function () {
    $branchA = Branch::factory()->create(['is_active' => true]);
    $branchB = Branch::factory()->create(['is_active' => true]);
    $manager = User::factory()->manager($branchA)->create();
    $customer = User::factory()->create();
    $order = createBranchPayableOrder($customer, $branchB);

    $this->actingAs($manager)
        ->postJson('/api/v1/admin/orders/'.$order->public_order_number.'/payment/reject', [
            'reason' => 'Nope',
        ])
        ->assertForbidden();

    expect($order->fresh()->payment->status)->toBe(PaymentStatus::Submitted);
});

test('super admin can verify payment for any branch', function () {
    Storage::fake('local');
    $branch = Branch::factory()->create(['is_active' => true]);
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = createBranchPayableOrder($customer, $branch);
    PaymentProof::factory()->for($order->payment)->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/orders/'.$order->public_order_number.'/payment/verify')
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Verified->value);
});

test('customer cannot verify or reject via admin payment endpoints', function () {
    $branch = Branch::factory()->create(['is_active' => true]);
    $customer = User::factory()->create();
    $order = createBranchPayableOrder($customer, $branch);

    $this->actingAs($customer)
        ->postJson('/api/v1/admin/orders/'.$order->public_order_number.'/payment/verify')
        ->assertForbidden();

    $this->actingAs($customer)
        ->postJson('/api/v1/admin/orders/'.$order->public_order_number.'/payment/reject')
        ->assertForbidden();
});
