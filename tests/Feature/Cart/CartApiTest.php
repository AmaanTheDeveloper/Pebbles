<?php

use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\GuestCartCookie;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
});

function makeOrderableProduct(array $attributes = []): Product
{
    $category = Category::factory()->create(['is_active' => true]);

    return Product::factory()->for($category)->create(array_merge([
        'base_price' => '500.00',
        'is_active' => true,
        'is_available' => true,
    ], $attributes));
}

test('guests can access the cart', function () {
    $this->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.item_count', 0)
        ->assertCookie(GuestCartCookie::NAME);

    $this->postJson('/api/v1/cart/items', [])
        ->assertUnprocessable();
});

test('authenticated user can fetch an empty cart', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.item_count', 0)
        ->assertJsonPath('data.subtotal', '0.00')
        ->assertJsonPath('data.total', '0.00')
        ->assertJsonCount(0, 'data.items');
});

test('valid product configuration can be added and priced by the server', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($category)->create([
        'base_price' => '1000.00',
        'name' => 'Burger',
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
        'is_required' => false,
    ]);
    $addOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'name' => 'Cheese',
        'price' => '100.00',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'add_on_ids' => [$addOn->id],
        'quantity' => 2,
        'price' => 1,
        'total' => 1,
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.item_count', 2)
        ->assertJsonPath('data.items.0.unit_price', '1300.00')
        ->assertJsonPath('data.items.0.line_total', '2600.00')
        ->assertJsonPath('data.subtotal', '2600.00')
        ->assertJsonPath('data.total', '2600.00');

    expect(CartItem::query()->count())->toBe(1);
});

test('unavailable product cannot be added', function () {
    $user = User::factory()->create();
    $product = makeOrderableProduct(['is_available' => false]);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id']);
});

test('variant from another product is rejected', function () {
    $user = User::factory()->create();
    $productA = makeOrderableProduct();
    $productB = makeOrderableProduct();
    $foreignVariant = ProductVariant::factory()->for($productB)->create(['is_active' => true]);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $productA->id,
            'product_variant_id' => $foreignVariant->id,
            'quantity' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_variant_id']);

    expect(CartItem::query()->count())->toBe(0);
});

test('add-on from another product is rejected', function () {
    $user = User::factory()->create();
    $iceCreamCategory = Category::factory()->create([
        'slug' => 'ice-cream-premium-flavours',
        'is_active' => true,
    ]);
    $limcaCategory = Category::factory()->create([
        'slug' => 'limca-flavors',
        'is_active' => true,
    ]);
    $productA = Product::factory()->for($limcaCategory)->create([
        'is_active' => true,
        'is_available' => true,
    ]);
    $productB = Product::factory()->for($iceCreamCategory)->create([
        'is_active' => true,
        'is_available' => true,
    ]);
    $group = AddOnGroup::factory()->create([
        'slug' => 'ice-cream-toppings',
        'is_active' => true,
    ]);
    $foreignAddOn = AddOn::factory()->create([
        'add_on_group_id' => $group->id,
        'is_active' => true,
    ]);
    $foreignAddOn->categories()->sync([$iceCreamCategory->id]);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $productA->id,
            'add_on_ids' => [$foreignAddOn->id],
            'quantity' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['add_on_ids']);
});

test('invalid quantities are rejected', function () {
    $user = User::factory()->create();
    $product = makeOrderableProduct();

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 100,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);
});

test('cart item quantity can be updated and item can be deleted', function () {
    $user = User::factory()->create();
    $product = makeOrderableProduct(['base_price' => '200.00']);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $itemId = CartItem::query()->first()->id;

    $this->actingAs($user)
        ->patchJson('/api/v1/cart/items/'.$itemId, ['quantity' => 3, 'price' => 1])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.quantity', 3)
        ->assertJsonPath('data.items.0.line_total', '600.00');

    $this->actingAs($user)
        ->deleteJson('/api/v1/cart/items/'.$itemId)
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.items');
});

test('user cannot mutate another users cart item', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $product = makeOrderableProduct();

    $this->actingAs($owner)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $itemId = CartItem::query()->first()->id;

    $this->actingAs($intruder)
        ->patchJson('/api/v1/cart/items/'.$itemId, ['quantity' => 5])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->deleteJson('/api/v1/cart/items/'.$itemId)
        ->assertForbidden();
});

test('cart can be cleared', function () {
    $user = User::factory()->create();
    $product = makeOrderableProduct();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->deleteJson('/api/v1/cart')
        ->assertNoContent();

    expect(CartItem::query()->count())->toBe(0)
        ->and(Cart::query()->where('user_id', $user->id)->exists())->toBeTrue();
});
