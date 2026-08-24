<?php

namespace App\Models;

use App\Support\PublicMediaUrl;
use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected $attributes = [
        'is_primary' => false,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): ?string
    {
        return PublicMediaUrl::fromPath($this->path);
    }
}
