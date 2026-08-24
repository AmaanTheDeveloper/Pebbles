<?php

namespace App\Models;

use Database\Factories\DeliveryZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    /** @use HasFactory<DeliveryZoneFactory> */
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'delivery_fee',
        'minimum_order_amount',
        'estimated_minutes',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'estimated_minutes' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<DeliveryZone>  $query
     * @return Builder<DeliveryZone>
     */
    #[Scope]
    protected function catalog(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('branch', fn (Builder $branchQuery) => $branchQuery->catalog())
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
