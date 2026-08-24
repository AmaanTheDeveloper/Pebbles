<?php

namespace App\Models;

use Database\Factories\OrderItemAddOnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemAddOn extends Model
{
    /** @use HasFactory<OrderItemAddOnFactory> */
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'add_on_id',
        'add_on_name',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected $attributes = [
        'quantity' => 1,
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'line_total' => 'decimal:2',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class);
    }
}
