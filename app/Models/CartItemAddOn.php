<?php

namespace App\Models;

use Database\Factories\CartItemAddOnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItemAddOn extends Model
{
    /** @use HasFactory<CartItemAddOnFactory> */
    use HasFactory;

    protected $fillable = [
        'cart_item_id',
        'add_on_id',
        'quantity',
    ];

    protected $attributes = [
        'quantity' => 1,
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }

    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class);
    }
}
