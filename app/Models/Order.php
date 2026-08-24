<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Support\CustomerOrderActions;
use App\Support\OrderNumberGenerator;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'public_order_number',
        'user_id',
        'branch_id',
        'type',
        'status',
        'confirmed_at',
        'estimated_minutes',
        'change_window_minutes',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'tax_amount',
        'grand_total',
        'customer_notes',
        'delivery_zone_id',
        'delivery_zone_name',
        'delivery_recipient_name',
        'delivery_phone',
        'delivery_line1',
        'delivery_line2',
        'delivery_area',
        'delivery_city',
        'delivery_instructions',
        'table_number',
        'pickup_notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $attributes = [
        'status' => 'pending',
        'discount_amount' => 0,
        'delivery_fee' => 0,
        'tax_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'confirmed_at' => 'datetime',
            'estimated_minutes' => 'integer',
            'change_window_minutes' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (blank($order->public_order_number)) {
                $order->public_order_number = app(OrderNumberGenerator::class)->generate();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_order_number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function isCancellable(): bool
    {
        return CustomerOrderActions::canCancel($this);
    }

    public function isModifiable(): bool
    {
        return CustomerOrderActions::canModify($this);
    }
}
