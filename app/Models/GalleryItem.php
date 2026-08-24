<?php

namespace App\Models;

use App\Enums\GalleryCategory;
use App\Support\PublicMediaUrl;
use Database\Factories\GalleryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    /** @use HasFactory<GalleryItemFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'alt_text',
        'gradient',
        'category',
        'display_order',
        'is_active',
        'legacy_key',
    ];

    protected $attributes = [
        'display_order' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'category' => GalleryCategory::class,
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<GalleryItem>  $query
     * @return Builder<GalleryItem>
     */
    #[Scope]
    protected function catalog(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderByDesc('id');
    }

    public function imageUrl(): ?string
    {
        return PublicMediaUrl::fromPath($this->image_path);
    }

    /**
     * @return array<string, mixed>
     */
    public function toCustomerArray(): array
    {
        return [
            'id' => $this->id,
            'image' => $this->imageUrl(),
            'alt' => $this->alt_text ?: $this->title,
            'title' => $this->title,
            'category' => $this->category->value,
            'description' => $this->description,
            'gradient' => $this->gradient,
        ];
    }
}
