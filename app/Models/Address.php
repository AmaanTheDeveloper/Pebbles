<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'line1',
        'line2',
        'area',
        'city',
        'instructions',
        'delivery_zone_id',
        'is_default',
    ];

    protected $attributes = [
        'is_default' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }
}
