<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Policies\OrderPolicy;

test('order policy denies customer cancel and modify after confirmation', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Confirmed,
    ]);

    $policy = new OrderPolicy;

    expect($policy->cancel($customer, $order))->toBeFalse()
        ->and($policy->modify($customer, $order))->toBeFalse();
});

test('order policy allows customer cancel and modify while pending', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Pending,
    ]);

    $policy = new OrderPolicy;

    expect($policy->cancel($customer, $order))->toBeTrue()
        ->and($policy->modify($customer, $order))->toBeTrue();
});
