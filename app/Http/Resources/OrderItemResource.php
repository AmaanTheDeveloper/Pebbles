<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_name' => $this->product_name,
            'variant_name' => $this->variant_name,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'line_total' => $this->line_total,
            'add_ons' => $this->whenLoaded('addOns', fn () => $this->addOns->map(fn ($addOn) => [
                'add_on_name' => $addOn->add_on_name,
                'unit_price' => $addOn->unit_price,
                'quantity' => $addOn->quantity,
                'line_total' => $addOn->line_total,
            ])),
        ];
    }
}
