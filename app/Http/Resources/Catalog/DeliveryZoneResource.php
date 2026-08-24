<?php

namespace App\Http\Resources\Catalog;

use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryZone
 */
class DeliveryZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'delivery_fee' => $this->delivery_fee,
            'minimum_order_amount' => $this->minimum_order_amount,
            'estimated_minutes' => $this->estimated_minutes,
            'available' => $this->is_active,
            'branch' => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
                'slug' => $this->branch?->slug,
            ],
        ];
    }
}
