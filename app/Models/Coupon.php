<?php

namespace App\Models;

use App\Enums\CouponDiscountType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Coupon usage is recorded when an order is successfully created and is not
     * released if that order is later cancelled. This prevents cancel-and-reuse abuse.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'minimum_order_amount',
        'maximum_discount_amount',
        'usage_limit',
        'usage_count',
        'per_customer_limit',
        'starts_at',
        'expires_at',
        'is_active',
        'branch_id',
        'created_by',
    ];

    protected $attributes = [
        'usage_count' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => CouponDiscountType::class,
            'discount_value' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'maximum_discount_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'per_customer_limit' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public static function normalizeCode(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function statusLabel(?Carbon $now = null): string
    {
        $moment = $now ?? now();

        if (! $this->is_active) {
            return 'Inactive';
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return 'Scheduled';
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return 'Expired';
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return 'Exhausted';
        }

        return 'Active';
    }
}
