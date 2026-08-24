<?php

namespace App\Http\Resources\Catalog;

use App\Models\AddOn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AddOn
 */
class AddOnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'available' => $this->is_active,
        ];
    }
}
