<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Support\GuestCartCookie;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
    $this->withCredentials();
});

function makeGuestOrderableProduct(array $attributes = []): Product
{
    $category = Category::factory()->create(['is_active' => true]);

    return Product::factory()->for($category)->create(array_merge([
        'base_price' => '500.00',
        'is_active' => true,
        'is_available' => true,
    ], $attributes));
}

function guestCartTokenFrom($response): string
{
    $cookie = $response->getCookie(GuestCartCookie::NAME, decrypt: false);

    expect($cookie)->not->toBeNull();

    return $cookie->getValue();
}

test('guest can fetch cart and receives guest cart cookie', function () {
    $response = $this->getJson('/api/v1/cart');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.item_count', 0)
        ->assertCookie(GuestCartCookie::NAME);

    $token = guestCartTokenFrom($response);

    expect(Cart::query()->where('guest_token', $token)->whereNull('user_id')->exists())->toBeTrue();
});

test('guest can add update and remove cart items using cookie', function () {
    $product = makeGuestOrderableProduct(['base_price' => '200.00']);

    $addResponse = $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $addResponse
        ->assertSuccessful()
        ->assertJsonPath('data.item_count', 1)
        ->assertCookie(GuestCartCookie::NAME);

    $token = guestCartTokenFrom($addResponse);
    $itemId = CartItem::query()->firstOrFail()->id;

    $this->withUnencryptedCookie(GuestCartCookie::NAME, $token)
        ->patchJson('/api/v1/cart/items/'.$itemId, ['quantity' => 3])
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.quantity', 3)
        ->assertJsonPath('data.items.0.line_total', '600.00');

    $this->withUnencryptedCookie(GuestCartCookie::NAME, $token)
        ->deleteJson('/api/v1/cart/items/'.$itemId)
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.items');
});

test('guest cannot mutate another guests cart item', function () {
    $product = makeGuestOrderableProduct();

    $ownerResponse = $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $ownerToken = guestCartTokenFrom($ownerResponse);
    $itemId = CartItem::query()->firstOrFail()->id;

    $intruderResponse = $this->getJson('/api/v1/cart')->assertSuccessful();
    $intruderToken = guestCartTokenFrom($intruderResponse);

    expect($intruderToken)->not->toBe($ownerToken);

    $this->withUnencryptedCookie(GuestCartCookie::NAME, $intruderToken)
        ->patchJson('/api/v1/cart/items/'.$itemId, ['quantity' => 5])
        ->assertForbidden();

    $this->withUnencryptedCookie(GuestCartCookie::NAME, $intruderToken)
        ->deleteJson('/api/v1/cart/items/'.$itemId)
        ->assertForbidden();
});

test('guest can clear cart', function () {
    $product = makeGuestOrderableProduct();

    $response = $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertSuccessful();

    $token = guestCartTokenFrom($response);

    $this->withUnencryptedCookie(GuestCartCookie::NAME, $token)
        ->deleteJson('/api/v1/cart')
        ->assertNoContent();

    expect(CartItem::query()->count())->toBe(0)
        ->and(Cart::query()->where('guest_token', $token)->exists())->toBeTrue();
});
