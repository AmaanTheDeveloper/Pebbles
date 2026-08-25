<?php

namespace App\Models;

use App\Enums\OfferScope;
use App\Enums\OfferType;
use App\Support\PublicMediaUrl;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'short_description',
        'description',
        'image_path',
        'offer_value',
        'scope',
        'maximum_discount_amount',
        'cta_label',
        'cta_url',
        'starts_at',
        'ends_at',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
        'scope' => 'entire_store',
    ];

    protected function casts(): array
    {
        return [
            'type' => OfferType::class,
            'scope' => OfferScope::class,
            'maximum_discount_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'offer_category');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'offer_product');
    }

    /**
     * Active offers currently within their optional date window.
     *
     * @param  Builder<Offer>  $query
     * @return Builder<Offer>
     */
    #[Scope]
    protected function currentlyAvailable(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $builder) use ($now): void {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $builder) use ($now): void {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    public function imageUrl(): ?string
    {
        return PublicMediaUrl::fromPath($this->image_path);
    }
}
