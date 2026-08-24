<?php

use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Address;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;

test('category has many products', function () {
    $category = Category::factory()->create();
    $products = Product::factory()->count(2)->for($category)->create();

    expect($category->products)->toHaveCount(2)
        ->and($products->first()->category->is($category))->toBeTrue();
});

test('product has variants images and add-on groups', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->count(2)->for($product)->create();
    ProductImage::factory()->primary()->for($product)->create();
    $group = AddOnGroup::factory()->create();
    AddOn::factory()->count(2)->for($group, 'group')->create();
    $product->addOnGroups()->attach($group);

    $product->refresh();

    expect($product->variants)->toHaveCount(2)
        ->and($product->images)->toHaveCount(1)
        ->and($product->addOnGroups)->toHaveCount(1)
        ->and($product->addOnGroups->first()->addOns)->toHaveCount(2);
});

test('branch has delivery zones', function () {
    $branch = Branch::factory()->create();
    DeliveryZone::factory()->count(2)->for($branch)->create();

    expect($branch->deliveryZones)->toHaveCount(2);
});

test('user has addresses cart and orders', function () {
    $user = User::factory()->create();
    Address::factory()->count(2)->for($user)->create();
    $cart = Cart::factory()->for($user)->create();
    CartItem::factory()->for($cart)->create();
    Order::factory()->count(2)->for($user)->create();

    expect($user->addresses)->toHaveCount(2)
        ->and($user->cart->is($cart))->toBeTrue()
        ->and($user->cart->items)->toHaveCount(1)
        ->and($user->orders)->toHaveCount(2);
});

test('order has items payment and status history', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->count(2)->for($order)->create();
    Payment::factory()->for($order)->create(['amount' => $order->grand_total]);
    OrderStatusHistory::factory()->for($order)->create();

    expect($order->items)->toHaveCount(2)
        ->and($order->payment)->not->toBeNull()
        ->and($order->statusHistories)->toHaveCount(1);
});
