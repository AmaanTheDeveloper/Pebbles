<?php

namespace App\Http\Resources\Catalog;

use App\Models\AddOnGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AddOnGroup
 */
class AddOnGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_required' => $this->is_required,
            'min_selections' => $this->min_selections,
            'max_selections' => $this->max_selections,
            'available' => $this->is_active,
            'add_ons' => AddOnResource::collection($this->whenLoaded('addOns')),
        ];
    }
}
