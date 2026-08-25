<?php

namespace App\Models;

use Database\Factories\AddOnFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Catalog add-on option within an add-on group.
 *
 * Targeting source of truth is {@see categories()} (`add_on_category`).
 * {@see products()} / `add_on_product` is legacy pivot data retained for schema
 * integrity and historical backfills — do not use it for new availability rules.
 */
class AddOn extends Model
{
    /** @use HasFactory<AddOnFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'add_on_group_id',
        'name',
        'price',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'price' => 0,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<AddOn>  $query
     * @return Builder<AddOn>
     */
    #[Scope]
    protected function catalog(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AddOnGroup::class, 'add_on_group_id');
    }

    /**
     * Legacy product-level pivot (`add_on_product`). Prefer {@see categories()}.
     *
     * @deprecated Use category targeting via add_on_category instead.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withTimestamps();
    }

    /**
     * Primary targeting: which categories may offer this add-on (`add_on_category`).
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withTimestamps();
    }
}
