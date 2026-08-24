<?php

namespace App\Models;

use Database\Factories\HomepageCtaCardFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageCtaCard extends Model
{
    /** @use HasFactory<HomepageCtaCardFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'link_url',
        'tone',
        'display_order',
        'is_active',
    ];

    protected $attributes = [
        'tone' => 'orange',
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
     * @param  Builder<HomepageCtaCard>  $query
     * @return Builder<HomepageCtaCard>
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
