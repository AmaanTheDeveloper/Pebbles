<?php

use App\Models\AddOnGroup;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

test('superadmin can create edit and deactivate products with variants', function () {
    $admin = User::factory()->superAdmin()->create();
    $category = Category::factory()->create(['is_active' => true]);
    $group = AddOnGroup::factory()->create(['is_active' => true]);

    $created = $this->actingAs($admin)
        ->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name' => 'Admin Burger',
            'slug' => 'admin-burger',
            'base_price' => '500.00',
            'is_active' => true,
            'is_available' => true,
            'add_on_group_ids' => [$group->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Admin Burger')
        ->json('data');

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/products/'.$created['id'].'/variants', [
            'name' => 'Large',
            'price' => '650.00',
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.price', '650.00');

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/products/'.$created['id'], [
            'is_available' => false,
            'name' => 'Admin Burger Updated',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.is_available', false)
        ->assertJsonPath('data.name', 'Admin Burger Updated');

    $this->getJson('/api/v1/catalog/products/admin-burger')
        ->assertSuccessful()
        ->assertJsonPath('data.available', false);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$created['id'])
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $this->getJson('/api/v1/catalog/products/admin-burger')->assertNotFound();
});

test('catalog product price change does not alter historical order snapshots', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $product = Product::factory()->create([
        'name' => 'Snapshot Meal',
        'base_price' => '1000.00',
        'is_active' => true,
        'is_available' => true,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'name' => 'Regular',
        'price' => '1000.00',
        'is_active' => true,
    ]);

    $order = Order::factory()->for($customer)->create(['delivery_fee' => '75.00']);
    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => 'Snapshot Meal',
        'variant_name' => 'Regular',
        'unit_price' => '1000.00',
        'line_total' => '1000.00',
    ]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/products/'.$product->id.'/variants/'.$variant->id, [
            'price' => '1500.00',
        ])
        ->assertSuccessful();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/products/'.$product->id, [
            'base_price' => '1500.00',
            'name' => 'Renamed Meal',
        ])
        ->assertSuccessful();

    expect($order->fresh()->items->first()->unit_price)->toBe('1000.00')
        ->and($order->fresh()->items->first()->product_name)->toBe('Snapshot Meal')
        ->and($order->fresh()->delivery_fee)->toBe('75.00');
});

test('superadmin can manage categories and add-on groups', function () {
    $admin = User::factory()->superAdmin()->create();

    $category = $this->actingAs($admin)
        ->postJson('/api/v1/admin/categories', [
            'name' => 'Wings',
            'slug' => 'wings',
            'is_active' => true,
        ])
        ->assertCreated()
        ->json('data');

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/categories/'.$category['id'], [
            'is_active' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $group = $this->actingAs($admin)
        ->postJson('/api/v1/admin/add-on-groups', [
            'name' => 'Sauces',
            'slug' => 'sauces',
            'is_active' => true,
        ])
        ->assertCreated()
        ->json('data');

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/add-on-groups/'.$group['id'].'/options', [
            'name' => 'BBQ',
            'price' => '50.00',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'BBQ');

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/add-on-groups/'.$group['id'], [
            'is_active' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);
});

test('category with products cannot be hard deleted', function () {
    $admin = User::factory()->superAdmin()->create();
    $category = Category::factory()->create();
    Product::factory()->for($category)->create();

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/categories/'.$category->id)
        ->assertUnprocessable();
});

test('customer cannot manage catalog via admin api', function () {
    $customer = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($customer)
        ->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name' => 'Nope',
            'slug' => 'nope',
            'base_price' => '10.00',
        ])
        ->assertForbidden();
});
