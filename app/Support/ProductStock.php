<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductStock
{
    /**
     * Lock products in a stable id order, then deduct tracked stock.
     *
     * @param  array<int, int>|Collection<int, int>  $quantitiesByProductId
     *
     * @throws ValidationException
     */
    public static function deduct(array|Collection $quantitiesByProductId): void
    {
        $quantities = collect($quantitiesByProductId)
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn (int $qty) => $qty > 0);

        if ($quantities->isEmpty()) {
            return;
        }

        $products = self::lockProducts($quantities->keys()->all());

        foreach ($quantities as $productId => $quantity) {
            /** @var Product|null $product */
            $product = $products->get($productId);

            if ($product === null) {
                continue;
            }

            if ($product->stock_quantity === null) {
                continue;
            }

            $product->assertSufficientStock($quantity);
            $product->decrement('stock_quantity', $quantity);
        }
    }

    /**
     * Restore tracked stock for cancelled or reduced order quantities.
     *
     * @param  array<int, int>|Collection<int, int>  $quantitiesByProductId
     */
    public static function restore(array|Collection $quantitiesByProductId): void
    {
        $quantities = collect($quantitiesByProductId)
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn (int $qty) => $qty > 0);

        if ($quantities->isEmpty()) {
            return;
        }

        $products = self::lockProducts($quantities->keys()->all());

        foreach ($quantities as $productId => $quantity) {
            /** @var Product|null $product */
            $product = $products->get($productId);

            if ($product === null || $product->stock_quantity === null) {
                continue;
            }

            $product->increment('stock_quantity', $quantity);
        }
    }

    /**
     * Apply net quantity deltas (positive = deduct, negative = restore).
     *
     * @param  array<int, int>|Collection<int, int>  $deltasByProductId
     *
     * @throws ValidationException
     */
    public static function applyDeltas(array|Collection $deltasByProductId): void
    {
        $deltas = collect($deltasByProductId)
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn (int $qty) => $qty !== 0);

        if ($deltas->isEmpty()) {
            return;
        }

        $products = self::lockProducts($deltas->keys()->all());

        foreach ($deltas as $productId => $delta) {
            /** @var Product|null $product */
            $product = $products->get($productId);

            if ($product === null || $product->stock_quantity === null) {
                continue;
            }

            if ($delta > 0) {
                $product->assertSufficientStock($delta);
                $product->decrement('stock_quantity', $delta);
            } else {
                $product->increment('stock_quantity', abs($delta));
            }
        }
    }

    /**
     * @param  list<int>  $productIds
     * @return Collection<int, Product>
     */
    private static function lockProducts(array $productIds): Collection
    {
        $sortedIds = collect($productIds)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        return Product::query()
            ->whereIn('id', $sortedIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }
}
