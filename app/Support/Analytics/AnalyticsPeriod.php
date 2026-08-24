<?php

namespace App\Support\Analytics;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Inclusive calendar range expressed as [from, toExclusive).
 */
final class AnalyticsPeriod
{
    public const PRESETS = [
        'today',
        'yesterday',
        'last_7_days',
        'last_30_days',
        'this_month',
        'last_month',
        'this_year',
        'custom',
    ];

    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $toExclusive,
        public readonly string $preset,
    ) {}

    /**
     * @param  array{preset?: string|null, from?: string|null, to?: string|null}  $input
     */
    public static function fromInput(array $input, ?CarbonImmutable $now = null): self
    {
        $now ??= CarbonImmutable::now();
        $preset = $input['preset'] ?? 'last_30_days';

        if (! in_array($preset, self::PRESETS, true)) {
            throw new InvalidArgumentException('Invalid analytics preset.');
        }

        return match ($preset) {
            'today' => new self($now->startOfDay(), $now->startOfDay()->addDay(), $preset),
            'yesterday' => new self(
                $now->startOfDay()->subDay(),
                $now->startOfDay(),
                $preset,
            ),
            'last_7_days' => new self(
                $now->startOfDay()->subDays(6),
                $now->startOfDay()->addDay(),
                $preset,
            ),
            'last_30_days' => new self(
                $now->startOfDay()->subDays(29),
                $now->startOfDay()->addDay(),
                $preset,
            ),
            'this_month' => new self($now->startOfMonth(), $now->startOfMonth()->addMonth(), $preset),
            'last_month' => new self(
                $now->startOfMonth()->subMonth(),
                $now->startOfMonth(),
                $preset,
            ),
            'this_year' => new self($now->startOfYear(), $now->startOfYear()->addYear(), $preset),
            'custom' => self::fromCustom($input['from'] ?? null, $input['to'] ?? null, $now),
        };
    }

    public function previousEquivalent(): self
    {
        $seconds = $this->toExclusive->getTimestamp() - $this->from->getTimestamp();

        return new self(
            $this->from->subSeconds($seconds),
            $this->from,
            'custom',
        );
    }

    public function durationDays(): int
    {
        return max(1, (int) $this->from->diffInDays($this->toExclusive));
    }

    /**
     * Choose trend grain from range length.
     */
    public function suggestedGrain(): string
    {
        $days = $this->durationDays();

        if ($days <= 14) {
            return 'day';
        }

        if ($days <= 90) {
            return 'week';
        }

        return 'month';
    }

    private static function fromCustom(?string $from, ?string $to, CarbonImmutable $now): self
    {
        if (blank($from) || blank($to)) {
            throw new InvalidArgumentException('Custom range requires from and to dates.');
        }

        $start = CarbonImmutable::parse($from, $now->timezone)->startOfDay();
        $endInclusive = CarbonImmutable::parse($to, $now->timezone)->startOfDay();

        if ($endInclusive->lt($start)) {
            throw new InvalidArgumentException('The to date must be on or after the from date.');
        }

        return new self($start, $endInclusive->addDay(), 'custom');
    }
}
