<?php

namespace App\Enums;

enum OfferScope: string
{
    case EntireStore = 'entire_store';
    case Categories = 'categories';
    case Products = 'products';

    public function label(): string
    {
        return match ($this) {
            self::EntireStore => 'Entire store',
            self::Categories => 'Selected categories',
            self::Products => 'Selected products',
        };
    }
}
