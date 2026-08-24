<?php

use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;

test('manager customer analytics total_customers is scoped to assigned branch', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'UTC'));

    $dha = Branch::factory()->create(['is_active' => true, 'name' => 'DHA']);
    $gulberg = Branch::factory()->create(['is_active' => true, 'name' => 'Gulberg']);
    $manager = User::factory()->manager($dha)->create();

    $dhaCustomer = User::factory()->create();
    $gulbergCustomer = User::factory()->create();
    User::factory()->create();

    Order::factory()->for($dhaCustomer)->create([
        'branch_id' => $dha->id,
        'status' => OrderStatus::Completed,
        'created_at' => '2026-08-10 10:00:00',
    ]);

    Order::factory()->for($gulbergCustomer)->create([
        'branch_id' => $gulberg->id,
        'status' => OrderStatus::Completed,
        'created_at' => '2026-08-10 10:00:00',
    ]);

    $this->actingAs($manager)
        ->getJson('/api/v1/admin/analytics/customers?preset=custom&from=2026-08-01&to=2026-08-15')
        ->assertSuccessful()
        ->assertJsonPath('data.kpis.total_customers', 1)
        ->assertJsonPath('data.kpis.customers_with_orders', 1);
});

test('super admin customer analytics can remain global without branch filter', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'UTC'));

    $admin = User::factory()->superAdmin()->create();
    $branchA = Branch::factory()->create(['is_active' => true]);
    $branchB = Branch::factory()->create(['is_active' => true]);

    $customerA = User::factory()->create();
    $customerB = User::factory()->create();
    User::factory()->create();

    Order::factory()->for($customerA)->create([
        'branch_id' => $branchA->id,
        'status' => OrderStatus::Completed,
        'created_at' => '2026-08-10 10:00:00',
    ]);

    Order::factory()->for($customerB)->create([
        'branch_id' => $branchB->id,
        'status' => OrderStatus::Completed,
        'created_at' => '2026-08-10 10:00:00',
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/analytics/customers?preset=custom&from=2026-08-01&to=2026-08-15')
        ->assertSuccessful()
        ->assertJsonPath('data.kpis.total_customers', 3);
});
