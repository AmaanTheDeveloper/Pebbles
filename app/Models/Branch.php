<?php

namespace App\Models;

use App\Support\OpeningHours;
use App\Support\PublicMediaUrl;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'address',
        'phone',
        'whatsapp',
        'latitude',
        'longitude',
        'map_url',
        'image_path',
        'opens_at',
        'closes_at',
        'opening_hours',
        'is_active',
        'display_order',
        'preparation_minutes',
        'delivery_minutes',
    ];

    protected $attributes = [
        'is_active' => true,
        'display_order' => 0,
        'preparation_minutes' => 20,
        'delivery_minutes' => 30,
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'opening_hours' => 'array',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'preparation_minutes' => 'integer',
            'delivery_minutes' => 'integer',
        ];
    }

    /**
     * @param  Builder<Branch>  $query
     * @return Builder<Branch>
     */
    #[Scope]
    protected function catalog(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name');
    }

    public function imageUrl(): ?string
    {
        return PublicMediaUrl::fromPath($this->image_path);
    }

    public function resolvedOpeningHours(): array
    {
        if (is_array($this->opening_hours) && $this->opening_hours !== []) {
            return OpeningHours::normalize($this->opening_hours);
        }

        return OpeningHours::normalize(
            OpeningHours::fromLegacyTimes(
                $this->opens_at ? substr((string) $this->opens_at, 0, 5) : null,
                $this->closes_at ? substr((string) $this->closes_at, 0, 5) : null,
            ),
        );
    }

    public function isOpenNow(?string $timezone = null): bool
    {
        return OpeningHours::isOpenNow($this->resolvedOpeningHours(), $timezone);
    }

    public function directionsUrl(): ?string
    {
        if ($this->map_url) {
            return $this->map_url;
        }

        if ($this->latitude !== null && $this->longitude !== null) {
            return 'https://www.google.com/maps/search/?api=1&query='.$this->latitude.','.$this->longitude;
        }

        if ($this->address) {
            return 'https://www.google.com/maps/search/?api=1&query='.urlencode($this->address);
        }

        return null;
    }

    public function mapEmbedUrl(): ?string
    {
        if ($this->latitude !== null && $this->longitude !== null) {
            return 'https://maps.google.com/maps?q='.$this->latitude.','.$this->longitude.'&z=15&output=embed';
        }

        if ($this->address) {
            return 'https://maps.google.com/maps?q='.urlencode($this->address).'&z=15&output=embed';
        }

        return null;
    }

    public function whatsappUrl(): ?string
    {
        if (! $this->whatsapp) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $this->whatsapp);

        return $digits ? 'https://wa.me/'.$digits : null;
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }
}
