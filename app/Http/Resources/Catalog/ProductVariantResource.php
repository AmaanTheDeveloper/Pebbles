<?php

namespace App\Http\Resources\Catalog;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductVariant
 */
class ProductVariantResource extends JsonResource
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
            'image' => $this->when(
                $this->relationLoaded('image'),
                fn () => $this->image === null ? null : [
                    'id' => $this->image->id,
                    'url' => $this->image->url(),
                    'alt_text' => $this->image->alt_text,
                ],
            ),
        ];
    }
}
