<?php

namespace App\Http\Resources\Catalog;

use App\Models\Product;
use App\Support\ProductOfferPricing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $promo = app(ProductOfferPricing::class)->forProduct($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'starting_price' => $this->startingPrice(),
            'compare_at_price' => $promo['compare_at_price'] ?? null,
            'promotional_price' => $promo['promotional_price'] ?? null,
            'available' => $this->is_available && $this->isInStock(),
            'stock_status' => $this->stockStatus()->value,
            'stock_quantity' => $this->availableQuantity(),
            'is_featured' => $this->is_featured,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'add_on_groups' => AddOnGroupResource::collection($this->whenLoaded('addOnGroups')),
        ];
    }
}
