<?php

use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Support\Orders\OrderReceiptData;

test('cod order payment remains pending during processing and automatically becomes verified when order is completed', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create(['name' => 'John Doe']);
    $branch = Branch::factory()->create(['name' => 'Main Branch', 'is_active' => true]);

    $order = Order::factory()->for($customer)->for($branch)->create([
        'status' => OrderStatus::Pending,
        'type' => OrderType::Delivery,
        'subtotal' => '1000.00',
        'grand_total' => '1100.00',
        'delivery_fee' => '100.00',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_name' => 'Chocolate Shake',
        'unit_price' => '500.00',
        'quantity' => 2,
        'line_total' => '1000.00',
    ]);

    $payment = Payment::factory()->for($order)->create([
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
        'amount' => '1100.00',
    ]);

    expect($payment->status)->toBe(PaymentStatus::Pending);

    $action = app(UpdateOrderStatus::class);

    // Transition: Pending -> Confirmed
    $order = $action->handle($order, OrderStatus::Confirmed, $admin);
    expect($order->fresh()->payment->status)->toBe(PaymentStatus::Pending);

    // Transition: Confirmed -> Preparing
    $order = $action->handle($order, OrderStatus::Preparing, $admin);
    expect($order->fresh()->payment->status)->toBe(PaymentStatus::Pending);

    // Transition: Preparing -> Ready
    $order = $action->handle($order, OrderStatus::Ready, $admin);
    expect($order->fresh()->payment->status)->toBe(PaymentStatus::Pending);

    // Transition: Ready -> OutForDelivery
    $order = $action->handle($order, OrderStatus::OutForDelivery, $admin);
    expect($order->fresh()->payment->status)->toBe(PaymentStatus::Pending);

    // Transition: OutForDelivery -> Completed (Delivered)
    $order = $action->handle($order, OrderStatus::Completed, $admin);
    $freshPayment = $order->fresh()->payment;

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($freshPayment->status)->toBe(PaymentStatus::Verified)
        ->and($freshPayment->paid_at)->not->toBeNull()
        ->and($freshPayment->verified_at)->not->toBeNull()
        ->and($freshPayment->verified_by)->toBe($admin->id);

    // Verify Customer Order API shows Verified payment status
    $userOrderResponse = $this->actingAs($customer)
        ->getJson('/api/v1/orders/'.$order->public_order_number)
        ->assertSuccessful();

    expect($userOrderResponse->json('data.payment.status'))->toBe(PaymentStatus::Verified->value);

    // Verify Receipt Data payload shows Paid
    $receiptData = OrderReceiptData::for($order->fresh(['items.addOns', 'payment', 'branch', 'user']));
    expect($receiptData['payment_status_label'])->toBe('Paid');

    // Verify Customer Receipt Download
    $customerReceiptResponse = $this->actingAs($customer)
        ->get('/api/v1/orders/'.$order->public_order_number.'/receipt');

    $customerReceiptResponse->assertSuccessful();
    expect($customerReceiptResponse->headers->get('content-type'))->toContain('application/pdf')
        ->and(strlen($customerReceiptResponse->getContent()))->toBeGreaterThan(500);

    // Verify Admin Receipt Download
    $adminReceiptResponse = $this->actingAs($admin)
        ->get('/api/v1/orders/'.$order->public_order_number.'/receipt');

    $adminReceiptResponse->assertSuccessful();
    expect($adminReceiptResponse->headers->get('content-type'))->toContain('application/pdf')
        ->and(strlen($adminReceiptResponse->getContent()))->toBeGreaterThan(500);
});
