<?php

namespace App\Enums;

enum GalleryCategory: string
{
    case Food = 'Food';
    case Interior = 'Interior';
    case Events = 'Events';
    case Atmosphere = 'Atmosphere';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
