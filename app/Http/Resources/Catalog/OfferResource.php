<?php

namespace App\Http\Resources\Catalog;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Offer
 */
class OfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'short_description' => $this->short_description,
            'description' => $this->description,
            'image' => $this->imageUrl() === null ? null : [
                'url' => $this->imageUrl(),
            ],
            'offer_value' => $this->offer_value,
            'cta_label' => $this->cta_label,
            'cta_url' => $this->cta_url,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'sort_order' => $this->sort_order,
        ];
    }
}
