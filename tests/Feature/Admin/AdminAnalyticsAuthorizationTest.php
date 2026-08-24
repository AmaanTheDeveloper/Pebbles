<?php

use App\Models\User;

test('guest cannot access analytics overview', function () {
    $this->getJson('/api/v1/admin/analytics/overview')->assertUnauthorized();
});

test('customer cannot access analytics endpoints', function () {
    $customer = User::factory()->create();

    foreach ([
        '/api/v1/admin/analytics/overview',
        '/api/v1/admin/analytics/sales',
        '/api/v1/admin/analytics/orders',
        '/api/v1/admin/analytics/products',
        '/api/v1/admin/analytics/categories',
        '/api/v1/admin/analytics/branches',
        '/api/v1/admin/analytics/customers',
        '/api/v1/admin/analytics/payment-methods',
        '/api/v1/admin/analytics/delivery-zones',
        '/api/v1/admin/analytics/export',
    ] as $path) {
        $this->actingAs($customer)->getJson($path)->assertForbidden();
    }
});

test('superadmin can access analytics overview', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/overview?preset=last_30_days')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'kpis' => [
                    'total_revenue',
                    'net_revenue',
                    'total_orders',
                    'average_order_value',
                    'successful_payments',
                    'cancelled_orders',
                    'rejected_payments',
                ],
                'comparison',
                'order_status_distribution',
                'payment_status_distribution',
            ],
        ]);
});

test('invalid analytics filters are rejected', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/overview?preset=custom')
        ->assertUnprocessable();

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/products?product_sort=hack')
        ->assertUnprocessable();
});
