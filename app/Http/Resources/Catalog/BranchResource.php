<?php

namespace App\Http\Resources\Catalog;

use App\Models\Branch;
use App\Support\ContactSettings;
use App\Support\OpeningHours;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Branch
 */
class BranchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezone = ContactSettings::timezone();
        $hours = $this->resolvedOpeningHours();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'whatsapp_url' => $this->whatsappUrl(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'map_url' => $this->map_url,
            'directions_url' => $this->directionsUrl(),
            'map_embed_url' => $this->mapEmbedUrl(),
            'image_url' => $this->imageUrl(),
            'opens_at' => $this->opens_at,
            'closes_at' => $this->closes_at,
            'opening_hours' => $hours,
            'opening_hours_schedule' => OpeningHours::formattedSchedule($hours),
            'is_open_now' => $this->isOpenNow($timezone),
            'display_order' => $this->display_order,
            'preparation_minutes' => $this->preparation_minutes,
            'delivery_minutes' => $this->delivery_minutes,
            'available' => $this->is_active,
        ];
    }
}
