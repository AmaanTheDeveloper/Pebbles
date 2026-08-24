<?php

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('core domain tables exist', function () {
    foreach ([
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
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
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
