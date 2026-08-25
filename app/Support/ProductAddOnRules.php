<?php

namespace App\Support;

use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductAddOnRules
{
    /** @var list<string> */
    public const ICE_CREAM_CATEGORY_SLUGS = [
        'ice-cream-premium-flavours',
        'ice-cream-shakes',
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
     * Category slugs that may receive legacy (unassigned) add-ons from the given group slug.
     *
     * @return list<string>
     */
    public static function categorySlugsForGroup(?string $groupSlug): array
    {
        if ($groupSlug === null || $groupSlug === '') {
            return [];
        }

        if (in_array($groupSlug, self::ICE_CREAM_GROUP_SLUGS, true)) {
            return self::ICE_CREAM_CATEGORY_SLUGS;
        }

        if (in_array($groupSlug, self::GOLA_GANDA_GROUP_SLUGS, true)) {
            return self::GOLA_GANDA_CATEGORY_SLUGS;
        }

        return [];
    }

    public static function groupAllowsProduct(AddOnGroup $group, Product $product): bool
    {
        $allowedCategorySlugs = self::categorySlugsForGroup($group->slug);
        $productCategorySlug = $product->category?->slug;

        return $allowedCategorySlugs !== []
            && $productCategorySlug !== null
            && in_array($productCategorySlug, $allowedCategorySlugs, true);
    }

    /**
     * Resolve active add-on groups for a product.
     *
     * Source of truth:
     * 1. Explicit add_on_category assignments for the product's category_id
     * 2. Legacy add-ons with no category rows, limited to the historical group/category allowlist
     *
     * @return Collection<int, AddOnGroup>
     */
    public static function groupsForProduct(Product $product): Collection
    {
        if ($product->category_id === null) {
            return collect();
        }

        $legacyGroupSlugs = self::allowedGroupSlugsForCategory($product->category?->slug);

        return AddOnGroup::query()
            ->catalog()
            ->whereHas('addOns', function (Builder $query) use ($product, $legacyGroupSlugs): void {
                $query->catalog()->where(function (Builder $builder) use ($product, $legacyGroupSlugs): void {
                    self::constrainAddOnsForProduct($builder, $product, $legacyGroupSlugs);
                });
            })
            ->with(['addOns' => function ($query) use ($product, $legacyGroupSlugs): void {
                $query->catalog()->where(function (Builder $builder) use ($product, $legacyGroupSlugs): void {
                    self::constrainAddOnsForProduct($builder, $product, $legacyGroupSlugs);
                });
            }])
            ->get()
            ->filter(fn (AddOnGroup $group): bool => $group->addOns->isNotEmpty())
            ->values();
    }

    /**
     * @param  list<string>  $legacyGroupSlugs
     */
    private static function constrainAddOnsForProduct(Builder $builder, Product $product, array $legacyGroupSlugs): void
    {
        $builder->whereHas(
            'categories',
            fn (Builder $categories) => $categories->where('categories.id', $product->category_id)
        );

        if ($legacyGroupSlugs === []) {
            return;
        }

        $builder->orWhere(function (Builder $legacy) use ($legacyGroupSlugs): void {
            $legacy
                ->whereDoesntHave('categories')
                ->whereHas(
                    'group',
                    fn (Builder $group) => $group->whereIn('slug', $legacyGroupSlugs)
                );
        });
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
