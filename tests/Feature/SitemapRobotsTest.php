<?php

use App\Models\Category;
use App\Models\Product;

test('sitemap xml returns public indexable routes and catalog entries', function () {
    $category = Category::factory()->create([
        'name' => 'Ice Cream',
        'slug' => 'ice-cream-seo',
        'is_active' => true,
    ]);
    $product = Product::factory()->for($category)->create([
        'name' => 'Mango Scoop',
        'slug' => 'mango-scoop-seo',
        'is_active' => true,
        'is_available' => true,
    ]);
    Product::factory()->for($category)->create([
        'name' => 'Hidden Scoop',
        'slug' => 'hidden-scoop-seo',
        'is_active' => false,
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('content-type', 'application/xml; charset=UTF-8');

    $body = $response->getContent();

    expect($body)->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->and($body)->toContain('<urlset')
        ->and($body)->toContain('/menu</loc>')
        ->and($body)->toContain('/about</loc>')
        ->and($body)->toContain('/gallery</loc>')
        ->and($body)->toContain('/product/'.$product->slug)
        ->and($body)->toContain('/menu/'.$category->slug)
        ->and($body)->not->toContain('/admin')
        ->and($body)->not->toContain('/cart')
        ->and($body)->not->toContain('/checkout')
        ->and($body)->not->toContain('/profile')
        ->and($body)->not->toContain('/verify-email')
        ->and($body)->not->toContain('hidden-scoop-seo');
});

test('robots txt references the sitemap', function () {
    $contents = file_get_contents(public_path('robots.txt'));

    expect($contents)->toContain('Sitemap: /sitemap.xml')
        ->and($contents)->toContain('Disallow: /admin')
        ->and($contents)->toContain('Disallow: /cart');
});
