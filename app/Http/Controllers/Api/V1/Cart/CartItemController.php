<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Actions\AddCartItem;
use App\Actions\EnsureCustomerCart;
use App\Actions\ResolveCartPricing;
use App\Http\Controllers\Api\V1\Cart\Concerns\ResolvesCustomerCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\Cart\CartResource;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    use ResolvesCustomerCart;

    public function store(
        StoreCartItemRequest $request,
        EnsureCustomerCart $ensureCustomerCart,
        AddCartItem $addCartItem,
    ): CartResource {
        $user = $this->cartUser($request);
        $cart = $ensureCustomerCart->handle($user, null, $this->guestTokenFor($request, $user));

        $this->authorizeCustomerCart($request, $cart, $user, 'update');

        $addCartItem->handle($cart, $request->validated());

        return new CartResource($cart->fresh(['items.product', 'items.variant', 'items.addOns.addOn', 'coupon']));
    }

    public function update(
        UpdateCartItemRequest $request,
        CartItem $item,
        ResolveCartPricing $resolveCartPricing,
    ): CartResource {
        $item->load(['cart', 'addOns']);

        abort_unless($item->cart !== null, 404);

        $user = $this->cartUser($request);

        if ($user !== null) {
            $this->authorize('update', $item->cart);
        } else {
            $this->authorizeGuestOwnedCart($request, $item->cart);
        }

        $quantity = (int) $request->validated('quantity');

        $resolveCartPricing->handle(
            $item->product_id,
            $item->product_variant_id,
            $item->addOns->pluck('add_on_id')->map(fn ($id) => (int) $id)->all(),
            $quantity,
        );

        $item->loadMissing(['cart.items', 'product']);

        $otherProductQuantity = $item->cart->items
            ->where('product_id', $item->product_id)
            ->reject(fn (CartItem $cartItem) => $cartItem->id === $item->id)
            ->sum('quantity');

        $item->product?->assertSufficientStock($otherProductQuantity + $quantity);

        $item->update([
            'quantity' => $quantity,
        ]);

        $cart = $item->cart->fresh(['items.product', 'items.variant', 'items.addOns.addOn', 'coupon']);

        return new CartResource($cart);
    }

    public function destroy(Request $request, CartItem $item): CartResource
    {
        $item->load('cart');

        abort_unless($item->cart !== null, 404);

        $user = $this->cartUser($request);

        if ($user !== null) {
            $this->authorize('update', $item->cart);
        } else {
            $this->authorizeGuestOwnedCart($request, $item->cart);
        }

        $cart = $item->cart;

        $item->addOns()->delete();
        $item->delete();

        return new CartResource($cart->fresh(['items.product', 'items.variant', 'items.addOns.addOn', 'coupon']));
    }
}
