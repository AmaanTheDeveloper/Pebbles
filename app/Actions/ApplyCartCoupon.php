<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApplyCartCoupon
{
    public function __construct(
        private EnsureCustomerCart $ensureCustomerCart,
        private ResolveCouponDiscount $resolveCouponDiscount,
        private ResolveCartPricing $resolveCartPricing,
        private ResolveOfferDiscount $resolveOfferDiscount,
    ) {}

    public function handle(?User $user, string $code, ?int $branchId = null, ?string $guestToken = null): Cart
    {
        $cart = $this->ensureCustomerCart->handle($user, $branchId, $guestToken);
        $cart->load(['items.addOns', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Add items to your cart before applying a coupon.',
            ]);
        }

        $coupon = $this->resolveCouponDiscount->findByCode($code);
        $pricing = $this->priceCart($cart);
        $effectiveBranchId = $branchId ?? $cart->branch_id;

        $this->resolveCouponDiscount->handle(
            $coupon,
            $user,
            $pricing['subtotal'],
            $pricing['offer_discount'],
            $effectiveBranchId !== null ? (int) $effectiveBranchId : null,
            forCheckout: false,
        );

        $cart->update([
            'coupon_id' => $coupon->id,
            'branch_id' => $effectiveBranchId,
        ]);

        return $cart->fresh(['items.addOns', 'coupon', 'items.product', 'items.variant', 'items.addOns.addOn']);
    }

    /**
     * @return array{subtotal: float, offer_discount: float}
     */
    private function priceCart(Cart $cart): array
    {
        $subtotal = 0.0;
        $pricedLines = [];

        foreach ($cart->items as $item) {
            $addOnIds = $item->addOns->pluck('add_on_id')->map(fn ($id) => (int) $id)->all();

            try {
                $priced = $this->resolveCartPricing->handle(
                    $item->product_id,
                    $item->product_variant_id,
                    $addOnIds,
                    $item->quantity,
                );
                $subtotal += (float) $priced['line_total'];
                $pricedLines[] = $priced;
            } catch (ValidationException) {
                continue;
            }
        }

        $offer = $this->resolveOfferDiscount->handle(
            $this->resolveOfferDiscount->linesFromPriced($pricedLines)
        );

        return [
            'subtotal' => $subtotal,
            'offer_discount' => (float) $offer['discount_amount'],
        ];
    }
}
