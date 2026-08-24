<?php

namespace App\Http\Resources\Cart;

use App\Actions\ResolveCartPricing;
use App\Actions\ResolveOfferDiscount;
use App\Models\Cart;
use App\Models\CartItem;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

/**
 * @mixin Cart
 */
class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resolver = app(ResolveCartPricing::class);
        $items = [];
        $subtotal = 0.0;
        $itemCount = 0;

        foreach ($this->items as $item) {
            $payload = $this->serializeItem($item, $resolver);
            $items[] = $payload;
            $itemCount += $item->quantity;

            if ($payload['available'] && $payload['line_total'] !== null) {
                $subtotal += (float) $payload['line_total'];
            }
        }

        $subtotalMoney = Money::of($subtotal);
        $offerDiscount = app(ResolveOfferDiscount::class)->handle($subtotal);
        $discountAmount = $offerDiscount['discount_amount'];
        $totalMoney = Money::subtract($subtotalMoney, $discountAmount);

        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'items' => $items,
            'item_count' => $itemCount,
            'subtotal' => $subtotalMoney,
            'discount_amount' => $discountAmount,
            'discount_label' => $offerDiscount['offer_title'],
            'applied_offer' => $offerDiscount['offer_id'] === null ? null : [
                'id' => $offerDiscount['offer_id'],
                'title' => $offerDiscount['offer_title'],
                'offer_value' => $offerDiscount['offer_value'],
            ],
            'delivery_fee' => Money::of(0),
            'total' => $totalMoney,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeItem(CartItem $item, ResolveCartPricing $resolver): array
    {
        $addOnIds = $item->addOns->pluck('add_on_id')->map(fn ($id) => (int) $id)->all();

        try {
            $priced = $resolver->handle(
                $item->product_id,
                $item->product_variant_id,
                $addOnIds,
                $item->quantity,
            );

            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'available' => true,
                'error' => null,
                'product' => [
                    'id' => $priced['product']->id,
                    'name' => $priced['product']->name,
                    'slug' => $priced['product']->slug,
                ],
                'variant' => $priced['variant'] === null ? null : [
                    'id' => $priced['variant']->id,
                    'name' => $priced['variant']->name,
                ],
                'add_ons' => $priced['add_ons']->map(fn ($addOn) => [
                    'id' => $addOn->id,
                    'name' => $addOn->name,
                    'price' => Money::of($addOn->price),
                ])->values()->all(),
                'unit_price' => $priced['line_unit_price'],
                'line_total' => $priced['line_total'],
            ];
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?? 'Item is no longer available.';

            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'available' => false,
                'error' => $message,
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product?->name,
                    'slug' => $item->product?->slug,
                ],
                'variant' => $item->product_variant_id === null ? null : [
                    'id' => $item->product_variant_id,
                    'name' => $item->variant?->name,
                ],
                'add_ons' => $item->addOns->map(fn ($cartAddOn) => [
                    'id' => $cartAddOn->add_on_id,
                    'name' => $cartAddOn->addOn?->name,
                    'price' => $cartAddOn->addOn?->price,
                ])->values()->all(),
                'unit_price' => null,
                'line_total' => null,
            ];
        }
    }
}
