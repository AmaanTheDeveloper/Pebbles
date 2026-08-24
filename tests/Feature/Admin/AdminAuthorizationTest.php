<?php

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;

test('guest cannot access admin dashboard api', function () {
    $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
});

test('customer cannot access admin dashboard or mutations', function () {
    $customer = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($customer)
        ->getJson('/api/v1/admin/dashboard')
        ->assertForbidden();

    $this->actingAs($customer)
        ->getJson('/api/v1/admin/orders')
        ->assertForbidden();

    $this->actingAs($customer)
        ->postJson('/api/v1/admin/categories', [
            'name' => 'Hacked',
            'slug' => 'hacked',
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->patchJson('/api/v1/admin/settings', [
            'order_cancellation_minutes' => 10,
        ])
        ->assertForbidden();
});

test('customer cannot verify payments via admin payment endpoints', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create();

    $this->actingAs($customer)
        ->postJson('/api/v1/admin/orders/'.$order->public_order_number.'/payment/verify')
        ->assertForbidden();

    $this->actingAs($customer)
        ->postJson('/api/v1/admin/orders/'.$order->public_order_number.'/payment/reject')
        ->assertForbidden();
});

test('superadmin can access admin dashboard', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'orders_today',
                'pending_orders',
                'preparing_orders',
                'ready_orders',
                'pending_payment_verification',
                'sales_today',
                'active_products',
            ],
        ]);
});
