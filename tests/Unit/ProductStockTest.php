<?php

use App\Enums\StockStatus;
use App\Models\Product;
use App\Support\ProductStock;
use Illuminate\Validation\ValidationException;

test('null stock_quantity means unlimited and in stock', function () {
    $product = new Product([
        'name' => 'Unlimited Scoop',
        'stock_quantity' => null,
    ]);

    expect($product->isInStock())->toBeTrue()
        ->and($product->stockStatus())->toBe(StockStatus::Unlimited)
        ->and($product->availableQuantity())->toBeNull();
});

test('positive stock_quantity is in stock', function () {
    $product = new Product([
        'name' => 'Tracked Cone',
        'stock_quantity' => 8,
        'low_stock_threshold' => 5,
    ]);

    expect($product->isInStock())->toBeTrue()
        ->and($product->stockStatus())->toBe(StockStatus::InStock);
});

test('zero stock_quantity is out of stock', function () {
    $product = new Product([
        'name' => 'Sold Out',
        'stock_quantity' => 0,
    ]);

    expect($product->isInStock())->toBeFalse()
        ->and($product->stockStatus())->toBe(StockStatus::OutOfStock);
});

test('assertSufficientStock allows unlimited and rejects oversell', function () {
    $unlimited = new Product(['name' => 'Unlimited', 'stock_quantity' => null]);
    $tracked = Product::factory()->withStock(2)->create(['name' => 'Tracked']);

    $unlimited->assertSufficientStock(99);

    expect(fn () => $tracked->assertSufficientStock(3))
        ->toThrow(ValidationException::class);
});

test('ProductStock deduct and restore skip unlimited inventory', function () {
    $unlimited = Product::factory()->create(['stock_quantity' => null]);
    $tracked = Product::factory()->withStock(5)->create();

    ProductStock::deduct([
        $unlimited->id => 3,
        $tracked->id => 2,
    ]);

    expect($unlimited->fresh()->stock_quantity)->toBeNull()
        ->and($tracked->fresh()->stock_quantity)->toBe(3);

    ProductStock::restore([
        $tracked->id => 2,
    ]);

    expect($tracked->fresh()->stock_quantity)->toBe(5);
});
