<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;

test('product detail variants include linked images and fall back when missing', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->for($category)->create([
        'slug' => 'mineral-demo',
        'base_price' => '100.00',
        'is_active' => true,
    ]);

    $primary = ProductImage::factory()->for($product)->primary()->create([
        'path' => 'products/mineral-demo/primary.jpg',
        'alt_text' => 'Primary bottle',
        'sort_order' => 0,
    ]);
    $largeImage = ProductImage::factory()->for($product)->create([
        'path' => 'products/mineral-demo/large.jpg',
        'alt_text' => 'Large bottle',
        'is_primary' => false,
        'sort_order' => 1,
    ]);
    $smallImage = ProductImage::factory()->for($product)->create([
        'path' => 'products/mineral-demo/small.jpg',
        'alt_text' => 'Small bottle',
        'is_primary' => false,
        'sort_order' => 2,
    ]);

    ProductVariant::factory()->for($product)->create([
        'name' => 'Large',
        'price' => '150.00',
        'is_active' => true,
        'sort_order' => 1,
        'product_image_id' => $largeImage->id,
    ]);
    ProductVariant::factory()->for($product)->create([
        'name' => 'Small',
        'price' => '80.00',
        'is_active' => true,
        'sort_order' => 2,
        'product_image_id' => $smallImage->id,
    ]);
    ProductVariant::factory()->for($product)->create([
        'name' => 'Regular',
        'price' => '100.00',
        'is_active' => true,
        'sort_order' => 3,
        'product_image_id' => null,
    ]);

    $response = $this->getJson('/api/v1/catalog/products/mineral-demo');

    $response
        ->assertSuccessful()
        ->assertJsonCount(3, 'data.variants')
        ->assertJsonPath('data.variants.0.name', 'Large')
        ->assertJsonPath('data.variants.0.price', '150.00')
        ->assertJsonPath('data.variants.0.image.alt_text', 'Large bottle')
        ->assertJsonPath('data.variants.1.name', 'Small')
        ->assertJsonPath('data.variants.1.price', '80.00')
        ->assertJsonPath('data.variants.1.image.alt_text', 'Small bottle')
        ->assertJsonPath('data.variants.2.name', 'Regular')
        ->assertJsonPath('data.variants.2.image', null);

    expect($response->json('data.variants.0.image.url'))->toContain('products/mineral-demo/large.jpg')
        ->and($response->json('data.images.0.id'))->toBe($primary->id);
});

test('inactive variants are omitted from catalog detail', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->for($category)->create([
        'slug' => 'hidden-size-drink',
        'is_active' => true,
    ]);

    ProductVariant::factory()->for($product)->create([
        'name' => 'Visible',
        'price' => '50.00',
        'is_active' => true,
    ]);
    ProductVariant::factory()->for($product)->create([
        'name' => 'Hidden',
        'price' => '40.00',
        'is_active' => false,
    ]);

    $this->getJson('/api/v1/catalog/products/hidden-size-drink')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.variants')
        ->assertJsonPath('data.variants.0.name', 'Visible');
});
