<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\CustomerOrderLifecycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderOrderToCart
{
    public function __construct(
        private EnsureCustomerCart $ensureCustomerCart,
        private AddCartItem $addCartItem,
    ) {}

    /**
     * @return array{
     *     cart: Cart,
     *     added: list<array<string, mixed>>,
     *     unavailable: list<array<string, mixed>>,
     * }
     */
    public function handle(Order $order, User $user, string $mode = 'merge'): array
    {
        if (! CustomerOrderLifecycle::isFinal($order)) {
            throw ValidationException::withMessages([
                'order' => 'Only completed or cancelled orders can be reordered.',
            ]);
        }

        $order->load(['items.addOns']);

        if ($order->items->isEmpty()) {
            throw ValidationException::withMessages([
                'order' => 'This order has no items to reorder.',
            ]);
        }

        return DB::transaction(function () use ($order, $user, $mode): array {
            $cart = $this->ensureCustomerCart->handle($user);

            if ($mode === 'replace') {
                $this->clearCart($cart);
                $cart->refresh();
            }

            $added = [];
            $unavailable = [];

            foreach ($order->items as $item) {
                $result = $this->addOrderItemToCart($cart, $item);

                if ($result['status'] === 'added') {
                    $added[] = $result['item'];
                } else {
                    $unavailable[] = $result['item'];
                }
            }

            if ($added === []) {
                throw ValidationException::withMessages([
                    'order' => 'Some items from this order are currently unavailable.',
                    'unavailable' => $unavailable,
                ]);
            }

            $cart->load(['items.product', 'items.variant', 'items.addOns.addOn']);

            return [
                'cart' => $cart,
                'added' => $added,
                'unavailable' => $unavailable,
            ];
        });
    }

    /**
     * @return array{status: 'added'|'unavailable', item: array<string, mixed>}
     */
    private function addOrderItemToCart(Cart $cart, OrderItem $item): array
    {
        $addOnIds = $item->addOns
            ->pluck('add_on_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $basePayload = [
            'product_id' => (int) $item->product_id,
            'product_variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
            'quantity' => (int) $item->quantity,
        ];

        $display = [
            'product_name' => $item->product_name,
            'variant_name' => $item->variant_name,
            'quantity' => (int) $item->quantity,
            'add_ons' => $item->addOns->pluck('add_on_name')->filter()->values()->all(),
        ];

        try {
            $this->addCartItem->handle($cart, array_merge($basePayload, ['add_on_ids' => $addOnIds]));

            return [
                'status' => 'added',
                'item' => $display,
            ];
        } catch (ValidationException $exception) {
            if ($addOnIds !== []) {
                try {
                    $this->addCartItem->handle($cart, array_merge($basePayload, ['add_on_ids' => []]));

                    return [
                        'status' => 'added',
                        'item' => array_merge($display, [
                            'add_ons' => [],
                            'note' => 'Some options from this item are no longer available.',
                        ]),
                    ];
                } catch (ValidationException) {
                    // Fall through to unavailable handling.
                }
            }

            return [
                'status' => 'unavailable',
                'item' => array_merge($display, [
                    'reason' => $this->firstValidationMessage($exception),
                ]),
            ];
        }
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        $messages = $exception->errors();

        foreach ($messages as $fieldMessages) {
            if (is_array($fieldMessages) && isset($fieldMessages[0])) {
                return (string) $fieldMessages[0];
            }
        }

        return 'This item is currently unavailable.';
    }

    private function clearCart(Cart $cart): void
    {
        $cart->load('items.addOns');

        foreach ($cart->items as $item) {
            $item->addOns()->delete();
            $item->delete();
        }
    }
}
