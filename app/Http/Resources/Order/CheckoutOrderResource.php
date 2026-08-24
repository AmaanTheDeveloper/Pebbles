<?php

namespace App\Http\Resources\Order;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class CheckoutOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->public_order_number,
            'status' => $this->status?->value,
            'estimated_minutes' => $this->estimated_minutes,
            'fulfillment_type' => $this->type?->value,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'delivery_fee' => $this->delivery_fee,
            'total' => $this->grand_total,
            'payment' => $this->whenLoaded('payment', fn () => [
                'method' => $this->payment?->method?->value,
                'status' => $this->payment?->status?->value,
                'amount' => $this->payment?->amount,
                'requires_proof' => $this->payment?->requiresProof() ?? false,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
                'add_ons' => $item->relationLoaded('addOns')
                    ? $item->addOns->map(fn ($addOn) => [
                        'name' => $addOn->add_on_name,
                        'unit_price' => $addOn->unit_price,
                        'quantity' => $addOn->quantity,
                        'line_total' => $addOn->line_total,
                    ])->values()
                    : [],
            ])->values()),
        ];
    }
}
