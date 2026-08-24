<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class OpeningHours
{
    public const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    /**
     * @return array<string, array{label: string, closed: bool, opens: string|null, closes: string|null, note: string|null}>
     */
    public static function defaultWeek(): array
    {
        return collect(self::DAYS)
            ->mapWithKeys(fn (string $day): array => [
                $day => [
                    'label' => ucfirst($day),
                    'closed' => false,
                    'opens' => '12:00',
                    'closes' => '23:00',
                    'note' => null,
                ],
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $hours
     * @return array<string, array{label: string, closed: bool, opens: string|null, closes: string|null, note: string|null}>
     */
    public static function normalize(?array $hours): array
    {
        $defaults = self::defaultWeek();

        if ($hours === null) {
            return $defaults;
        }

        foreach (self::DAYS as $day) {
            if (! isset($hours[$day]) || ! is_array($hours[$day])) {
                continue;
            }

            $defaults[$day] = array_merge($defaults[$day], [
                'closed' => (bool) ($hours[$day]['closed'] ?? false),
                'opens' => self::normalizeTime($hours[$day]['opens'] ?? $defaults[$day]['opens']),
                'closes' => self::normalizeTime($hours[$day]['closes'] ?? $defaults[$day]['closes']),
                'note' => filled($hours[$day]['note'] ?? null) ? (string) $hours[$day]['note'] : null,
            ]);
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>|null  $hours
     */
    public static function isOpenNow(?array $hours, ?string $timezone = null, ?CarbonInterface $moment = null): bool
    {
        $normalized = self::normalize($hours);
        $now = ($moment ?? now())->timezone($timezone ?: config('app.timezone'));
        $dayKey = strtolower($now->englishDayOfWeek);
        $today = $normalized[$dayKey] ?? null;

        if ($today === null || $today['closed']) {
            return false;
        }

        if ($today['note']) {
            return false;
        }

        $opens = self::parseTimeOnDate($today['opens'], $now);
        $closes = self::parseTimeOnDate($today['closes'], $now);

        if ($opens === null || $closes === null) {
            return false;
        }

        if ($closes->lessThanOrEqualTo($opens)) {
            return $now->greaterThanOrEqualTo($opens) || $now->lessThanOrEqualTo($closes);
        }

        return $now->betweenIncluded($opens, $closes);
    }

    /**
     * @param  array<string, mixed>|null  $hours
     * @return array<int, array{day: string, label: string, closed: bool, opens: string|null, closes: string|null, note: string|null, formatted: string}>
     */
    public static function formattedSchedule(?array $hours): array
    {
        return collect(self::normalize($hours))
            ->map(function (array $day, string $key): array {
                $formatted = $day['closed']
                    ? 'Closed'
                    : (($day['opens'] && $day['closes'])
                        ? self::formatDisplayTime($day['opens']).' – '.self::formatDisplayTime($day['closes'])
                        : 'Hours unavailable');

                if ($day['note']) {
                    $formatted = $day['note'];
                }

                return [
                    'day' => $key,
                    'label' => $day['label'],
                    'closed' => (bool) $day['closed'],
                    'opens' => $day['opens'],
                    'closes' => $day['closes'],
                    'note' => $day['note'],
                    'formatted' => $formatted,
                ];
            })
            ->values()
            ->all();
    }

    public static function fromLegacyTimes(?string $opensAt, ?string $closesAt): ?array
    {
        if (! $opensAt || ! $closesAt) {
            return null;
        }

        $hours = self::defaultWeek();

        foreach (self::DAYS as $day) {
            $hours[$day]['opens'] = self::normalizeTime($opensAt);
            $hours[$day]['closes'] = self::normalizeTime($closesAt);
        }

        return $hours;
    }

    private static function normalizeTime(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return substr((string) $value, 0, 5);
    }

    private static function parseTimeOnDate(?string $time, CarbonInterface $date): ?CarbonInterface
    {
        if (! $time) {
            return null;
        }

        return $date->copy()->setTimeFromTimeString($time);
    }

    private static function formatDisplayTime(string $time): string
    {
        return Carbon::createFromFormat('H:i', substr($time, 0, 5))->format('g:i A');
    }
}
