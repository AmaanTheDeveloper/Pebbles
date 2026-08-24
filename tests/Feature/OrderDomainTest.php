<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddOn;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('product prices use decimal columns', function () {
    $column = Schema::getColumns('products');
    $basePrice = collect($column)->firstWhere('name', 'base_price');
    $type = strtolower((string) ($basePrice['type'] ?? $basePrice['type_name'] ?? ''));

    expect($basePrice)->not->toBeNull()
        ->and($type)->toMatch('/decimal|numeric/');
});

test('order items preserve historical prices and names', function () {
    $product = Product::factory()->create([
        'name' => 'Live Product Name',
        'base_price' => '100.00',
    ]);

    $order = Order::factory()->create();
    $item = OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'product_name' => 'Historical Brownie',
        'variant_name' => 'Large',
        'unit_price' => '699.00',
        'quantity' => 2,
        'line_total' => '1398.00',
    ]);

    OrderItemAddOn::factory()->for($item, 'orderItem')->create([
        'add_on_name' => 'Extra Chocolate',
        'unit_price' => '80.00',
        'quantity' => 1,
        'line_total' => '80.00',
    ]);

    $product->update(['name' => 'Renamed Product', 'base_price' => '999.00']);

    $item->refresh();

    expect($item->product_name)->toBe('Historical Brownie')
        ->and($item->variant_name)->toBe('Large')
        ->and($item->unit_price)->toBe('699.00')
        ->and($item->line_total)->toBe('1398.00')
        ->and($item->addOns->first()->add_on_name)->toBe('Extra Chocolate');
});

test('public order numbers are unique and auto-generated', function () {
    $first = Order::factory()->create();
    $second = Order::factory()->create();

    expect($first->public_order_number)->toStartWith('PB-')
        ->and($second->public_order_number)->toStartWith('PB-')
        ->and($first->public_order_number)->not->toBe($second->public_order_number);

    expect(Order::query()->where('public_order_number', $first->public_order_number)->count())->toBe(1);
});

test('order types and statuses use controlled enums', function () {
    $order = Order::factory()->takeaway()->create([
        'status' => OrderStatus::Preparing,
    ]);

    expect($order->type)->toBe(OrderType::Takeaway)
        ->and($order->status)->toBe(OrderStatus::Preparing);
});

test('cancellation and modification are only available while order is pending', function () {
    Setting::setValue('customer_order_cancellation_enabled', true, 'boolean');

    $pending = Order::factory()->create([
        'created_at' => now(),
        'status' => OrderStatus::Pending,
    ]);
    $confirmed = Order::factory()->create([
        'created_at' => now(),
        'status' => OrderStatus::Confirmed,
        'confirmed_at' => now(),
    ]);
    $preparing = Order::factory()->create([
        'created_at' => now(),
        'status' => OrderStatus::Preparing,
        'confirmed_at' => now(),
    ]);

    expect($pending->isCancellable())->toBeTrue()
        ->and($pending->isModifiable())->toBeTrue()
        ->and($confirmed->isCancellable())->toBeFalse()
        ->and($confirmed->isModifiable())->toBeFalse()
        ->and($preparing->isCancellable())->toBeFalse()
        ->and($preparing->isModifiable())->toBeFalse();
});

test('registration still produces the user role', function () {
    $this->withHeader('Origin', 'http://localhost')
        ->postJson('/api/register', [
            'name' => 'Phase Two User',
            'email' => 'phase2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertCreated()
        ->assertJsonPath('data.role', UserRole::User->value);

    expect(User::query()->where('email', 'phase2@example.com')->first()?->role)->toBe(UserRole::User);
});
