<?php

namespace App\Models;

use Database\Factories\HomepageSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    /** @use HasFactory<HomepageSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'display_order',
        'is_active',
        'settings',
    ];

    protected $attributes = [
        'display_order' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * @param  Builder<HomepageSection>  $query
     * @return Builder<HomepageSection>
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
