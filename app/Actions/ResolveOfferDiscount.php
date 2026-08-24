<?php

namespace App\Actions;

use App\Models\Offer;
use App\Support\Money;
use Illuminate\Support\Collection;

class ResolveOfferDiscount
{
    /**
     * Resolve a single automatic discount from currently available Admin offers.
     * Only offers with a parseable percentage or fixed amount in `offer_value` apply.
     * Multiple active offers do not stack — the largest valid discount wins.
     *
     * @return array{
     *     discount_amount: string,
     *     offer_id: int|null,
     *     offer_title: string|null,
     *     offer_value: string|null
     * }
     */
    public function handle(float|int|string $subtotal): array
    {
        $subtotalAmount = max(0.0, (float) $subtotal);

        if ($subtotalAmount <= 0) {
            return $this->emptyResult();
        }

        /** @var Collection<int, Offer> $offers */
        $offers = Offer::query()->currentlyAvailable()->get();

        $bestAmount = 0.0;
        $bestOffer = null;

        foreach ($offers as $offer) {
            $parsed = $this->parseOfferValue($offer->offer_value);

            if ($parsed === null) {
                continue;
            }

            $amount = $parsed['kind'] === 'percent'
                ? round($subtotalAmount * ($parsed['value'] / 100), 2)
                : round($parsed['value'], 2);

            $amount = min(max(0.0, $amount), $subtotalAmount);

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
