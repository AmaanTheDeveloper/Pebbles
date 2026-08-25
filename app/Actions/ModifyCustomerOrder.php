<?php

namespace App\Actions;

use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Events\OrderModifiedByCustomer;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddOn;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Support\CustomerOrderActions;
use App\Support\Money;
use App\Support\ProductStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModifyCustomerOrder
{
    public function __construct(
        private ResolveCartPricing $resolveCartPricing,
        private ResolveOfferDiscount $resolveOfferDiscount,
        private ResolveCouponDiscount $resolveCouponDiscount,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{order: Order, previous_total: string, new_total: string}
     */
    public function handle(Order $order, User $user, array $payload, bool $preview = false): array
    {
        return DB::transaction(function () use ($order, $user, $payload, $preview): array {
            $locked = Order::query()
                ->whereKey($order->id)
                ->with(['items.addOns', 'payment'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! CustomerOrderActions::canModify($locked)) {
                throw ValidationException::withMessages([
                    'order' => CustomerOrderActions::modifyUnavailableReason($locked)
                        ?? 'This order can no longer be modified because the allowed modification window has expired or the order is already being processed.',
                ]);
            }

            $items = $payload['items'] ?? [];

            if (! is_array($items) || $items === []) {
                throw ValidationException::withMessages([
                    'items' => 'At least one item is required.',
                ]);
            }

            $oldQuantitiesByProductId = $locked->items
                ->groupBy('product_id')
                ->map(fn ($orderItems) => (int) $orderItems->sum('quantity'));

            $pricedLines = [];
            $subtotal = 0.0;
            $newQuantitiesByProductId = [];

            foreach ($items as $item) {
                $quantity = (int) $item['quantity'];
                $productId = (int) $item['product_id'];
                $addOnIds = array_values(array_map('intval', $item['add_on_ids'] ?? []));
                $stockCredit = (int) ($oldQuantitiesByProductId[$productId] ?? 0);

                $priced = $this->resolveCartPricing->handle(
                    $productId,
                    isset($item['product_variant_id']) ? (int) $item['product_variant_id'] : null,
                    $addOnIds,
                    $quantity,
                    $stockCredit,
                );

                // Aggregate check across lines of the same product; line credit covers order-held units.
                $priced['product']->assertSufficientStock(
                    ($newQuantitiesByProductId[$productId] ?? 0) + $quantity,
                    $stockCredit,
                );

                $priced['quantity'] = $quantity;
                $pricedLines[] = $priced;
                $subtotal += (float) $priced['line_total'];
                $newQuantitiesByProductId[$productId] = ($newQuantitiesByProductId[$productId] ?? 0) + $quantity;
            }

            $deltas = [];
            foreach (array_unique([...$oldQuantitiesByProductId->keys()->all(), ...array_keys($newQuantitiesByProductId)]) as $productId) {
                $deltas[(int) $productId] = ($newQuantitiesByProductId[$productId] ?? 0)
                    - (int) ($oldQuantitiesByProductId[$productId] ?? 0);
            }

            if (! $preview) {
                ProductStock::applyDeltas($deltas);
            }

            $locked->loadMissing('deliveryZone');

            if ($locked->type === OrderType::Delivery && $locked->delivery_zone_id !== null) {
                $minimum = (float) ($locked->deliveryZone?->minimum_order_amount ?? 0);

                if ($minimum > 0 && $subtotal < $minimum) {
                    throw ValidationException::withMessages([
                        'items' => 'Order subtotal does not meet the minimum for this delivery zone.',
                    ]);
                }
            }

            $previousTotal = Money::of($locked->grand_total);
            $subtotalMoney = Money::of($subtotal);
            $offerDiscount = $this->resolveOfferDiscount->handle(
                $this->resolveOfferDiscount->linesFromPriced($pricedLines)
            );
            $offerDiscountAmount = $offerDiscount['discount_amount'];

            $couponId = $locked->coupon_id;
            $couponCode = $locked->coupon_code;
            $couponDiscountAmount = Money::of(0);

            if ($locked->coupon_id !== null) {
                $coupon = Coupon::query()
                    ->whereKey($locked->coupon_id)
                    ->lockForUpdate()
                    ->first();

                $resolved = $coupon === null
                    ? null
                    : $this->resolveCouponDiscount->tryResolve(
                        $coupon,
                        $user,
                        $subtotal,
                        $offerDiscountAmount,
                        (int) $locked->branch_id,
                    );

                if ($resolved === null) {
                    $couponId = null;
                    $couponCode = null;
                } else {
                    $couponId = $resolved['coupon_id'];
                    $couponCode = $resolved['coupon_code'];
                    $couponDiscountAmount = $resolved['discount_amount'];
                }
            }

            $discountAmount = Money::add($offerDiscountAmount, $couponDiscountAmount);
            $discountedSubtotal = Money::subtract($subtotalMoney, $discountAmount);
            $deliveryFee = Money::of($locked->delivery_fee);
            $grandTotal = Money::add($discountedSubtotal, $deliveryFee);

            if ($preview) {
                return [
                    'order' => $locked,
                    'previous_total' => $previousTotal,
                    'new_total' => $grandTotal,
                    'subtotal' => $subtotalMoney,
                    'discount_amount' => $discountAmount,
                    'delivery_fee' => $deliveryFee,
                    'priced_lines' => $pricedLines,
                ];
            }

            $updates = [
                'subtotal' => $subtotalMoney,
                'discount_amount' => $discountAmount,
                'coupon_id' => $couponId,
                'coupon_code' => $couponCode,
                'coupon_discount_amount' => $couponDiscountAmount,
                'grand_total' => $grandTotal,
            ];

            if (array_key_exists('customer_notes', $payload)) {
                $updates['customer_notes'] = $payload['customer_notes'];
            }

            if ($locked->type === OrderType::Takeaway && array_key_exists('pickup_notes', $payload)) {
                $updates['pickup_notes'] = $payload['pickup_notes'];
            }

            if ($locked->type === OrderType::Delivery && array_key_exists('delivery_instructions', $payload)) {
                $updates['delivery_instructions'] = $payload['delivery_instructions'];
            }

            $locked->update($updates);

            $locked->items()->each(function (OrderItem $item): void {
                $item->addOns()->delete();
                $item->delete();
            });

            foreach ($pricedLines as $priced) {
                $orderItem = OrderItem::query()->create([
                    'order_id' => $locked->id,
                    'product_id' => $priced['product']->id,
                    'product_variant_id' => $priced['variant']?->id,
                    'product_name' => $priced['product']->name,
                    'variant_name' => $priced['variant']?->name,
                    'unit_price' => $priced['unit_price'],
                    'quantity' => (int) ($priced['quantity'] ?? 1),
                    'line_total' => $priced['line_total'],
                ]);

                foreach ($priced['add_ons'] as $addOn) {
                    OrderItemAddOn::query()->create([
                        'order_item_id' => $orderItem->id,
                        'add_on_id' => $addOn->id,
                        'add_on_name' => $addOn->name,
                        'unit_price' => Money::of($addOn->price),
                        'quantity' => 1,
                        'line_total' => Money::of($addOn->price),
                    ]);
                }
            }

            if ($locked->payment !== null) {
                $payment = $locked->payment;
                $amountChanged = (float) $payment->amount !== (float) $grandTotal;

                $paymentUpdates = ['amount' => $grandTotal];

                if (
                    $amountChanged
                    && $payment->requiresProof()
                    && $payment->status === PaymentStatus::Verified
                ) {
                    $paymentUpdates['status'] = PaymentStatus::Submitted;
                    $paymentUpdates['verified_at'] = null;
                    $paymentUpdates['verified_by'] = null;
                }

                $payment->update($paymentUpdates);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $locked->id,
                'from_status' => $locked->status,
                'to_status' => $locked->status,
                'changed_by' => $user->id,
                'note' => (float) $previousTotal !== (float) $grandTotal
                    ? 'Order modified by customer. Total updated from Rs '.number_format((float) $previousTotal, 0).' to Rs '.number_format((float) $grandTotal, 0).'.'
                    : 'Order modified by customer.',
                'created_at' => now(),
            ]);

            $result = [
                'order' => $locked->fresh(['items.addOns', 'payment.proofs', 'statusHistories', 'branch']),
                'previous_total' => $previousTotal,
                'new_total' => $grandTotal,
            ];

            OrderModifiedByCustomer::dispatch($result['order']);

            return $result;
        });
    }
}
