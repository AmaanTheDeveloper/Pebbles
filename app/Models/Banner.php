<?php

namespace App\Models;

use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Deprecated promotional slides table.
 *
 * Homepage CMS (`homepage_showcase_items`, CTA cards, sections) already covers
 * storefront marketing carousels. Prefer Homepage CMS and `site_contents` (About)
 * instead of building a parallel banners admin/API. The table remains for schema
 * integrity and legacy seed data only.
 *
 * @deprecated Use Homepage CMS showcase/CTA content instead.
 */
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'link_url',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
