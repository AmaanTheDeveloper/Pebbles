<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ApplyCartCoupon;
use App\Actions\RemoveCartCoupon;
use App\Http\Controllers\Api\V1\Cart\Concerns\ResolvesCustomerCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\ApplyCartCouponRequest;
use App\Http\Resources\Cart\CartResource;
use Illuminate\Http\Request;

class CartCouponController extends Controller
{
    use ResolvesCustomerCart;

    public function store(ApplyCartCouponRequest $request, ApplyCartCoupon $applyCartCoupon): CartResource
    {
        $user = $this->cartUser($request);
        $branchId = $request->validated('branch_id');

        $cart = $applyCartCoupon->handle(
            $user,
            $request->validated('coupon_code'),
            $branchId !== null ? (int) $branchId : null,
            $this->guestTokenFor($request, $user),
        );

        $this->authorizeCustomerCart($request, $cart, $user, 'view');

        $cart->load(['items.product', 'items.variant', 'items.addOns.addOn', 'coupon']);

        return new CartResource($cart);
    }

    public function destroy(Request $request, RemoveCartCoupon $removeCartCoupon): CartResource
    {
        $user = $this->cartUser($request);
        $cart = $removeCartCoupon->handle($user, $this->guestTokenFor($request, $user));

        $this->authorizeCustomerCart($request, $cart, $user, 'view');

        $cart->load(['items.product', 'items.variant', 'items.addOns.addOn', 'coupon']);

        return new CartResource($cart);
    }
}
