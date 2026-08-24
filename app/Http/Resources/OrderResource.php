<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_order_number' => $this->public_order_number,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'delivery_fee' => $this->delivery_fee,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'customer_notes' => $this->customer_notes,
            'branch_id' => $this->branch_id,
            'created_at' => $this->created_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
        ];
    }
}
