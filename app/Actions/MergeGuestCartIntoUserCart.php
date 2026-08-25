<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;
use App\Support\GuestCartCookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MergeGuestCartIntoUserCart
{
    public function __construct(
        private EnsureCustomerCart $ensureCustomerCart,
        private AddCartItem $addCartItem,
        private ResolveCouponDiscount $resolveCouponDiscount,
        private ResolveCartPricing $resolveCartPricing,
        private ResolveOfferDiscount $resolveOfferDiscount,
    ) {}

    public function handle(User $user, ?string $guestToken): void
    {
        if (! is_string($guestToken) || $guestToken === '') {
            return;
        }

        DB::transaction(function () use ($user, $guestToken): void {
            $guestCart = Cart::query()
                ->where('guest_token', $guestToken)
                ->whereNull('user_id')
                ->lockForUpdate()
                ->first();

            if ($guestCart === null) {
                return;
            }

            $userCart = $this->ensureCustomerCart->handle($user);
            $userCart = Cart::query()
                ->whereKey($userCart->id)
                ->lockForUpdate()
                ->firstOrFail();

            $guestCart->load(['items.addOns', 'coupon']);

            $guestHasItems = $guestCart->items->isNotEmpty();

            if ($guestHasItems && $guestCart->branch_id !== null) {
                $userCart->update(['branch_id' => $guestCart->branch_id]);
            }

            foreach ($guestCart->items as $item) {
                $addOnIds = $item->addOns
                    ->pluck('add_on_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                try {
                    $this->addCartItem->handle($userCart, [
                        'product_id' => (int) $item->product_id,
                        'product_variant_id' => $item->product_variant_id !== null
                            ? (int) $item->product_variant_id
                            : null,
                        'quantity' => (int) $item->quantity,
                        'add_on_ids' => $addOnIds,
                    ]);
                    $userCart->unsetRelation('items');
                } catch (ValidationException) {
                    // Skip items that are no longer orderable.
                }

                $item->addOns()->delete();
                $item->delete();
            }

            $userCart->refresh()->load(['items.addOns', 'coupon']);

            if ($guestCart->coupon_id !== null) {
                $userCart->update(['coupon_id' => $guestCart->coupon_id]);
                $userCart->refresh()->load(['items.addOns', 'coupon']);

                $pricing = $this->priceCart($userCart);
                $resolved = $this->resolveCouponDiscount->tryResolve(
                    $userCart->coupon,
                    $user,
                    $pricing['subtotal'],
                    $pricing['offer_discount'],
                    $userCart->branch_id !== null ? (int) $userCart->branch_id : null,
                );

                if ($resolved === null) {
                    $userCart->update(['coupon_id' => null]);
                }
            }

            $guestCart->delete();
        });

        GuestCartCookie::clear();
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
