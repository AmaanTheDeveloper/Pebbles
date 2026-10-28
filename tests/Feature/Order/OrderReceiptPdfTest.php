<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddOn;
use App\Models\Payment;
use App\Models\User;
use App\Support\BrandingSettings;
use App\Support\Orders\OrderReceiptData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function createReceiptOrder(User $customer, Branch $branch, array $orderAttributes = []): Order
{
    $order = Order::factory()->for($customer)->for($branch)->create(array_merge([
        'status' => OrderStatus::Confirmed,
        'type' => OrderType::Delivery,
        'subtotal' => '900.00',
        'discount_amount' => '50.00',
        'delivery_fee' => '100.00',
        'tax_amount' => '0.00',
        'grand_total' => '950.00',
        'delivery_zone_name' => 'DHA Phase 7',
        'delivery_recipient_name' => 'Ali Customer',
        'delivery_phone' => '03001234567',
        'delivery_line1' => 'Street 12',
        'delivery_area' => 'DHA Phase 7',
        'delivery_city' => 'Karachi',
        'confirmed_at' => now(),
    ], $orderAttributes));

    $item = OrderItem::factory()->for($order)->create([
        'product_name' => 'Chocolate Ice Cream',
        'variant_name' => 'Double Scoop',
        'unit_price' => '300.00',
        'quantity' => 2,
        'line_total' => '600.00',
    ]);

    OrderItemAddOn::factory()->for($item)->create([
        'add_on_name' => 'Oreo Topping',
        'unit_price' => '50.00',
        'quantity' => 1,
        'line_total' => '50.00',
    ]);

    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::JazzCash,
        'status' => PaymentStatus::Submitted,
        'amount' => '950.00',
    ]);

    return $order->fresh(['items.addOns', 'payment', 'branch', 'user']);
}

test('customer can download receipt for their own order', function () {
    $customer = User::factory()->create(['name' => 'Ali Customer', 'phone' => '03001112222']);
    $branch = Branch::factory()->create(['name' => 'Pebbles DHA', 'is_active' => true]);
    $order = createReceiptOrder($customer, $branch);

    $response = $this->actingAs($customer)
        ->get('/api/v1/orders/'.$order->public_order_number.'/receipt');

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->headers->get('content-disposition'))->toContain('Pebbles-Order-')
        ->and(strlen($response->getContent()))->toBeGreaterThan(500);
});

test('customer cannot download another customers receipt', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $branch = Branch::factory()->create(['is_active' => true]);
    $order = createReceiptOrder($owner, $branch);

    $this->actingAs($intruder)
        ->get('/api/v1/orders/'.$order->public_order_number.'/receipt')
        ->assertForbidden();
});

test('manager can download receipt for assigned branch only', function () {
    $dha = Branch::factory()->create(['name' => 'Pebbles DHA', 'is_active' => true]);
    $gulberg = Branch::factory()->create(['name' => 'Pebbles Gulberg', 'is_active' => true]);
    $manager = User::factory()->manager($dha)->create();
    $customer = User::factory()->create();

    $dhaOrder = createReceiptOrder($customer, $dha);
    $gulbergOrder = createReceiptOrder($customer, $gulberg);

    $this->actingAs($manager)
        ->get('/api/v1/orders/'.$dhaOrder->public_order_number.'/receipt')
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get('/api/v1/orders/'.$gulbergOrder->public_order_number.'/receipt')
        ->assertForbidden();
});

test('super admin can download receipts across branches', function () {
    $admin = User::factory()->superAdmin()->create();
    $dha = Branch::factory()->create(['is_active' => true]);
    $gulberg = Branch::factory()->create(['is_active' => true]);
    $customer = User::factory()->create();

    $dhaOrder = createReceiptOrder($customer, $dha);
    $gulbergOrder = createReceiptOrder($customer, $gulberg);

    $this->actingAs($admin)
        ->get('/api/v1/orders/'.$dhaOrder->public_order_number.'/receipt')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get('/api/v1/orders/'.$gulbergOrder->public_order_number.'/receipt')
        ->assertSuccessful();
});

test('receipt uses historical stored order values and logo fallback safely', function () {
    Storage::fake('public');

    $customer = User::factory()->create(['name' => 'Historical Customer']);
    $branch = Branch::factory()->create([
        'name' => 'Pebbles Clifton',
        'address' => 'Clifton Block 5',
        'is_active' => true,
    ]);
    $order = createReceiptOrder($customer, $branch, [
        'status' => OrderStatus::Cancelled,
        'cancelled_at' => now(),
        'cancellation_reason' => 'Cancelled by customer.',
        'discount_amount' => '150.00',
        'grand_total' => '850.00',
    ]);

    $payload = OrderReceiptData::for($order);

    expect($payload['order_number'])->toBe($order->public_order_number)
        ->and($payload['branch_name'])->toBe('Pebbles Clifton')
        ->and($payload['has_discount'])->toBeTrue()
        ->and($payload['discount_amount'])->toBe('Rs. 150')
        ->and($payload['grand_total'])->toBe('Rs. 850')
        ->and($payload['has_delivery_fee'])->toBeTrue()
        ->and($payload['payment_method_label'])->toBe('JazzCash')
        ->and($payload['payment_status_label'])->toBe('Pending Verification')
        ->and($payload['is_cancelled'])->toBeTrue()
        ->and($payload['items'][0]['name'])->toContain('Chocolate Ice Cream')
        ->and($payload['items'][0]['name'])->toContain('Double Scoop')
        ->and($payload['items'][0]['add_ons'][0]['name'])->toBe('Oreo Topping')
        ->and($payload['has_logo'])->toBeFalse();
});

test('receipt includes logo when branding logo exists on disk and gd is available', function () {
    Storage::fake('public');

    BrandingSettings::storeLogo(UploadedFile::fake()->image('pebbles-logo.png', 200, 80));

    $customer = User::factory()->create();
    $branch = Branch::factory()->create(['is_active' => true]);
    $order = createReceiptOrder($customer, $branch);

    $payload = OrderReceiptData::for($order);

    if (extension_loaded('gd') || extension_loaded('imagick')) {
        expect($payload['has_logo'])->toBeTrue()
            ->and($payload['logo_src'])->toStartWith('data:image/');
    } else {
        expect($payload['has_logo'])->toBeFalse()
            ->and($payload['logo_src'])->toBeNull();
    }
});

test('takeaway receipt omits delivery fee section', function () {
    $customer = User::factory()->create();
    $branch = Branch::factory()->create(['name' => 'Pebbles DHA', 'is_active' => true]);
    $order = createReceiptOrder($customer, $branch, [
        'type' => OrderType::Takeaway,
        'delivery_fee' => '0.00',
        'grand_total' => '850.00',
        'delivery_line1' => null,
        'delivery_city' => null,
        'pickup_notes' => 'Call on arrival',
    ]);

    $payload = OrderReceiptData::for($order);

    expect($payload['is_takeaway'])->toBeTrue()
        ->and($payload['has_delivery_fee'])->toBeFalse()
        ->and($payload['delivery_address_lines'])->toBe([])
        ->and($payload['pickup_notes'])->toBe('Call on arrival');
});

test('guest cannot download order receipt', function () {
    $customer = User::factory()->create();
    $branch = Branch::factory()->create(['is_active' => true]);
    $order = createReceiptOrder($customer, $branch);

    $this->getJson('/api/v1/orders/'.$order->public_order_number.'/receipt')
        ->assertUnauthorized();
});
