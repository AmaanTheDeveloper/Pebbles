<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\CarbonImmutable;

test('analytics preserve historical product and delivery fee snapshots after catalog changes', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'UTC'));

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $branch = Branch::factory()->create(['name' => 'Original Branch']);
    $category = Category::factory()->create(['name' => 'Original Category']);
    $product = Product::factory()->for($category)->create([
        'name' => 'Live Product',
        'base_price' => '2000.00',
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'name' => 'Large',
        'price' => '2000.00',
    ]);
    $zone = DeliveryZone::factory()->for($branch)->create([
        'name' => 'Live Zone',
        'delivery_fee' => '250.00',
    ]);

    $order = Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Completed,
        'type' => OrderType::Delivery,
        'delivery_zone_id' => $zone->id,
        'delivery_zone_name' => 'Historical Zone',
        'delivery_fee' => '100.00',
        'grand_total' => '1100.00',
        'created_at' => '2026-08-10 10:00:00',
    ]);
    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => 'Historical Product',
        'variant_name' => 'Historical Large',
        'unit_price' => '1000.00',
        'quantity' => 1,
        'line_total' => '1000.00',
    ]);
    Payment::factory()->for($order)->create(['status' => PaymentStatus::Verified, 'amount' => '1100.00']);

    $product->update(['name' => 'Renamed Product', 'base_price' => '9999.00']);
    $variant->update(['name' => 'Renamed Variant', 'price' => '9999.00']);
    $zone->update(['name' => 'Renamed Zone', 'delivery_fee' => '999.00']);
    $branch->update(['name' => 'Renamed Branch']);
    $category->update(['name' => 'Renamed Category']);

    $products = $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/products?preset=custom&from=2026-08-01&to=2026-08-15')
        ->assertSuccessful()
        ->json('data');

    expect(collect($products['products'])->pluck('product'))->toContain('Historical Product')
        ->and(collect($products['products'])->pluck('product'))->not->toContain('Renamed Product')
        ->and(collect($products['variants'])->first()['variant'])->toBe('Historical Large')
        ->and(collect($products['variants'])->first()['revenue'])->toBe('1000.00');

    $zones = $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/delivery-zones?preset=custom&from=2026-08-01&to=2026-08-15')
        ->assertSuccessful()
        ->json('data.zones.0');

    expect($zones['zone'])->toBe('Historical Zone')
        ->and($zones['delivery_fees'])->toBe('100.00')
        ->and($zones['revenue'])->toBe('1100.00');

    CarbonImmutable::setTestNow();
});
