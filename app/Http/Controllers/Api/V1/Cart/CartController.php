<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Actions\EnsureCustomerCart;
use App\Http\Controllers\Api\V1\Cart\Concerns\ResolvesCustomerCart;
use App\Http\Controllers\Controller;
use App\Http\Resources\Cart\CartResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    use ResolvesCustomerCart;

    public function show(Request $request, EnsureCustomerCart $ensureCustomerCart): CartResource
    {
        $user = $this->cartUser($request);
        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        $cart = $ensureCustomerCart->handle($user, $branchId, $this->guestTokenFor($request, $user));

        $this->authorizeCustomerCart($request, $cart, $user, 'view');

        $cart->load(['items.product', 'items.variant', 'items.addOns.addOn', 'coupon']);

        return new CartResource($cart);
    }

    public function destroy(Request $request, EnsureCustomerCart $ensureCustomerCart): Response
    {
        $user = $this->cartUser($request);
        $cart = $ensureCustomerCart->handle($user, null, $this->guestTokenFor($request, $user));

        $this->authorizeCustomerCart($request, $cart, $user, 'delete');

        $cart->load('items.addOns');

        foreach ($cart->items as $item) {
            $item->addOns()->delete();
            $item->delete();
        }

        $cart->update(['coupon_id' => null]);

        return response()->noContent();
    }
}
