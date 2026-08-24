<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderConfirmed;
use App\Mail\Orders\OrderConfirmedMail;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddOn;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Support\BrandingSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('pending checkout does not send an order confirmation email', function () {
    Mail::fake();

    $user = User::factory()->create();
    $branch = Branch::factory()->create(['is_active' => true]);
    $order = Order::factory()->for($user)->for($branch)->create([
        'status' => OrderStatus::Pending,
    ]);

    expect($order->status)->toBe(OrderStatus::Pending);

    Mail::assertNothingQueued();
    Mail::assertNothingSent();
});

test('super admin confirmation dispatches order confirmed event once', function () {
    $user = User::factory()->create(['name' => 'Muhammad Ali', 'email' => 'customer@example.com']);
    $admin = User::factory()->superAdmin()->create();
    $branch = Branch::factory()->create(['name' => 'Pebbles DHA', 'is_active' => true]);
    $order = Order::factory()->for($user)->for($branch)->create([
        'status' => OrderStatus::Pending,
        'estimated_minutes' => 30,
    ]);

    Event::fake([OrderConfirmed::class]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$order->public_order_number.'/status', [
            'status' => OrderStatus::Confirmed->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Confirmed->value);

    Event::assertDispatchedTimes(OrderConfirmed::class, 1);
    Event::assertDispatched(OrderConfirmed::class, function (OrderConfirmed $event) use ($order): bool {
        return $event->order->is($order);
    });
});

test('manager confirmation queues exactly one confirmation email', function () {
    Mail::fake();

    $branch = Branch::factory()->create(['name' => 'Pebbles Gulberg', 'is_active' => true]);
    $customer = User::factory()->create(['name' => 'Ayesha Khan', 'email' => 'ayesha@example.com']);
    $manager = User::factory()->manager($branch)->create();
    $order = Order::factory()->for($customer)->for($branch)->takeaway()->create([
        'status' => OrderStatus::Pending,
        'estimated_minutes' => 25,
        'grand_total' => '850.00',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_name' => 'Chocolate Shake',
        'variant_name' => null,
        'quantity' => 2,
        'line_total' => '700.00',
    ]);

    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
        'amount' => '850.00',
    ]);

    $this->actingAs($manager)
        ->patchJson('/api/v1/admin/orders/'.$order->public_order_number.'/status', [
            'status' => OrderStatus::Confirmed->value,
        ])
        ->assertSuccessful();

    Mail::assertQueued(OrderConfirmedMail::class, function (OrderConfirmedMail $mail) use ($order, $customer): bool {
        return $mail->order->is($order)
            && $mail->hasTo($customer->email);
    });

    Mail::assertQueued(OrderConfirmedMail::class, 1);
});

test('confirmation email is not queued again when order is already confirmed', function () {
    Mail::fake();

    $customer = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Pending,
        'estimated_minutes' => 30,
    ]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$order->public_order_number.'/status', [
            'status' => OrderStatus::Confirmed->value,
        ])
        ->assertSuccessful();

    Mail::assertQueued(OrderConfirmedMail::class, 1);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$order->public_order_number.'/status', [
            'status' => OrderStatus::Confirmed->value,
        ])
        ->assertUnprocessable();

    Mail::assertQueued(OrderConfirmedMail::class, 1);
});

test('invalid customer email does not break order confirmation', function () {
    Mail::fake();

    $customer = User::factory()->create();
    $customer->forceFill(['email' => 'not-an-email'])->saveQuietly();

    $admin = User::factory()->superAdmin()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/orders/'.$order->public_order_number.'/status', [
            'status' => OrderStatus::Confirmed->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Confirmed->value);

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);

    Mail::assertNothingQueued();
});

