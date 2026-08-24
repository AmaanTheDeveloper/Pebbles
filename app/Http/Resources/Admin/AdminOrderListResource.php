<?php

namespace App\Http\Resources\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class AdminOrderListResource extends JsonResource
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
            'total' => $this->grand_total,
            'customer' => $this->whenLoaded('user', fn () => [
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),
            'payment_status' => $this->whenLoaded('payment', fn () => $this->payment?->status?->value),
            'payment_method' => $this->whenLoaded('payment', fn () => $this->payment?->method?->value),
        ];
    }
}
