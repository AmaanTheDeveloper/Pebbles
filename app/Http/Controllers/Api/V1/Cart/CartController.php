<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Actions\EnsureCustomerCart;
use App\Http\Controllers\Controller;
use App\Http\Resources\Cart\CartResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function show(Request $request, EnsureCustomerCart $ensureCustomerCart): CartResource
    {
        $cart = $ensureCustomerCart->handle($request->user());

        $this->authorize('view', $cart);

        $cart->load(['items.product', 'items.variant', 'items.addOns.addOn']);

        return new CartResource($cart);
    }

    public function destroy(Request $request, EnsureCustomerCart $ensureCustomerCart): Response
    {
        $cart = $ensureCustomerCart->handle($request->user());

        $this->authorize('delete', $cart);

        $cart->load('items.addOns');

        foreach ($cart->items as $item) {
            $item->addOns()->delete();
            $item->delete();
        }

        return response()->noContent();
    }
}
