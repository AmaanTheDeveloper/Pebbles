<?php

use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('super admin can create an add-on with explicit category assignments', function () {
    $admin = User::factory()->superAdmin()->create();
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $otherCategory = Category::factory()->create([
        'slug' => 'ice-cream-shakes',
        'is_active' => true,
    ]);
    Product::factory()->for($category)->create([
        'name' => 'Chocolate Scoop',
        'is_active' => true,
        'is_available' => true,
    ]);
    Product::factory()->for($otherCategory)->create([
        'name' => 'Vanilla Shake',
        'is_active' => true,
        'is_available' => true,
    ]);
    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/add-on-groups/'.$group->id.'/options', [
            'name' => 'Oreo Topping',
            'price' => 50,
            'is_active' => true,
            'category_ids' => [$category->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Oreo Topping')
        ->assertJsonPath('data.category_ids.0', $category->id);

    $addOn = AddOn::query()->where('name', 'Oreo Topping')->first();

    expect($addOn)->not->toBeNull()
        ->and($addOn->categories()->pluck('categories.id')->all())->toBe([$category->id]);
});

test('manager cannot create add-on category assignments', function () {
    $manager = User::factory()->manager()->create();
    $group = AddOnGroup::factory()->create(['slug' => 'ice-cream-toppings']);
    $category = Category::factory()->create(['slug' => 'ice-cream-premium-flavours', 'is_active' => true]);

    $this->actingAs($manager)
        ->postJson('/api/v1/admin/add-on-groups/'.$group->id.'/options', [
            'name' => 'Blocked',
            'price' => 10,
            'category_ids' => [$category->id],
        ])
        ->assertForbidden();
});

test('customer only sees add-ons assigned to the product category', function () {
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $otherCategory = Category::factory()->create([
        'slug' => 'ice-cream-shakes',
        'is_active' => true,
    ]);
    $chocolate = Product::factory()->for($category)->create([
        'slug' => 'chocolate-scoop',
        'is_active' => true,
        'is_available' => true,
    ]);
    Product::factory()->for($otherCategory)->create([
        'slug' => 'vanilla-shake',
        'is_active' => true,
        'is_available' => true,
    ]);
    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Oreo Topping',
        'price' => '50.00',
        'is_active' => true,
    ]);
    $addOn->categories()->sync([$category->id]);

    $this->getJson('/api/v1/catalog/products/chocolate-scoop')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.add_on_groups.0.add_ons')
        ->assertJsonPath('data.add_on_groups.0.add_ons.0.name', 'Oreo Topping');

    $this->getJson('/api/v1/catalog/products/vanilla-shake')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.add_on_groups');
});

test('cart rejects add-ons not assigned to the product category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $otherCategory = Category::factory()->create([
        'slug' => 'ice-cream-shakes',
        'is_active' => true,
    ]);
    $chocolate = Product::factory()->for($category)->create([
        'base_price' => '400.00',
        'is_active' => true,
        'is_available' => true,
    ]);
    $shake = Product::factory()->for($otherCategory)->create([
        'base_price' => '400.00',
        'is_active' => true,
        'is_available' => true,
    ]);
    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Oreo Topping',
        'price' => '50.00',
        'is_active' => true,
    ]);
    $addOn->categories()->sync([$category->id]);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $shake->id,
            'add_on_ids' => [$addOn->id],
            'quantity' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['add_on_ids']);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $chocolate->id,
            'add_on_ids' => [$addOn->id],
            'quantity' => 1,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.unit_price', '450.00');
});

test('inactive assigned add-ons are hidden from customers', function () {
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($category)->create([
        'slug' => 'inactive-topping-product',
        'is_active' => true,
        'is_available' => true,
    ]);
    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Hidden Topping',
        'is_active' => false,
    ]);
    $addOn->categories()->sync([$category->id]);

    $this->getJson('/api/v1/catalog/products/inactive-topping-product')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.add_on_groups');
});

test('assignable categories endpoint returns active categories', function () {
    $admin = User::factory()->superAdmin()->create();
    Category::factory()->create(['name' => 'Ice Cream Shakes', 'slug' => 'ice-cream-shakes', 'is_active' => true]);
    Category::factory()->create(['name' => 'Coffee', 'slug' => 'coffee', 'is_active' => true]);
    Category::factory()->create(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);
    $group = AddOnGroup::factory()->create(['slug' => 'ice-cream-toppings', 'is_active' => true]);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/add-on-groups/'.$group->id.'/assignable-categories')
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'Ice Cream Shakes'])
        ->assertJsonFragment(['name' => 'Coffee'])
        ->assertJsonMissing(['name' => 'Hidden']);
});

