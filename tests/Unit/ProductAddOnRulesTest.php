<?php

use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductAddOnRules;

test('legacy allowlist maps ice cream and gola ganda category slugs to groups', function () {
    expect(ProductAddOnRules::allowedGroupSlugsForCategory('ice-cream-premium-flavours'))
        ->toBe(ProductAddOnRules::ICE_CREAM_GROUP_SLUGS)
        ->and(ProductAddOnRules::allowedGroupSlugsForCategory('gola-ganda'))
        ->toBe(ProductAddOnRules::GOLA_GANDA_GROUP_SLUGS)
        ->and(ProductAddOnRules::allowedGroupSlugsForCategory('coffee'))
        ->toBe([])
        ->and(ProductAddOnRules::categoryAllowsAddOns('ice-cream-shakes'))->toBeTrue()
        ->and(ProductAddOnRules::categoryAllowsAddOns('limca-flavors'))->toBeFalse();
});

test('legacy unassigned add-ons resolve for allowlisted categories only', function () {
    $iceCream = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $coffee = Category::factory()->create([
        'slug' => 'coffee',
        'is_active' => true,
    ]);
    $iceCreamProduct = Product::factory()->for($iceCream)->create([
        'is_active' => true,
        'is_available' => true,
    ]);
    $coffeeProduct = Product::factory()->for($coffee)->create([
        'is_active' => true,
        'is_available' => true,
    ]);

    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Chocolate Chips',
        'is_active' => true,
    ]);

    $iceCreamGroups = ProductAddOnRules::groupsForProduct($iceCreamProduct->fresh('category'));
    $coffeeGroups = ProductAddOnRules::groupsForProduct($coffeeProduct->fresh('category'));

    expect($iceCreamGroups)->toHaveCount(1)
        ->and($iceCreamGroups->first()->slug)->toBe('ice-cream-toppings')
        ->and($coffeeGroups)->toHaveCount(0);
});

test('explicit category assignment beats legacy allowlist for non-legacy categories', function () {
    $coffee = Category::factory()->create([
        'slug' => 'coffee',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($coffee)->create([
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
        'is_active' => true,
    ]);
    $addOn->categories()->sync([$coffee->id]);

    $groups = ProductAddOnRules::groupsForProduct($product->fresh('category'));

    expect($groups)->toHaveCount(1)
        ->and($groups->first()->addOns->first()->name)->toBe('Extra Shot');
});

test('groupAllowsProduct follows legacy category to group mapping', function () {
    $iceCream = Category::factory()->create(['slug' => 'ice-cream-shakes']);
    $coffee = Category::factory()->create(['slug' => 'coffee']);
    $iceCreamProduct = Product::factory()->for($iceCream)->create();
    $coffeeProduct = Product::factory()->for($coffee)->create();
    $group = AddOnGroup::factory()->create(['slug' => 'ice-cream-toppings']);

    expect(ProductAddOnRules::groupAllowsProduct($group, $iceCreamProduct->fresh('category')))->toBeTrue()
        ->and(ProductAddOnRules::groupAllowsProduct($group, $coffeeProduct->fresh('category')))->toBeFalse();
});
