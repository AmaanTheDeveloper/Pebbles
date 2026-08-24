<?php

namespace App\Support;

final class Money
{
    public static function of(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    public static function add(float|int|string ...$amounts): string
    {
        $total = 0.0;

        foreach ($amounts as $amount) {
            $total += (float) $amount;
        }

        return self::of($total);
    }

    public static function multiply(float|int|string $amount, int|float $multiplier): string
    {
        return self::of((float) $amount * (float) $multiplier);
    }

    public static function subtract(float|int|string $amount, float|int|string $deduction): string
    {
        return self::of(max(0.0, (float) $amount - (float) $deduction));
    }
}
