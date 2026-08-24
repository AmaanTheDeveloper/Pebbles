<?php

namespace App\Models;

use Database\Factories\HomepageShowcaseItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageShowcaseItem extends Model
{
    /** @use HasFactory<HomepageShowcaseItemFactory> */
    use HasFactory;

    protected $fillable = [
        'pillar_key',
        'category_id',
        'label',
        'blurb',
        'badge',
        'accent',
        'is_special',
        'display_order',
        'is_active',
    ];

    protected $attributes = [
        'accent' => 'orange',
        'is_special' => false,
        'display_order' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'is_special' => 'boolean',
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @param  Builder<HomepageShowcaseItem>  $query
     * @return Builder<HomepageShowcaseItem>
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
