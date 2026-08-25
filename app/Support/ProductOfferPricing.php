<?php

namespace App\Support;

use App\Actions\ResolveOfferDiscount;
use App\Enums\OfferScope;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductOfferPricing
{
    /** @var Collection<int, Offer>|null */
    private static ?Collection $availableOffers = null;

    public function __construct(
        private ResolveOfferDiscount $resolveOfferDiscount,
    ) {}

    /**
     * Best active promotional price for a catalog product, or null when none applies.
     *
     * @return array{
     *     compare_at_price: string,
     *     promotional_price: string,
     *     offer_id: int,
     *     offer_title: string,
     *     offer_value: string|null
     * }|null
     */
    public function forProduct(Product $product): ?array
    {
        $compareAt = (float) $product->startingPrice();

        if ($compareAt <= 0) {
            return null;
        }

        $productId = (int) $product->id;
        $categoryId = $product->category_id !== null ? (int) $product->category_id : null;

        $bestDiscount = 0.0;
        $bestOffer = null;

        foreach ($this->availableOffers() as $offer) {
            if (! $this->offerAppliesToProduct($offer, $productId, $categoryId)) {
                continue;
            }

            $discount = $this->resolveOfferDiscount->discountAmountForOffer($offer, $compareAt);

            if ($discount > $bestDiscount) {
                $bestDiscount = $discount;
                $bestOffer = $offer;
            }
        }

        if ($bestOffer === null || $bestDiscount <= 0) {
            return null;
        }

        $promotional = max(0.0, $compareAt - $bestDiscount);

        if ($promotional >= $compareAt) {
            return null;
        }

        return [
            'compare_at_price' => Money::of($compareAt),
            'promotional_price' => Money::of($promotional),
            'offer_id' => $bestOffer->id,
            'offer_title' => $bestOffer->title,
            'offer_value' => $bestOffer->offer_value,
        ];
    }

    /**
     * Clear memoized offers between tests / after admin mutations in long-lived workers.
     */
    public static function flush(): void
    {
        self::$availableOffers = null;
    }

    /**
     * @return Collection<int, Offer>
     */
    private function availableOffers(): Collection
    {
        return self::$availableOffers ??= Offer::query()
            ->currentlyAvailable()
            ->with(['categories:id', 'products:id'])
            ->get();
    }

    private function offerAppliesToProduct(Offer $offer, int $productId, ?int $categoryId): bool
    {
        return match ($offer->scope ?? OfferScope::EntireStore) {
            OfferScope::EntireStore => true,
            OfferScope::Categories => $categoryId !== null
                && $offer->categories->contains('id', $categoryId),
            OfferScope::Products => $offer->products->contains('id', $productId),
            default => false,
        };
    }
}
