<?php

use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

test('ice cream products expose only ice cream topping groups', function () {
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($category)->create([
        'slug' => 'vanilla-scoop',
        'is_active' => true,
        'is_available' => true,
    ]);

    $iceCreamGroup = AddOnGroup::factory()->create([
        'name' => 'Ice Cream Toppings',
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $golaGroup = AddOnGroup::factory()->create([
        'name' => 'Gola Ganda Toppings',
        'slug' => 'gola-ganda-toppings',
        'is_active' => true,
    ]);

    AddOn::factory()->create([
        'add_on_group_id' => $iceCreamGroup->id,
        'name' => 'Chocolate Chips',
        'price' => '50.00',
        'is_active' => true,
    ]);
    AddOn::factory()->create([
        'add_on_group_id' => $golaGroup->id,
        'name' => 'Extra Jelly',
        'price' => '30.00',
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/catalog/products/vanilla-scoop')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.add_on_groups')
        ->assertJsonPath('data.add_on_groups.0.slug', 'ice-cream-toppings')
        ->assertJsonPath('data.add_on_groups.0.add_ons.0.name', 'Chocolate Chips');
});

test('gola ganda products expose only gola ganda topping groups', function () {
    $category = Category::factory()->create([
        'slug' => 'gola-ganda',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($category)->create([
        'slug' => 'rainbow-gola',
        'is_active' => true,
        'is_available' => true,
    ]);

    $iceCreamGroup = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $golaGroup = AddOnGroup::factory()->create([
        'slug' => 'gola-ganda-toppings',
        'is_active' => true,
    ]);

    AddOn::factory()->create([
        'add_on_group_id' => $iceCreamGroup->id,
        'name' => 'Oreo Crumbs',
        'price' => '80.00',
        'is_active' => true,
    ]);
    AddOn::factory()->create([
        'add_on_group_id' => $golaGroup->id,
        'name' => 'Extra Jelly',
        'price' => '30.00',
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/catalog/products/rainbow-gola')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.add_on_groups')
        ->assertJsonPath('data.add_on_groups.0.slug', 'gola-ganda-toppings')
        ->assertJsonPath('data.add_on_groups.0.add_ons.0.name', 'Extra Jelly');
});

test('non-eligible products do not expose legacy add-ons without category assignment', function () {
    $category = Category::factory()->create([
        'slug' => 'limca-flavors',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($category)->create([
        'slug' => 'classic-limca',
        'is_active' => true,
        'is_available' => true,
    ]);

    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'is_active' => true,
    ]);
    $product->addOnGroups()->attach($group->id);

    $this->getJson('/api/v1/catalog/products/classic-limca')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.add_on_groups');
});

test('explicit category assignment exposes add-ons on any assigned product category', function () {
    $limca = Category::factory()->create([
        'slug' => 'limca-flavors',
        'is_active' => true,
    ]);
    $coffee = Category::factory()->create([
        'slug' => 'coffee',
        'is_active' => true,
    ]);
    $limcaProduct = Product::factory()->for($limca)->create([
        'slug' => 'classic-limca-assigned',
        'is_active' => true,
        'is_available' => true,
    ]);
    $coffeeProduct = Product::factory()->for($coffee)->create([
        'slug' => 'cafe-latte',
        'is_active' => true,
        'is_available' => true,
    ]);

    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Rainbow Sprinkles',
        'price' => '40.00',
        'is_active' => true,
    ]);
    $addOn->categories()->sync([$limca->id, $coffee->id]);

    $this->getJson('/api/v1/catalog/products/classic-limca-assigned')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.add_on_groups')
        ->assertJsonPath('data.add_on_groups.0.add_ons.0.name', 'Rainbow Sprinkles');

    $this->getJson('/api/v1/catalog/products/cafe-latte')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.add_on_groups')
        ->assertJsonPath('data.add_on_groups.0.add_ons.0.name', 'Rainbow Sprinkles');
});

test('cart accepts add-ons assigned to a product category outside the legacy allowlist', function () {
    $user = User::factory()->create();
    $coffee = Category::factory()->create([
        'slug' => 'coffee',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($coffee)->create([
        'base_price' => '300.00',
        'is_active' => true,
        'is_available' => true,
    ]);
    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Extra Shot',
        'price' => '50.00',
        'is_active' => true,
    ]);
    $addOn->categories()->sync([$coffee->id]);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'add_on_ids' => [$addOn->id],
            'quantity' => 1,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.unit_price', '350.00');
});

test('empty add-on groups are hidden for eligible products', function () {
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    Product::factory()->for($category)->create([
        'slug' => 'plain-scoop',
        'is_active' => true,
        'is_available' => true,
    ]);

    AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/catalog/products/plain-scoop')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.add_on_groups');
});

test('cart rejects add-ons for non-eligible products', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'slug' => 'drinks',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($category)->create([
        'base_price' => '300.00',
        'is_active' => true,
        'is_available' => true,
    ]);

    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'price' => '50.00',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'add_on_ids' => [$addOn->id],
            'quantity' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['add_on_ids']);
});

test('cart accepts ice cream toppings only for ice cream products', function () {
    $user = User::factory()->create();
    $iceCreamCategory = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $golaCategory = Category::factory()->create([
        'slug' => 'gola-ganda',
        'is_active' => true,
    ]);

    $iceCreamProduct = Product::factory()->for($iceCreamCategory)->create([
        'base_price' => '450.00',
        'is_active' => true,
        'is_available' => true,
    ]);
    $golaProduct = Product::factory()->for($golaCategory)->create([
        'base_price' => '350.00',
        'is_active' => true,
        'is_available' => true,
    ]);

    $iceCreamGroup = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $golaGroup = AddOnGroup::factory()->create([
        'slug' => 'gola-ganda-toppings',
        'is_active' => true,
    ]);

    $iceCreamTopping = AddOn::factory()->create([
        'add_on_group_id' => $iceCreamGroup->id,
        'name' => 'Oreo Crumbs',
        'price' => '80.00',
        'is_active' => true,
    ]);
    $golaTopping = AddOn::factory()->create([
        'add_on_group_id' => $golaGroup->id,
        'name' => 'Extra Jelly',
        'price' => '30.00',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $iceCreamProduct->id,
            'add_on_ids' => [$iceCreamTopping->id],
            'quantity' => 1,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.unit_price', '530.00');

    $this->actingAs($user)
        ->deleteJson('/api/v1/cart')
        ->assertNoContent();

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $golaProduct->id,
            'add_on_ids' => [$iceCreamTopping->id],
            'quantity' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['add_on_ids']);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $golaProduct->id,
            'add_on_ids' => [$golaTopping->id],
            'quantity' => 1,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.unit_price', '380.00');
});

test('ice cream products with variants and toppings price correctly in cart', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($category)->create([
        'base_price' => '1000.00',
        'is_active' => true,
        'is_available' => true,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'name' => 'Large',
        'price' => '1200.00',
        'is_active' => true,
    ]);
    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Cheese',
        'price' => '100.00',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'add_on_ids' => [$addOn->id],
            'quantity' => 2,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.unit_price', '1300.00')
        ->assertJsonPath('data.items.0.line_total', '2600.00');
});
