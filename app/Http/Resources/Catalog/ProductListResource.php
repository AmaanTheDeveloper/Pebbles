<?php

namespace App\Http\Resources\Catalog;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductListResource extends JsonResource
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
            'short_description' => $this->short_description,
            'starting_price' => $this->startingPrice(),
            'available' => $this->is_available,
            'is_featured' => $this->is_featured,
            'image' => $this->whenLoaded('images', function () {
                $primaryImage = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

                if ($primaryImage === null) {
                    return null;
                }

                return [
                    'url' => $primaryImage->url(),
                    'alt_text' => $primaryImage->alt_text,
                ];
            }),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
