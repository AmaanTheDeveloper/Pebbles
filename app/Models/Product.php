<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'base_price',
        'is_active',
        'is_available',
        'is_featured',
        'sort_order',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_available' => true,
        'is_featured' => false,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Customer-visible products in active categories.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    #[Scope]
    protected function catalog(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->catalog())
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Resolve only customer-visible catalog products by slug.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field ??= $this->getRouteKeyName();

        $query = static::query();

        if ($field === $this->getRouteKeyName()) {
            $query->catalog();
        }

        return $query->where($field, $value)->firstOrFail();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function addOnGroups(): BelongsToMany
    {
        return $this->belongsToMany(AddOnGroup::class)
            ->withTimestamps()
            ->orderBy('sort_order');
    }

    public function startingPrice(): string
    {
        $lowestVariantPrice = $this->relationLoaded('variants')
            ? $this->variants->where('is_active', true)->min('price')
            : $this->variants()->catalog()->min('price');

        if ($lowestVariantPrice !== null) {
            return number_format((float) $lowestVariantPrice, 2, '.', '');
        }

        return number_format((float) $this->base_price, 2, '.', '');
    }
}
