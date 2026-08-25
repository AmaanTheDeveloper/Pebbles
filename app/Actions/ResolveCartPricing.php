<?php

namespace App\Actions;

use App\Models\AddOn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use App\Support\ProductAddOnRules;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ResolveCartPricing
{
    /**
     * Validate a product configuration and return authoritative pricing.
     *
     * @param  list<int>  $addOnIds
     * @return array{
     *     product: Product,
     *     variant: ?ProductVariant,
     *     add_ons: Collection<int, AddOn>,
     *     unit_price: string,
     *     add_on_unit_total: string,
     *     line_unit_price: string,
     *     line_total: string
     * }
     */
    public function handle(int $productId, ?int $variantId, array $addOnIds, int $quantity, int $stockCredit = 0): array
    {
        if ($quantity < 1 || $quantity > 99) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be between 1 and 99.',
            ]);
        }

        $product = Product::query()
            ->catalog()
            ->with([
                'category',
                'variants' => fn ($query) => $query->catalog(),
            ])
            ->find($productId);

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => 'The selected product is not available.',
            ]);
        }

        if (! $product->is_available) {
            throw ValidationException::withMessages([
                'product_id' => 'The selected product is currently unavailable.',
            ]);
        }

        if (! $product->isInStock() && $stockCredit < 1) {
            throw ValidationException::withMessages([
                'product_id' => 'The selected product is currently unavailable.',
            ]);
        }

        $product->assertSufficientStock($quantity, $stockCredit);

        $variant = $this->resolveVariant($product, $variantId);
        $addOns = $this->resolveAddOns($product, $addOnIds);

        $baseUnit = $variant !== null
            ? Money::of($variant->price)
            : Money::of($product->base_price);

        $addOnUnitTotal = Money::of(
            $addOns->sum(fn (AddOn $addOn) => (float) $addOn->price)
        );

        $lineUnitPrice = Money::add($baseUnit, $addOnUnitTotal);
        $lineTotal = Money::multiply($lineUnitPrice, $quantity);

        return [
            'product' => $product,
            'variant' => $variant,
            'add_ons' => $addOns,
            'unit_price' => $baseUnit,
            'add_on_unit_total' => $addOnUnitTotal,
            'line_unit_price' => $lineUnitPrice,
            'line_total' => $lineTotal,
        ];
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        $activeVariants = $product->variants;

        if ($variantId === null) {
            if ($activeVariants->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'A variant is required for this product.',
                ]);
            }

            return null;
        }

        $variant = $activeVariants->firstWhere('id', $variantId);

        if ($variant === null) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'The selected variant is invalid for this product.',
            ]);
        }

        return $variant;
    }

    /**
     * @param  list<int>  $addOnIds
     * @return Collection<int, AddOn>
     */
    private function resolveAddOns(Product $product, array $addOnIds): Collection
    {
        $addOnIds = array_values(array_unique(array_map('intval', $addOnIds)));

        if ($addOnIds !== [] && $product->category_id === null) {
            throw ValidationException::withMessages([
                'add_on_ids' => 'Add-ons are not available for this product.',
            ]);
        }

        $allowedGroups = ProductAddOnRules::groupsForProduct($product);

        /** @var Collection<int, AddOn> $allowedAddOns */
        $allowedAddOns = $allowedGroups
            ->flatMap(fn ($group) => $group->addOns)
            ->keyBy('id');

        $selected = collect();

        foreach ($addOnIds as $addOnId) {
            $addOn = $allowedAddOns->get($addOnId);

            if ($addOn === null) {
                throw ValidationException::withMessages([
                    'add_on_ids' => 'One or more selected add-ons are invalid for this product.',
                ]);
            }

            $selected->push($addOn);
        }

        foreach ($allowedGroups as $group) {
            $groupAddOnIds = $group->addOns->pluck('id');
            $selectedCount = $selected->whereIn('id', $groupAddOnIds)->count();

            if ($group->is_required && $selectedCount < max(1, (int) $group->min_selections)) {
                throw ValidationException::withMessages([
                    'add_on_ids' => "Please select options for {$group->name}.",
                ]);
            }

            if ($group->min_selections > 0 && $selectedCount < $group->min_selections) {
                throw ValidationException::withMessages([
                    'add_on_ids' => "{$group->name} requires at least {$group->min_selections} selection(s).",
                ]);
            }

            if ($group->max_selections !== null && $selectedCount > $group->max_selections) {
                throw ValidationException::withMessages([
                    'add_on_ids' => "{$group->name} allows at most {$group->max_selections} selection(s).",
                ]);
            }
        }

        return $selected->values();
    }
}
