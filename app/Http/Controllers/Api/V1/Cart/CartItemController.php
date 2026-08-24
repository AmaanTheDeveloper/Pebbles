<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Actions\AddCartItem;
use App\Actions\EnsureCustomerCart;
use App\Actions\ResolveCartPricing;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\Cart\CartResource;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function store(
        StoreCartItemRequest $request,
        EnsureCustomerCart $ensureCustomerCart,
        AddCartItem $addCartItem,
    ): CartResource {
        $cart = $ensureCustomerCart->handle($request->user());

        $this->authorize('update', $cart);

        $addCartItem->handle($cart, $request->validated());

        $cart->load(['items.product', 'items.variant', 'items.addOns.addOn']);

        return new CartResource($cart->fresh(['items.product', 'items.variant', 'items.addOns.addOn']));
    }

    public function update(
        UpdateCartItemRequest $request,
        CartItem $item,
        ResolveCartPricing $resolveCartPricing,
    ): CartResource {
        $item->load(['cart', 'addOns']);

        abort_unless($item->cart !== null, 404);
        $this->authorize('update', $item->cart);

        $resolveCartPricing->handle(
            $item->product_id,
            $item->product_variant_id,
            $item->addOns->pluck('add_on_id')->map(fn ($id) => (int) $id)->all(),
            (int) $request->validated('quantity'),
        );

        $item->update([
            'quantity' => (int) $request->validated('quantity'),
        ]);

        $cart = $item->cart->fresh(['items.product', 'items.variant', 'items.addOns.addOn']);

        return new CartResource($cart);
    }

    public function destroy(Request $request, CartItem $item): CartResource
    {
        $item->load('cart');

        abort_unless($item->cart !== null, 404);
        $this->authorize('update', $item->cart);

        $cart = $item->cart;

        $item->addOns()->delete();
        $item->delete();

        return new CartResource($cart->fresh(['items.product', 'items.variant', 'items.addOns.addOn']));
    }
}
