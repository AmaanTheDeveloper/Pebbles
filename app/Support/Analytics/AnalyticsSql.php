<?php

namespace App\Support\Analytics;

use Illuminate\Support\Facades\DB;

final class AnalyticsSql
{
    public static function bucketExpression(string $column, string $grain): string
    {
        $driver = DB::connection()->getDriverName();
        $allowedColumns = ['orders.created_at', 'created_at'];

        if (! in_array($column, $allowedColumns, true)) {
            $column = 'orders.created_at';
        }

        $grain = in_array($grain, ['day', 'week', 'month', 'hour', 'dow'], true) ? $grain : 'day';

        if ($driver === 'sqlite') {
            return match ($grain) {
                'week' => "strftime('%Y-%W', {$column})",
                'month' => "strftime('%Y-%m', {$column})",
                'hour' => "CAST(strftime('%H', {$column}) AS INTEGER)",
                'dow' => "CAST(strftime('%w', {$column}) AS INTEGER)",
                default => "date({$column})",
            };
        }

        return match ($grain) {
            'week' => "DATE_FORMAT({$column}, '%x-%v')",
            'month' => "DATE_FORMAT({$column}, '%Y-%m')",
            'hour' => "HOUR({$column})",
            'dow' => '(DAYOFWEEK('.$column.') - 1)',
            default => "DATE({$column})",
        };
    }

    public static function money(float|int|string|null $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }

    public static function rate(int $numerator, int $denominator): string
    {
        if ($denominator === 0) {
            return '0.00';
        }

        return number_format(($numerator / $denominator) * 100, 2, '.', '');
    }

    public static function aov(float $revenue, int $orders): string
    {
        if ($orders === 0) {
            return '0.00';
        }

        return self::money($revenue / $orders);
    }

    /**
     * @return array{direction: string, percent: string|null, label: string}
     */
    public static function delta(float $current, float $previous): array
    {
        if ($previous == 0.0) {
            if ($current == 0.0) {
                return [
                    'direction' => 'flat',
                    'percent' => '0.00',
                    'label' => 'No change',
                ];
            }

            return [
                'direction' => 'new',
                'percent' => null,
                'label' => 'New',
            ];
        }

        $change = (($current - $previous) / abs($previous)) * 100;

        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
            'percent' => number_format(abs($change), 2, '.', ''),
            'label' => number_format(abs($change), 1).'%',
        ];
    }
}
