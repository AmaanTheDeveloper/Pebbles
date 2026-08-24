<?php

namespace App\Models;

use Database\Factories\AddOnFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddOn extends Model
{
    /** @use HasFactory<AddOnFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'add_on_group_id',
        'name',
        'price',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'price' => 0,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<AddOn>  $query
     * @return Builder<AddOn>
     */
    #[Scope]
    protected function catalog(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AddOnGroup::class, 'add_on_group_id');
    }
}
