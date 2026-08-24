<?php

namespace App\Models;

use Database\Factories\HomepageFeatureFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageFeature extends Model
{
    /** @use HasFactory<HomepageFeatureFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'icon_letter',
        'accent',
        'glow',
        'display_order',
        'is_active',
    ];

    protected $attributes = [
        'icon_letter' => 'P',
        'accent' => 'from-brand-green/15 to-brand-green/5',
        'glow' => 'lift-card-green',
        'display_order' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<HomepageFeature>  $query
     * @return Builder<HomepageFeature>
     */
    #[Scope]
    protected function catalog(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
