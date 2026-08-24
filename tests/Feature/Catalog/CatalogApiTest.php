<?php

use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;

test('active categories are listed and inactive categories are hidden', function () {
    $active = Category::factory()->create(['name' => 'Burgers', 'slug' => 'burgers', 'is_active' => true, 'sort_order' => 1]);
    Category::factory()->create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);

    $response = $this->getJson('/api/v1/catalog/categories');

    $response
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $active->slug)
        ->assertJsonMissingPath('data.0.is_active');
});

test('active products are listed and inactive or inactive-category products are hidden', function () {
    $category = Category::factory()->create(['slug' => 'mains', 'is_active' => true]);
    $inactiveCategory = Category::factory()->create(['slug' => 'inactive-cat', 'is_active' => false]);

    $visible = Product::factory()->for($category)->create(['name' => 'Visible Bowl', 'slug' => 'visible-bowl', 'is_active' => true]);
    Product::factory()->for($category)->create(['name' => 'Inactive Bowl', 'slug' => 'inactive-bowl', 'is_active' => false]);
    Product::factory()->for($inactiveCategory)->create(['name' => 'Orphan Bowl', 'slug' => 'orphan-bowl', 'is_active' => true]);

    $response = $this->getJson('/api/v1/catalog/products');

    $response
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $visible->slug)
        ->assertJsonPath('data.0.available', true);
});

