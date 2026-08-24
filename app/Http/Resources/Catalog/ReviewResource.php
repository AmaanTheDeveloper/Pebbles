<?php

namespace App\Http\Resources\Catalog;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rating' => $this->rating,
            'review' => $this->review,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at?->toIso8601String(),
            'initial' => mb_strtoupper(mb_substr($this->name, 0, 1)),
        ];
    }
}