test('order confirmation email content includes branch items eta and track url', function () {
    Storage::fake('public');
    Setting::setValue('contact_email', 'hello@pebbles.test', 'string');
    Setting::setValue('contact_phone', '+92 300 1234567', 'string');
    BrandingSettings::storeLogo(UploadedFile::fake()->image('logo.png'));

    $branch = Branch::factory()->create([
        'name' => 'Pebbles DHA',
        'address' => 'DHA Phase 6, Karachi',
        'is_active' => true,
    ]);
    $customer = User::factory()->create(['name' => 'Muhammad Ali']);
    $order = Order::factory()->for($customer)->for($branch)->create([
        'status' => OrderStatus::Confirmed,
        'confirmed_at' => now(),
        'type' => OrderType::Delivery,
        'estimated_minutes' => 30,
        'subtotal' => '980.00',
        'discount_amount' => '50.00',
        'delivery_fee' => '100.00',
        'grand_total' => '1030.00',
        'delivery_recipient_name' => 'Muhammad Ali',
        'delivery_line1' => 'Street 12',
        'delivery_area' => 'DHA Phase 6',
        'delivery_city' => 'Karachi',
        'delivery_phone' => '03001234567',
    ]);

    $item = OrderItem::factory()->for($order)->create([
        'product_name' => 'Chocolate Ice Cream',
        'variant_name' => 'Large',
        'quantity' => 1,
        'line_total' => '400.00',
    ]);

    OrderItemAddOn::factory()->for($item, 'orderItem')->create([
        'add_on_name' => 'Oreo Topping',
        'quantity' => 1,
        'line_total' => '80.00',
    ]);

    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::JazzCash,
        'status' => PaymentStatus::Submitted,
        'amount' => '1030.00',
    ]);

    $mailable = new OrderConfirmedMail($order->fresh(['items.addOns', 'payment', 'branch', 'user']));

    $mailable->assertHasSubject('Your Pebbles Order #'.$order->public_order_number.' Is Confirmed!');
    $mailable->assertSeeInHtml('Your Order Is Confirmed!');
    $mailable->assertSeeInHtml('Hi Muhammad,');
    $mailable->assertSeeInHtml('#'.$order->public_order_number);
    $mailable->assertSeeInHtml('Pebbles DHA');
    $mailable->assertSeeInHtml('Delivery');
    $mailable->assertSeeInHtml('About 30 minutes');
    $mailable->assertSeeInHtml('Chocolate Ice Cream (Large)');
    $mailable->assertSeeInHtml('+ Oreo Topping');
    $mailable->assertSeeInHtml('Rs. 1,030');
    $mailable->assertSeeInHtml('JazzCash');
    $mailable->assertSeeInHtml('Pending Verification');
    $mailable->assertSeeInHtml('Street 12');
    $mailable->assertSeeInHtml('Track Order');
    $mailable->assertSeeInHtml(url('/orders/'.$order->public_order_number.'/track'));
    $mailable->assertSeeInHtml(BrandingSettings::publicPayload()['logo']['url']);
    $mailable->assertDontSeeInHtml('27 min remaining');
});

test('takeaway confirmation email shows pickup location without delivery address', function () {
    $branch = Branch::factory()->create([
        'name' => 'Pebbles Gulberg',
        'address' => 'Main Boulevard, Gulberg',
        'is_active' => true,
    ]);
    $customer = User::factory()->create(['name' => 'Sara']);
    $order = Order::factory()->for($customer)->for($branch)->takeaway()->create([
        'status' => OrderStatus::Confirmed,
        'confirmed_at' => now(),
        'estimated_minutes' => 20,
        'grand_total' => '500.00',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_name' => 'Vanilla Scoop',
        'quantity' => 1,
        'line_total' => '500.00',
    ]);

    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
        'amount' => '500.00',
    ]);

    $mailable = new OrderConfirmedMail($order->fresh(['items.addOns', 'payment', 'branch', 'user']));

    $mailable->assertSeeInHtml('Takeaway');
    $mailable->assertSeeInHtml('Pickup location');
    $mailable->assertSeeInHtml('Pebbles Gulberg');
    $mailable->assertSeeInHtml('Main Boulevard, Gulberg');
    $mailable->assertSeeInHtml('COD');
    $mailable->assertDontSeeInHtml('Delivery address');
});

test('customer cancel before confirmation does not send confirmation email', function () {
    Mail::fake();
    Setting::setValue('customer_order_cancellation_enabled', true, 'boolean');

    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Pending,
    ]);

    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
        'amount' => $order->grand_total,
    ]);

    $this->actingAs($customer)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/cancel', [
            'cancellation_reason' => 'ordered_by_mistake',
        ])
        ->assertSuccessful();

    Mail::assertNothingQueued();
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});
