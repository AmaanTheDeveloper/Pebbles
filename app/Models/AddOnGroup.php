<?php

namespace App\Models;

use Database\Factories\AddOnGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddOnGroup extends Model
{
    /** @use HasFactory<AddOnGroupFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_required',
        'min_selections',
        'max_selections',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'is_required' => false,
        'min_selections' => 0,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'min_selections' => 'integer',
            'max_selections' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<AddOnGroup>  $query
     * @return Builder<AddOnGroup>
     */
    #[Scope]
    protected function catalog(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function addOns(): HasMany
    {
        return $this->hasMany(AddOn::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withTimestamps();
    }
}
