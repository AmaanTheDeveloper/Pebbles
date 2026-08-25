<?php

use App\Enums\OfferScope;
use App\Enums\OfferType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Support\ProductOfferPricing;

function targetingProduct(string $price = '500.00', ?Category $category = null): Product
{
    $category ??= Category::factory()->create(['is_active' => true]);

    return Product::factory()->for($category)->create([
        'base_price' => $price,
        'is_active' => true,
        'is_available' => true,
    ]);
}

function targetingBranch(): Branch
{
    $branch = Branch::factory()->create(['is_active' => true]);
    DeliveryZone::factory()->for($branch)->create([
        'is_active' => true,
        'delivery_fee' => '100.00',
        'minimum_order_amount' => '100.00',
    ]);

    return $branch;
}

test('category-targeted offer discounts only eligible lines', function () {
    $user = User::factory()->create();
    targetingBranch();
    $burgers = Category::factory()->create(['is_active' => true, 'name' => 'Burgers']);
    $drinks = Category::factory()->create(['is_active' => true, 'name' => 'Drinks']);
    $burger = targetingProduct('500.00', $burgers);
    $drink = targetingProduct('200.00', $drinks);

    $offer = Offer::factory()->forCategories()->create([
        'title' => 'Burger Ten',
        'type' => OfferType::Discounts,
        'offer_value' => '10% off',
        'is_active' => true,
    ]);
    $offer->categories()->sync([$burgers->id]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $burger->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $drink->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', '700.00')
        ->assertJsonPath('data.offer_discount_amount', '50.00')
        ->assertJsonPath('data.total', '650.00')
        ->assertJsonPath('data.applied_offer.title', 'Burger Ten');
});

test('product-targeted offer discounts only matching products', function () {
    $user = User::factory()->create();
    targetingBranch();
    $category = Category::factory()->create(['is_active' => true]);
    $onSale = targetingProduct('400.00', $category);
    $fullPrice = targetingProduct('600.00', $category);

    $offer = Offer::factory()->forProducts()->create([
        'title' => 'Single Item Sale',
        'offer_value' => 'Rs 100 off',
        'is_active' => true,
    ]);
    $offer->products()->sync([$onSale->id]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $onSale->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $fullPrice->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', '1000.00')
        ->assertJsonPath('data.offer_discount_amount', '100.00')
        ->assertJsonPath('data.total', '900.00');
});

test('mixed cart with entire-store offer still discounts full subtotal', function () {
    $user = User::factory()->create();
    targetingBranch();
    $a = targetingProduct('300.00');
    $b = targetingProduct('200.00');

    Offer::factory()->entireStore()->create([
        'offer_value' => '10% off',
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $a->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $b->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.subtotal', '500.00')
        ->assertJsonPath('data.offer_discount_amount', '50.00')
        ->assertJsonPath('data.total', '450.00');
});

test('inactive and expired targeted offers do not discount', function () {
    $user = User::factory()->create();
    targetingBranch();
    $product = targetingProduct('500.00');

    $inactive = Offer::factory()->forProducts()->inactive()->create([
        'offer_value' => '50% off',
    ]);
    $inactive->products()->sync([$product->id]);

    $expired = Offer::factory()->forProducts()->expired()->create([
        'offer_value' => 'Rs 200 off',
    ]);
    $expired->products()->sync([$product->id]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.offer_discount_amount', '0.00')
        ->assertJsonPath('data.total', '500.00');
});

test('maximum discount amount caps percentage offers', function () {
    $user = User::factory()->create();
    targetingBranch();
    $product = targetingProduct('1000.00');

    Offer::factory()->entireStore()->create([
        'offer_value' => '50% off',
        'maximum_discount_amount' => '100.00',
        'is_active' => true,
    ]);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.offer_discount_amount', '100.00')
        ->assertJsonPath('data.total', '900.00');
});

test('catalog exposes promotional price only for products the offer targets', function () {
    $burgers = Category::factory()->create(['is_active' => true]);
    $drinks = Category::factory()->create(['is_active' => true]);
    $burger = targetingProduct('500.00', $burgers);
    $drink = targetingProduct('200.00', $drinks);

    $offer = Offer::factory()->forCategories()->create([
        'offer_value' => '10% off',
        'is_active' => true,
    ]);
    $offer->categories()->sync([$burgers->id]);
    ProductOfferPricing::flush();

    $this->getJson('/api/v1/catalog/products/'.$burger->slug)
        ->assertSuccessful()
        ->assertJsonPath('data.compare_at_price', '500.00')
        ->assertJsonPath('data.promotional_price', '450.00');

    $this->getJson('/api/v1/catalog/products/'.$drink->slug)
        ->assertSuccessful()
        ->assertJsonPath('data.compare_at_price', null)
        ->assertJsonPath('data.promotional_price', null);
});

test('admin can create a category-scoped offer with targets', function () {
    $admin = User::factory()->superAdmin()->create();
    $category = Category::factory()->create(['is_active' => true]);

    $created = $this->actingAs($admin)
        ->postJson('/api/v1/admin/offers', [
            'title' => 'Category Deal',
            'type' => OfferType::Discounts->value,
            'offer_value' => '15% off',
            'scope' => OfferScope::Categories->value,
            'category_ids' => [$category->id],
            'maximum_discount_amount' => '250.00',
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.scope', OfferScope::Categories->value)
        ->assertJsonPath('data.maximum_discount_amount', '250.00')
        ->assertJsonPath('data.category_ids.0', $category->id)
        ->json('data');

    $offer = Offer::query()->findOrFail($created['id']);

    expect($offer->scope)->toBe(OfferScope::Categories)
        ->and($offer->categories()->pluck('categories.id')->all())->toBe([$category->id]);
});

test('category-scoped offer requires at least one category', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/offers', [
            'title' => 'Missing Categories',
            'type' => OfferType::Discounts->value,
            'scope' => OfferScope::Categories->value,
            'category_ids' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_ids']);
});
