<?php

namespace App\Http\Resources\Order;

use App\Models\Order;
use App\Support\CustomerOrderLifecycle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact order list row for the authenticated customer.
 *
 * @mixin Order
 */
class CustomerOrderListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->public_order_number,
            'created_at' => $this->created_at,
            'status' => $this->status?->value,
            'fulfillment_type' => $this->type?->value,
            'branch' => $this->whenLoaded('branch', fn () => [
                'name' => $this->branch?->name,
                'slug' => $this->branch?->slug,
            ]),
            'total' => $this->grand_total,
            'payment_status' => $this->whenLoaded('payment', fn () => $this->payment?->status?->value),
            'payment_method' => $this->whenLoaded('payment', fn () => $this->payment?->method?->value),
            'lifecycle' => CustomerOrderLifecycle::payloadFor($this->resource),
        ];
    }
}
