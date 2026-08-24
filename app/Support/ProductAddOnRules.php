<?php

namespace App\Support;

use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductAddOnRules
{
    /** @var list<string> */
    public const ICE_CREAM_CATEGORY_SLUGS = [
        'ice-cream-premium-flavours',
    ];

    /** @var list<string> */
    public const GOLA_GANDA_CATEGORY_SLUGS = [
        'gola-ganda',
    ];

    /** @var list<string> */
    public const ICE_CREAM_GROUP_SLUGS = [
        'ice-cream-toppings',
    ];

    /** @var list<string> */
    public const GOLA_GANDA_GROUP_SLUGS = [
        'gola-ganda-toppings',
    ];

    /**
     * @return list<string>
     */
    public static function allowedGroupSlugsForCategory(?string $categorySlug): array
    {
        if ($categorySlug === null || $categorySlug === '') {
            return [];
        }

        if (in_array($categorySlug, self::ICE_CREAM_CATEGORY_SLUGS, true)) {
            return self::ICE_CREAM_GROUP_SLUGS;
        }

        if (in_array($categorySlug, self::GOLA_GANDA_CATEGORY_SLUGS, true)) {
            return self::GOLA_GANDA_GROUP_SLUGS;
        }

        return [];
    }

    public static function categoryAllowsAddOns(?string $categorySlug): bool
    {
        return self::allowedGroupSlugsForCategory($categorySlug) !== [];
    }

    /**
     * @return Collection<int, AddOnGroup>
     */
    public static function groupsForProduct(Product $product): Collection
    {
        $allowedSlugs = self::allowedGroupSlugsForCategory($product->category?->slug);

        if ($allowedSlugs === []) {
            return collect();
        }

        return AddOnGroup::query()
            ->catalog()
            ->whereIn('slug', $allowedSlugs)
            ->with(['addOns' => fn ($query) => $query->catalog()])
            ->get()
            ->filter(fn (AddOnGroup $group): bool => $group->addOns->isNotEmpty())
            ->values();
    }

    /**
     * @return Collection<int, AddOn>
     */
    public static function allowedAddOnsForProduct(Product $product): Collection
    {
        return self::groupsForProduct($product)
            ->flatMap(fn (AddOnGroup $group) => $group->addOns)
            ->values();
    }
}
