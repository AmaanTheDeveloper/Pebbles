<?php

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('core domain tables exist', function () {
    foreach ([
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'personal_access_tokens',
        'settings',
        'categories',
        'branches',
        'delivery_zones',
        'products',
        'product_images',
        'product_variants',
        'add_on_groups',
        'add_ons',
        'add_on_group_product',
        'add_on_product',
        'add_on_category',
        'addresses',
        'carts',
        'cart_items',
        'cart_item_add_ons',
        'orders',
        'order_items',
        'order_item_add_ons',
        'order_status_histories',
        'payments',
        'payment_proofs',
        'banners',
        'site_contents',
        'offers',
        'offer_category',
        'offer_product',
        'coupons',
        'coupon_redemptions',
        'reviews',
        'contact_messages',
        'gallery_items',
        'homepage_sections',
        'homepage_features',
        'homepage_cta_cards',
        'homepage_showcase_items',
        'menu_book_pages',
        'admin_notifications',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing table: {$table}");
    }
});

test('cart belongs to the correct user', function () {
    $user = User::factory()->create();
    $cart = Cart::factory()->for($user)->create();

    expect($cart->user->is($user))->toBeTrue()
        ->and($user->cart->is($cart))->toBeTrue();
});

test('money columns on orders are decimal', function () {
    $columns = collect(Schema::getColumns('orders'))->keyBy('name');

    foreach (['subtotal', 'delivery_fee', 'grand_total'] as $column) {
        $type = strtolower((string) ($columns[$column]['type'] ?? $columns[$column]['type_name'] ?? ''));
        expect($type)->toMatch('/decimal|numeric/');
    }
});

test('add_on_category is the primary add-on targeting table while add_on_product remains for legacy', function () {
    expect(Schema::hasTable('add_on_category'))->toBeTrue()
        ->and(Schema::hasTable('add_on_product'))->toBeTrue()
        ->and(Schema::hasColumns('add_on_category', ['add_on_id', 'category_id']))->toBeTrue()
        ->and(Schema::hasColumns('add_on_product', ['add_on_id', 'product_id']))->toBeTrue();
});
