<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemAddOn;
use Illuminate\Support\Facades\DB;

class AddCartItem
{
    public function __construct(
        private ResolveCartPricing $resolveCartPricing,
    ) {}

    /**
     * @param  array{product_id: int, product_variant_id?: int|null, quantity: int, add_on_ids?: list<int>}  $payload
     */
    public function handle(Cart $cart, array $payload): CartItem
    {
        $addOnIds = array_values(array_map('intval', $payload['add_on_ids'] ?? []));
        sort($addOnIds);

        $productId = (int) $payload['product_id'];
        $variantId = isset($payload['product_variant_id']) ? (int) $payload['product_variant_id'] : null;
        $addQuantity = (int) $payload['quantity'];

        $cart->loadMissing(['items.addOns']);

        $existing = $this->findMatchingItem($cart, $productId, $variantId, $addOnIds);
        $intendedLineQuantity = $existing !== null
            ? min(99, $existing->quantity + $addQuantity)
            : $addQuantity;

        $otherProductQuantity = $cart->items
            ->where('product_id', $productId)
            ->when($existing !== null, fn ($items) => $items->reject(fn (CartItem $item) => $item->id === $existing->id))
            ->sum('quantity');

        $priced = $this->resolveCartPricing->handle(
            $productId,
            $variantId,
            $addOnIds,
            $intendedLineQuantity,
        );

        $priced['product']->assertSufficientStock($otherProductQuantity + $intendedLineQuantity);

        return DB::transaction(function () use ($cart, $existing, $intendedLineQuantity, $priced): CartItem {
            if ($existing !== null) {
                $existing->update([
                    'quantity' => $intendedLineQuantity,
                ]);

                return $existing->fresh(['product', 'variant', 'addOns.addOn']);
            }

            $item = $cart->items()->create([
                'product_id' => $priced['product']->id,
                'product_variant_id' => $priced['variant']?->id,
                'quantity' => $intendedLineQuantity,
            ]);

            foreach ($priced['add_ons'] as $addOn) {
                CartItemAddOn::query()->create([
                    'cart_item_id' => $item->id,
                    'add_on_id' => $addOn->id,
                    'quantity' => 1,
                ]);
            }

            return $item->fresh(['product', 'variant', 'addOns.addOn']);
        });
    }

    /**
     * @param  list<int>  $addOnIds
     */
    private function findMatchingItem(Cart $cart, int $productId, ?int $variantId, array $addOnIds): ?CartItem
    {
        $cart->loadMissing(['items.addOns']);

        return $cart->items->first(function (CartItem $item) use ($productId, $variantId, $addOnIds): bool {
            if ($item->product_id !== $productId || $item->product_variant_id !== $variantId) {
                return false;
            }

            $existingIds = $item->addOns->pluck('add_on_id')->map(fn ($id) => (int) $id)->sort()->values()->all();

            return $existingIds === $addOnIds;
        });
    }
}
