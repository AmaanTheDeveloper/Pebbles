<?php

namespace App\Models;

use App\Support\PublicMediaUrl;
use Database\Factories\MenuBookPageFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuBookPage extends Model
{
    /** @use HasFactory<MenuBookPageFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'image_path',
        'display_order',
        'is_active',
    ];

    protected $attributes = [
        'display_order' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<MenuBookPage>  $query
     * @return Builder<MenuBookPage>
     */
    #[Scope]
    protected function catalog(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id');
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
            'title' => $this->title,
            'image' => $this->imageUrl(),
            'alt' => $this->title ?: 'Pebbles menu page',
            'display_order' => $this->display_order,
        ];
    }
}
