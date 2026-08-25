<?php

namespace App\Actions;

use App\Enums\OfferScope;
use App\Models\Cart;
use App\Models\Offer;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ResolveOfferDiscount
{
    public function __construct(
        private ResolveCartPricing $resolveCartPricing,
    ) {}

    /**
     * Resolve a single automatic discount from currently available Admin offers.
     * Only offers with a parseable percentage or fixed amount in `offer_value` apply.
     * Multiple active offers do not stack — the largest valid discount wins.
     * Discount is computed only against eligible lines for the offer's scope.
     *
     * @param  Collection<int, array{product_id: int, category_id: int|null, line_total: float|int|string}>|Cart  $linesOrCart
     * @return array{
     *     discount_amount: string,
     *     offer_id: int|null,
     *     offer_title: string|null,
     *     offer_value: string|null
     * }
     */
    public function handle(Collection|Cart $linesOrCart): array
    {
        $lines = $linesOrCart instanceof Cart
            ? $this->linesFromCart($linesOrCart)
            : $this->normalizeLines($linesOrCart);

        $cartSubtotal = $lines->sum(fn (array $line): float => (float) $line['line_total']);

        if ($cartSubtotal <= 0 || $lines->isEmpty()) {
            return $this->emptyResult();
        }

        /** @var Collection<int, Offer> $offers */
        $offers = Offer::query()
            ->currentlyAvailable()
            ->with(['categories:id', 'products:id'])
            ->get();

        $bestAmount = 0.0;
        $bestOffer = null;

        foreach ($offers as $offer) {
            $eligibleSubtotal = $this->eligibleSubtotal($offer, $lines);

            if ($eligibleSubtotal <= 0) {
                continue;
            }

            $amount = $this->discountAmountForOffer($offer, $eligibleSubtotal);

            if ($amount > $bestAmount) {
                $bestAmount = $amount;
                $bestOffer = $offer;
            }
        }

        if ($bestOffer === null || $bestAmount <= 0) {
            return $this->emptyResult();
        }

        return [
            'discount_amount' => Money::of($bestAmount),
            'offer_id' => $bestOffer->id,
            'offer_title' => $bestOffer->title,
            'offer_value' => $bestOffer->offer_value,
        ];
    }

    /**
     * Build priced offer lines from ResolveCartPricing results.
     *
     * @param  iterable<int, array{product: Product, line_total: float|int|string}>  $pricedLines
     * @return Collection<int, array{product_id: int, category_id: int|null, line_total: float}>
     */
    public function linesFromPriced(iterable $pricedLines): Collection
    {
        $lines = collect();

        foreach ($pricedLines as $priced) {
            $product = $priced['product'];

            $lines->push([
                'product_id' => (int) $product->id,
                'category_id' => $product->category_id !== null ? (int) $product->category_id : null,
                'line_total' => (float) $priced['line_total'],
            ]);
        }

        return $lines;
    }

    /**
     * @return array{kind: 'percent'|'fixed', value: float}|null
     */
    public function parseOfferValue(?string $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $value = trim($raw);

        if ($value === '') {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*%/', $value, $matches) === 1) {
            $percent = (float) $matches[1];

            if ($percent <= 0 || $percent > 100) {
                return null;
            }

            return [
                'kind' => 'percent',
                'value' => $percent,
            ];
        }

        if (preg_match('/(?:rs\.?|pkr|rupees?)\s*(\d+(?:\.\d+)?)/i', $value, $matches) === 1) {
            $fixed = (float) $matches[1];

            if ($fixed <= 0) {
                return null;
            }

            return [
                'kind' => 'fixed',
                'value' => $fixed,
            ];
        }

        return null;
    }

    public function discountAmountForOffer(Offer $offer, float $eligibleSubtotal): float
    {
        if ($eligibleSubtotal <= 0) {
            return 0.0;
        }

        $parsed = $this->parseOfferValue($offer->offer_value);

        if ($parsed === null) {
            return 0.0;
        }

        $amount = $parsed['kind'] === 'percent'
            ? round($eligibleSubtotal * ($parsed['value'] / 100), 2)
            : round($parsed['value'], 2);

        if ($offer->maximum_discount_amount !== null) {
            $amount = min($amount, (float) $offer->maximum_discount_amount);
        }

        return min(max(0.0, $amount), $eligibleSubtotal);
    }

    /**
     * @param  Collection<int, array{product_id: int, category_id: int|null, line_total: float}>  $lines
     */
    public function eligibleSubtotal(Offer $offer, Collection $lines): float
    {
        return $lines
            ->filter(fn (array $line): bool => $this->lineIsEligible($offer, $line))
            ->sum(fn (array $line): float => (float) $line['line_total']);
    }

    /**
     * @param  array{product_id: int, category_id: int|null, line_total: float}  $line
     */
    public function lineIsEligible(Offer $offer, array $line): bool
    {
        return match ($offer->scope ?? OfferScope::EntireStore) {
            OfferScope::EntireStore => true,
            OfferScope::Categories => ($line['category_id'] ?? null) !== null
                && $offer->categories->contains('id', (int) $line['category_id']),
            OfferScope::Products => $offer->products->contains('id', (int) $line['product_id']),
            default => false,
        };
    }

    /**
     * @return Collection<int, array{product_id: int, category_id: int|null, line_total: float}>
     */
    private function linesFromCart(Cart $cart): Collection
    {
        $cart->loadMissing(['items.addOns']);

        $lines = collect();

        foreach ($cart->items as $item) {
            $addOnIds = $item->addOns->pluck('add_on_id')->map(fn ($id) => (int) $id)->all();

            try {
                $priced = $this->resolveCartPricing->handle(
                    $item->product_id,
                    $item->product_variant_id,
                    $addOnIds,
                    $item->quantity,
                );
            } catch (ValidationException) {
                continue;
            }

            $product = $priced['product'];

            $lines->push([
                'product_id' => (int) $product->id,
                'category_id' => $product->category_id !== null ? (int) $product->category_id : null,
                'line_total' => (float) $priced['line_total'],
            ]);
        }

        return $lines;
    }

    /**
     * @param  Collection<int, array{product_id: int, category_id: int|null, line_total: float|int|string}>  $lines
     * @return Collection<int, array{product_id: int, category_id: int|null, line_total: float}>
     */
    private function normalizeLines(Collection $lines): Collection
    {
        return $lines
            ->map(fn (array $line): array => [
                'product_id' => (int) $line['product_id'],
                'category_id' => isset($line['category_id']) && $line['category_id'] !== null
                    ? (int) $line['category_id']
                    : null,
                'line_total' => max(0.0, (float) $line['line_total']),
            ])
            ->filter(fn (array $line): bool => $line['line_total'] > 0)
            ->values();
    }

    /**
     * @return array{
     *     discount_amount: string,
     *     offer_id: int|null,
     *     offer_title: string|null,
     *     offer_value: string|null
     * }
     */
    private function emptyResult(): array
    {
        return [
            'discount_amount' => Money::of(0),
            'offer_id' => null,
            'offer_title' => null,
            'offer_value' => null,
        ];
    }
}
