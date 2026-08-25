<?php

use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Setting;
use App\Support\OrderEstimateResolver;

test('delivery eta adds preparation and delivery minutes', function () {
    $branch = new Branch([
        'preparation_minutes' => 10,
        'delivery_minutes' => 20,
    ]);

    expect(OrderEstimateResolver::minutesFor($branch, OrderType::Delivery))->toBe(30);
});

test('takeaway and dine_in use preparation minutes only', function () {
    $branch = new Branch([
        'preparation_minutes' => 15,
        'delivery_minutes' => 40,
    ]);

    expect(OrderEstimateResolver::minutesFor($branch, OrderType::Takeaway))->toBe(15)
        ->and(OrderEstimateResolver::minutesFor($branch, OrderType::DineIn))->toBe(15);
});

test('falls back to global estimate when branch minutes are missing', function () {
    Setting::setValue('order_estimated_minutes', 45, 'integer');

    $branch = new Branch([
        'preparation_minutes' => 0,
        'delivery_minutes' => 0,
    ]);

    expect(OrderEstimateResolver::minutesFor($branch, OrderType::Delivery))->toBe(45)
        ->and(OrderEstimateResolver::minutesFor($branch, OrderType::Takeaway))->toBe(45);
});

test('delivery falls back when either preparation or delivery is zero', function () {
    Setting::setValue('order_estimated_minutes', 30, 'integer');

    $missingDelivery = new Branch([
        'preparation_minutes' => 12,
        'delivery_minutes' => 0,
    ]);
    $missingPrep = new Branch([
        'preparation_minutes' => null,
        'delivery_minutes' => 20,
    ]);

    expect(OrderEstimateResolver::minutesFor($missingDelivery, OrderType::Delivery))->toBe(30)
        ->and(OrderEstimateResolver::minutesFor($missingPrep, OrderType::Delivery))->toBe(30);
});

test('resolved minutes are clamped between 5 and 240', function () {
    $tooLow = new Branch([
        'preparation_minutes' => 1,
        'delivery_minutes' => 1,
    ]);
    $tooHigh = new Branch([
        'preparation_minutes' => 200,
        'delivery_minutes' => 100,
    ]);

    expect(OrderEstimateResolver::minutesFor($tooLow, OrderType::Takeaway))->toBe(5)
        ->and(OrderEstimateResolver::minutesFor($tooHigh, OrderType::Delivery))->toBe(240);
});
