<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Models\Address;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddOn;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use App\Support\OrderEstimateResolver;
use App\Support\PaymentMethodCatalog;
use App\Support\ProductStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutCart
{
    public function __construct(
        private ResolveCartPricing $resolveCartPricing,
        private ResolveOfferDiscount $resolveOfferDiscount,
        private ResolveCouponDiscount $resolveCouponDiscount,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(User $user, array $payload): Order
    {
        return DB::transaction(function () use ($user, $payload): Order {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($cart === null) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            $cart->load(['items.addOns']);

            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            $branch = $this->resolveBranch((int) $payload['branch_id']);
            $type = OrderType::from($payload['type']);
            $deliveryZone = null;
            $address = null;
            $deliveryFee = Money::of(0);

            if ($type === OrderType::Delivery) {
                [$deliveryZone, $address, $deliveryFee] = $this->resolveDelivery($user, $payload, $branch);
            }

            if ($type === OrderType::DineIn && blank($payload['table_number'] ?? null)) {
                throw ValidationException::withMessages([
                    'table_number' => 'A table number is required for dine-in orders.',
                ]);
            }

            $pricedLines = [];
            $subtotal = 0.0;
            $quantitiesByProductId = [];

            foreach ($cart->items as $item) {
                $addOnIds = $item->addOns->pluck('add_on_id')->map(fn ($id) => (int) $id)->all();

                $priced = $this->resolveCartPricing->handle(
                    $item->product_id,
                    $item->product_variant_id,
                    $addOnIds,
                    $item->quantity,
                );

                $pricedLines[] = [
                    'cart_item' => $item,
                    'priced' => $priced,
                ];

                $subtotal += (float) $priced['line_total'];
                $quantitiesByProductId[$item->product_id] = ($quantitiesByProductId[$item->product_id] ?? 0) + $item->quantity;
            }

            ProductStock::deduct($quantitiesByProductId);

            if ($type === OrderType::Delivery && $deliveryZone !== null) {
                $minimum = (float) $deliveryZone->minimum_order_amount;

                if ($subtotal < $minimum) {
                    throw ValidationException::withMessages([
                        'cart' => 'Order subtotal does not meet the minimum for this delivery zone.',
                    ]);
                }
            }

            $subtotalMoney = Money::of($subtotal);
            $offerDiscount = $this->resolveOfferDiscount->handle(
                $this->resolveOfferDiscount->linesFromPriced(array_column($pricedLines, 'priced'))
            );
            $offerDiscountAmount = $offerDiscount['discount_amount'];

            $couponId = null;
            $couponCode = null;
            $couponDiscountAmount = Money::of(0);

            if ($cart->coupon_id !== null) {
                $coupon = Coupon::query()
                    ->whereKey($cart->coupon_id)
                    ->lockForUpdate()
                    ->first();

                if ($coupon === null) {
                    throw ValidationException::withMessages([
                        'coupon_code' => 'Invalid coupon code.',
                    ]);
                }

                $resolvedCoupon = $this->resolveCouponDiscount->handle(
                    $coupon,
                    $user,
                    $subtotal,
                    $offerDiscountAmount,
                    $branch->id,
                    forCheckout: true,
                );

                $couponId = $resolvedCoupon['coupon_id'];
                $couponCode = $resolvedCoupon['coupon_code'];
                $couponDiscountAmount = $resolvedCoupon['discount_amount'];
            }

            $discountAmount = Money::add($offerDiscountAmount, $couponDiscountAmount);
            $discountedSubtotal = Money::subtract($subtotalMoney, $discountAmount);
            $grandTotal = Money::add($discountedSubtotal, $deliveryFee);

            $order = Order::query()->create([
                'user_id' => $user->id,
                'branch_id' => $branch->id,
                'type' => $type,
                'status' => OrderStatus::Pending,
                'estimated_minutes' => OrderEstimateResolver::minutesFor($branch, $type),
                'subtotal' => $subtotalMoney,
                'discount_amount' => $discountAmount,
                'coupon_id' => $couponId,
                'coupon_code' => $couponCode,
                'coupon_discount_amount' => $couponDiscountAmount,
                'delivery_fee' => $deliveryFee,
                'tax_amount' => Money::of(0),
                'grand_total' => $grandTotal,
                'customer_notes' => $payload['customer_notes'] ?? null,
                'delivery_zone_id' => $deliveryZone?->id,
                'delivery_zone_name' => $deliveryZone?->name,
                'delivery_recipient_name' => $address?->recipient_name ?? ($payload['delivery_recipient_name'] ?? null),
                'delivery_phone' => $address?->phone ?? ($payload['delivery_phone'] ?? null),
                'delivery_line1' => $address?->line1 ?? ($payload['delivery_line1'] ?? null),
                'delivery_line2' => $address?->line2 ?? ($payload['delivery_line2'] ?? null),
                'delivery_area' => $address?->area ?? ($payload['delivery_area'] ?? null),
                'delivery_city' => $address?->city ?? ($payload['delivery_city'] ?? null),
                'delivery_instructions' => $address?->instructions ?? ($payload['delivery_instructions'] ?? null),
                'table_number' => $type === OrderType::DineIn ? ($payload['table_number'] ?? null) : null,
                'pickup_notes' => $type === OrderType::Takeaway ? ($payload['pickup_notes'] ?? null) : null,
            ]);

            if ($couponId !== null) {
                CouponRedemption::query()->create([
                    'coupon_id' => $couponId,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_amount' => $couponDiscountAmount,
                    'used_at' => now(),
                ]);

                Coupon::query()->whereKey($couponId)->increment('usage_count');
            }

            foreach ($pricedLines as $line) {
                /** @var CartItem $cartItem */
                $cartItem = $line['cart_item'];
                $priced = $line['priced'];

                $orderItem = OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $priced['product']->id,
                    'product_variant_id' => $priced['variant']?->id,
                    'product_name' => $priced['product']->name,
                    'variant_name' => $priced['variant']?->name,
                    'unit_price' => $priced['unit_price'],
                    'quantity' => $cartItem->quantity,
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

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => OrderStatus::Pending,
                'changed_by' => $user->id,
                'note' => 'Order placed by customer.',
                'created_at' => now(),
            ]);

            $paymentMethod = isset($payload['payment_method'])
                ? PaymentMethod::from($payload['payment_method'])
                : PaymentMethod::Cod;

            if (! app(PaymentMethodCatalog::class)->isEnabled($paymentMethod)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'The selected payment method is not available.',
                ]);
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'method' => $paymentMethod,
                'status' => PaymentStatus::Pending,
                'amount' => $grandTotal,
            ]);

            $cart->items()->each(function (CartItem $item): void {
                $item->addOns()->delete();
                $item->delete();
            });

            $cart->update([
                'branch_id' => $branch->id,
                'coupon_id' => null,
            ]);

            $order = $order->fresh(['items.addOns', 'branch', 'payment']);

            OrderPlaced::dispatch($order);

            return $order;
        });
    }

    private function resolveBranch(int $branchId): Branch
    {
        $branch = Branch::query()->catalog()->find($branchId);

        if ($branch === null) {
            throw ValidationException::withMessages([
                'branch_id' => 'The selected branch is unavailable.',
            ]);
        }

        return $branch;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: DeliveryZone, 1: ?Address, 2: string}
     */
    private function resolveDelivery(User $user, array $payload, Branch $branch): array
    {
        $zone = DeliveryZone::query()
            ->catalog()
            ->where('branch_id', $branch->id)
            ->find($payload['delivery_zone_id'] ?? null);

        if ($zone === null) {
            throw ValidationException::withMessages([
                'delivery_zone_id' => 'The selected delivery zone is invalid for this branch.',
            ]);
        }

        $address = null;

        if (! empty($payload['address_id'])) {
            $address = Address::query()
                ->where('user_id', $user->id)
                ->whereKey($payload['address_id'])
                ->first();

            if ($address === null) {
                throw ValidationException::withMessages([
                    'address_id' => 'The selected address is invalid.',
                ]);
            }

            if ($address->delivery_zone_id !== null && $address->delivery_zone_id !== $zone->id) {
                throw ValidationException::withMessages([
                    'address_id' => 'The selected address does not match the delivery zone.',
                ]);
            }
        } else {
            foreach (['delivery_recipient_name', 'delivery_phone', 'delivery_line1', 'delivery_city'] as $field) {
                if (blank($payload[$field] ?? null)) {
                    throw ValidationException::withMessages([
                        $field => 'This field is required for delivery orders.',
                    ]);
                }
            }
        }

        return [$zone, $address, Money::of($zone->delivery_fee)];
    }
}
