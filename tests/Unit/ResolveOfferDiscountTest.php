<?php

use App\Actions\ResolveOfferDiscount;
use App\Enums\OfferType;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Support\Collection;

function offerDiscountLines(array $lines): Collection
{
    return collect($lines);
}

test('entire_store offer discounts every line', function () {
    Offer::factory()->entireStore()->create([
        'title' => 'Store Wide',
        'type' => OfferType::Discounts,
        'offer_value' => '10% off',
        'is_active' => true,
    ]);

    $result = app(ResolveOfferDiscount::class)->handle(offerDiscountLines([
        ['product_id' => 1, 'category_id' => 10, 'line_total' => 500],
        ['product_id' => 2, 'category_id' => 20, 'line_total' => 500],
    ]));

    expect($result['discount_amount'])->toBe('100.00')
        ->and($result['offer_title'])->toBe('Store Wide');
});

test('category-scoped offer discounts only matching category lines', function () {
    $burgers = Category::factory()->create(['is_active' => true]);
    $drinks = Category::factory()->create(['is_active' => true]);

    $offer = Offer::factory()->forCategories()->create([
        'title' => 'Burger Ten',
        'type' => OfferType::Discounts,
        'offer_value' => '10% off',
        'is_active' => true,
    ]);
    $offer->categories()->sync([$burgers->id]);

    $result = app(ResolveOfferDiscount::class)->handle(offerDiscountLines([
        ['product_id' => 1, 'category_id' => $burgers->id, 'line_total' => 500],
        ['product_id' => 2, 'category_id' => $drinks->id, 'line_total' => 500],
    ]));

    expect($result['discount_amount'])->toBe('50.00')
        ->and($result['offer_title'])->toBe('Burger Ten');
});

test('product-scoped offer discounts only matching products', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $onSale = Product::factory()->for($category)->create(['base_price' => '400.00']);
    $fullPrice = Product::factory()->for($category)->create(['base_price' => '600.00']);

    $offer = Offer::factory()->forProducts()->create([
        'title' => 'Single Item',
        'type' => OfferType::Discounts,
        'offer_value' => 'Rs 100 off',
        'is_active' => true,
    ]);
    $offer->products()->sync([$onSale->id]);

    $result = app(ResolveOfferDiscount::class)->handle(offerDiscountLines([
        ['product_id' => $onSale->id, 'category_id' => $category->id, 'line_total' => 400],
        ['product_id' => $fullPrice->id, 'category_id' => $category->id, 'line_total' => 600],
    ]));

    expect($result['discount_amount'])->toBe('100.00')
        ->and($result['offer_id'])->toBe($offer->id)
        ->and($result['offer_title'])->toBe('Single Item');
});

test('maximum_discount_amount caps percentage discounts', function () {
    Offer::factory()->entireStore()->create([
        'title' => 'Capped',
        'type' => OfferType::Discounts,
        'offer_value' => '50% off',
        'maximum_discount_amount' => '75.00',
        'is_active' => true,
    ]);

    $result = app(ResolveOfferDiscount::class)->handle(offerDiscountLines([
        ['product_id' => 1, 'category_id' => 1, 'line_total' => 1000],
    ]));

    expect($result['discount_amount'])->toBe('75.00');
});

test('inactive offers do not apply', function () {
    Offer::factory()->entireStore()->inactive()->create([
        'title' => 'Sleeping',
        'type' => OfferType::Discounts,
        'offer_value' => '20% off',
    ]);

    $result = app(ResolveOfferDiscount::class)->handle(offerDiscountLines([
        ['product_id' => 1, 'category_id' => 1, 'line_total' => 1000],
    ]));

    expect($result['discount_amount'])->toBe('0.00')
        ->and($result['offer_id'])->toBeNull()
        ->and($result['offer_title'])->toBeNull();
});
