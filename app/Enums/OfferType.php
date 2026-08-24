<?php

namespace App\Enums;

enum OfferType: string
{
    case ComboOffers = 'combo_offers';
    case Discounts = 'discounts';
    case SpecialEvents = 'special_events';
    case SeasonalDeals = 'seasonal_deals';

    public function label(): string
    {
        return match ($this) {
            self::ComboOffers => 'Combo Offers',
            self::Discounts => 'Discounts',
            self::SpecialEvents => 'Special Events',
            self::SeasonalDeals => 'Seasonal Deals',
        };
    }
}
