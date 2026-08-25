<?php

namespace App\Models;

use App\Enums\StockStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

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
        'stock_quantity',
        'low_stock_threshold',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_available' => true,
        'is_featured' => false,
        'sort_order' => 0,
        'low_stock_threshold' => 5,
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    /**
     * Null stock_quantity means unlimited inventory.
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity === null || $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        if ($this->stock_quantity === null || $this->stock_quantity <= 0) {
            return false;
        }

        $threshold = $this->low_stock_threshold ?? 5;

        return $this->stock_quantity <= $threshold;
    }

    /**
     * Remaining sellable units, or null when stock is unlimited.
     */
    public function availableQuantity(): ?int
    {
        return $this->stock_quantity;
    }

    public function stockStatus(): StockStatus
    {
        if ($this->stock_quantity === null) {
            return StockStatus::Unlimited;
        }

        if ($this->stock_quantity <= 0) {
            return StockStatus::OutOfStock;
        }

        if ($this->isLowStock()) {
            return StockStatus::LowStock;
        }

        return StockStatus::InStock;
    }

    /**
     * @throws ValidationException
     */
    public function assertSufficientStock(int $quantity, int $credit = 0): void
    {
        if ($this->stock_quantity === null) {
            return;
        }

        $available = $this->stock_quantity + max(0, $credit);

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => $available <= 0
                    ? "{$this->name} is out of stock."
                    : "Only {$available} of {$this->name} left in stock.",
            ]);
        }
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

    /**
     * Legacy direct add-on pivot (`add_on_product`).
     *
     * Availability is resolved via add-on → category (`add_on_category`) in
     * ProductAddOnRules — keep this relation only for schema/backfill compatibility.
     *
     * @deprecated Use AddOn::categories() / ProductAddOnRules instead.
     */
    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class)
            ->withTimestamps();
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
