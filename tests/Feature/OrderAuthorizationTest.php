<?php

use App\Models\Order;
use App\Models\User;

test('customers can view their own order by public order number', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson('/api/orders/'.$order->public_order_number)
        ->assertSuccessful()
        ->assertJsonPath('data.public_order_number', $order->public_order_number);
});

test('customers cannot view another customers order by public order number', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $order = Order::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->getJson('/api/orders/'.$order->public_order_number)
        ->assertForbidden();
});

test('superadmin can view any order', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $order = Order::factory()->for($owner)->create();

    $this->actingAs($admin)
        ->getJson('/api/orders/'.$order->public_order_number)
        ->assertSuccessful();
});

test('customers cannot escalate to superadmin through registration', function () {
    $this->withHeader('Origin', 'http://localhost')
        ->postJson('/api/register', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'super_admin',
        ])
        ->assertCreated()
        ->assertJsonPath('data.role', 'user');

    expect(User::query()->where('email', 'attacker@example.com')->first()?->isSuperAdmin())->toBeFalse();
});
