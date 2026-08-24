<?php

namespace App\Http\Resources;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Address
 */
class AddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'area' => $this->area,
            'city' => $this->city,
            'instructions' => $this->instructions,
            'delivery_zone_id' => $this->delivery_zone_id,
            'is_default' => $this->is_default,
        ];
    }
}