test('products can be filtered by category search and featured flag', function () {
    $burgers = Category::factory()->create(['slug' => 'burgers', 'is_active' => true]);
    $drinks = Category::factory()->create(['slug' => 'drinks', 'is_active' => true]);

    Product::factory()->for($burgers)->create([
        'name' => 'Cheese Burger',
        'slug' => 'cheese-burger',
        'short_description' => 'Classic cheesy burger',
        'is_featured' => true,
    ]);
    Product::factory()->for($burgers)->create([
        'name' => 'Veggie Burger',
        'slug' => 'veggie-burger',
        'short_description' => 'Plant based',
        'is_featured' => false,
    ]);
    Product::factory()->for($drinks)->create([
        'name' => 'Lemonade',
        'slug' => 'lemonade',
        'short_description' => 'Fresh lemon drink',
        'is_featured' => true,
    ]);

    $this->getJson('/api/v1/catalog/products?category=burgers')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/v1/catalog/products?search=cheesy')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'cheese-burger');

    $this->getJson('/api/v1/catalog/products?featured=1')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

test('invalid category filter returns validation error', function () {
    $this->getJson('/api/v1/catalog/products?category=does-not-exist')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category']);
});

test('product detail includes images variants and nested add-on groups', function () {
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($category)->create([
        'slug' => 'loaded-fries',
        'base_price' => '500.00',
        'is_active' => true,
    ]);

    ProductImage::factory()->for($product)->primary()->create(['path' => 'products/loaded.jpg']);
    ProductVariant::factory()->for($product)->create(['name' => 'Regular', 'price' => '450.00', 'is_active' => true]);
    ProductVariant::factory()->for($product)->create(['name' => 'Hidden Size', 'price' => '100.00', 'is_active' => false]);

    $group = AddOnGroup::factory()->create(['name' => 'Ice Cream Toppings', 'slug' => 'ice-cream-toppings', 'is_active' => true]);
    $inactiveGroup = AddOnGroup::factory()->create(['name' => 'Hidden Group', 'slug' => 'hidden-group', 'is_active' => false]);
    AddOn::factory()->create(['add_on_group_id' => $group->id, 'name' => 'Garlic Mayo', 'price' => '50.00', 'is_active' => true]);
    AddOn::factory()->create(['add_on_group_id' => $group->id, 'name' => 'Secret Sauce', 'price' => '60.00', 'is_active' => false]);
    $product->addOnGroups()->attach([$group->id, $inactiveGroup->id]);

    $response = $this->getJson('/api/v1/catalog/products/loaded-fries');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.slug', 'loaded-fries')
        ->assertJsonPath('data.starting_price', '450.00')
        ->assertJsonCount(1, 'data.images')
        ->assertJsonCount(1, 'data.variants')
        ->assertJsonPath('data.variants.0.name', 'Regular')
        ->assertJsonCount(1, 'data.add_on_groups')
        ->assertJsonPath('data.add_on_groups.0.slug', 'ice-cream-toppings')
        ->assertJsonCount(1, 'data.add_on_groups.0.add_ons')
        ->assertJsonPath('data.add_on_groups.0.add_ons.0.name', 'Garlic Mayo');
});

test('inactive or missing products return not found', function () {
    $category = Category::factory()->create(['is_active' => true]);
    Product::factory()->for($category)->create(['slug' => 'gone-burger', 'is_active' => false]);

    $this->getJson('/api/v1/catalog/products/gone-burger')->assertNotFound();
    $this->getJson('/api/v1/catalog/products/never-existed')->assertNotFound();
});

test('unavailable products remain listed with available false', function () {
    $category = Category::factory()->create(['is_active' => true]);
    Product::factory()->for($category)->unavailable()->create([
        'slug' => 'sold-out-bowl',
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/catalog/products')
        ->assertSuccessful()
        ->assertJsonPath('data.0.slug', 'sold-out-bowl')
        ->assertJsonPath('data.0.available', false);

    $this->getJson('/api/v1/catalog/products/sold-out-bowl')
        ->assertSuccessful()
        ->assertJsonPath('data.available', false);
});

test('active branches and delivery zones are listed with optional branch filter', function () {
    $activeBranch = Branch::factory()->create(['slug' => 'dha-phase-7', 'is_active' => true]);
    $inactiveBranch = Branch::factory()->create(['slug' => 'closed-branch', 'is_active' => false]);

    DeliveryZone::factory()->for($activeBranch)->create([
        'name' => 'Phase 7 Zone',
        'delivery_fee' => '149.00',
        'is_active' => true,
    ]);
    DeliveryZone::factory()->for($activeBranch)->create([
        'name' => 'Inactive Zone',
        'is_active' => false,
    ]);
    DeliveryZone::factory()->for($inactiveBranch)->create([
        'name' => 'Should Hide',
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/catalog/branches')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'dha-phase-7');

    $this->getJson('/api/v1/catalog/delivery-zones')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.delivery_fee', '149.00')
        ->assertJsonPath('data.0.branch.slug', 'dha-phase-7');

    $this->getJson('/api/v1/catalog/delivery-zones?branch=dha-phase-7')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/v1/catalog/delivery-zones?branch=missing-branch')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['branch']);
});

test('catalog responses do not expose payment order or customer keys', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->for($category)->create(['slug' => 'privacy-bowl', 'is_active' => true]);
    Branch::factory()->create(['is_active' => true]);

    $responses = [
        $this->getJson('/api/v1/catalog/categories')->json('data'),
        $this->getJson('/api/v1/catalog/products')->json('data'),
        $this->getJson('/api/v1/catalog/products/'.$product->slug)->json('data'),
        $this->getJson('/api/v1/catalog/branches')->json('data'),
        $this->getJson('/api/v1/catalog/delivery-zones')->json('data'),
    ];

    $forbiddenKeys = [
        'payment',
        'payments',
        'payment_id',
        'proof_path',
        'public_order_number',
        'customer_notes',
        'customer_id',
        'user_id',
        'password',
        'email',
        'cart',
        'cart_id',
    ];

    $assertClean = function (mixed $value) use (&$assertClean, $forbiddenKeys): void {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $key => $child) {
            if (is_string($key)) {
                expect($forbiddenKeys)->not->toContain($key);
            }

            $assertClean($child);
        }
    };

    foreach ($responses as $response) {
        $assertClean($response);
    }
});