test('editing category assignments updates customer visibility', function () {
    $admin = User::factory()->superAdmin()->create();
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $otherCategory = Category::factory()->create([
        'slug' => 'ice-cream-shakes',
        'is_active' => true,
    ]);
    $chocolate = Product::factory()->for($category)->create([
        'slug' => 'edit-chocolate',
        'is_active' => true,
        'is_available' => true,
    ]);
    $shake = Product::factory()->for($otherCategory)->create([
        'slug' => 'edit-shake',
        'is_active' => true,
        'is_available' => true,
    ]);
    $group = AddOnGroup::factory()->create(['slug' => 'ice-cream-toppings', 'is_active' => true]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Oreo Topping',
        'is_active' => true,
    ]);
    $addOn->categories()->sync([$category->id, $otherCategory->id]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/add-on-groups/'.$group->id.'/options/'.$addOn->id, [
            'category_ids' => [$category->id],
        ])
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.category_ids');

    $this->getJson('/api/v1/catalog/products/edit-chocolate')
        ->assertSuccessful()
        ->assertJsonPath('data.add_on_groups.0.add_ons.0.name', 'Oreo Topping');

    $this->getJson('/api/v1/catalog/products/edit-shake')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.add_on_groups');
});

test('add-on assigned to multiple categories appears on products in each category', function () {
    $shakes = Category::factory()->create(['slug' => 'ice-cream-shakes', 'is_active' => true]);
    $premium = Category::factory()->create(['slug' => 'ice-cream-premium-flavours', 'is_active' => true]);
    $coffee = Category::factory()->create(['slug' => 'coffee', 'is_active' => true]);
    $chaat = Category::factory()->create(['slug' => 'channa-chaat', 'is_active' => true]);

    Product::factory()->for($shakes)->create([
        'slug' => 'chocolate-shake',
        'is_active' => true,
        'is_available' => true,
    ]);
    Product::factory()->for($premium)->create([
        'slug' => 'vanilla-scoop',
        'is_active' => true,
        'is_available' => true,
    ]);
    Product::factory()->for($coffee)->create([
        'slug' => 'cafe-mocha',
        'is_active' => true,
        'is_available' => true,
    ]);
    Product::factory()->for($chaat)->create([
        'slug' => 'chicken-chaat',
        'is_active' => true,
        'is_available' => true,
    ]);

    $group = AddOnGroup::factory()->create(['slug' => 'ice-cream-toppings', 'is_active' => true]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Chocolate Chips',
        'is_active' => true,
    ]);
    $addOn->categories()->sync([$shakes->id, $premium->id, $coffee->id]);

    $this->getJson('/api/v1/catalog/products/chocolate-shake')
        ->assertSuccessful()
        ->assertJsonPath('data.add_on_groups.0.add_ons.0.name', 'Chocolate Chips');

    $this->getJson('/api/v1/catalog/products/vanilla-scoop')
        ->assertSuccessful()
        ->assertJsonPath('data.add_on_groups.0.add_ons.0.name', 'Chocolate Chips');

    $this->getJson('/api/v1/catalog/products/cafe-mocha')
        ->assertSuccessful()
        ->assertJsonPath('data.add_on_groups.0.add_ons.0.name', 'Chocolate Chips');

    $this->getJson('/api/v1/catalog/products/chicken-chaat')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.add_on_groups');
});

test('inactive category-assigned add-ons are hidden from the customer product api', function () {
    $category = Category::factory()->create([
        'slug' => 'ice-cream-shakes',
        'is_active' => true,
    ]);
    Product::factory()->for($category)->create([
        'slug' => 'oreo-shake',
        'is_active' => true,
        'is_available' => true,
    ]);
    $group = AddOnGroup::factory()->create(['slug' => 'ice-cream-toppings', 'is_active' => true]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Oreo Crumbs',
        'is_active' => false,
    ]);
    $addOn->categories()->sync([$category->id]);

    $this->getJson('/api/v1/catalog/products/oreo-shake')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.add_on_groups');
});
