<?php

use App\Models\Branch;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\User;

test('superadmin can manage branches and catalog reflects availability', function () {
    $admin = User::factory()->superAdmin()->create();

    $branch = $this->actingAs($admin)
        ->postJson('/api/v1/admin/branches', [
            'name' => 'Gulberg',
            'slug' => 'gulberg',
            'address' => 'Main Blvd',
            'is_active' => true,
        ])
        ->assertCreated()
        ->json('data');

    $this->getJson('/api/v1/catalog/branches')
        ->assertSuccessful()
        ->assertJsonFragment(['slug' => 'gulberg']);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/branches/'.$branch['id'], [
            'is_active' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $slugs = collect($this->getJson('/api/v1/catalog/branches')->json('data'))->pluck('slug');
    expect($slugs)->not->toContain('gulberg');
});

test('delivery zone fee changes do not rewrite historical order fees', function () {
    $admin = User::factory()->superAdmin()->create();
    $branch = Branch::factory()->create(['is_active' => true]);
    $zone = DeliveryZone::factory()->for($branch)->create([
        'name' => 'Zone A',
        'delivery_fee' => '100.00',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'branch_id' => $branch->id,
        'delivery_zone_id' => $zone->id,
        'delivery_zone_name' => 'Zone A',
        'delivery_fee' => '100.00',
        'grand_total' => '1100.00',
    ]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/delivery-zones/'.$zone->id, [
            'delivery_fee' => '250.00',
            'name' => 'Zone A Plus',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.delivery_fee', '250.00');

    $order->refresh();

    expect($order->delivery_fee)->toBe('100.00')
        ->and($order->delivery_zone_name)->toBe('Zone A');

    $this->getJson('/api/v1/catalog/delivery-zones')
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'Zone A Plus', 'delivery_fee' => '250.00']);
});

test('superadmin can update operational settings', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/settings')
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['customer_order_cancellation_enabled', 'customer_order_modification_enabled']]);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings', [
            'customer_order_cancellation_enabled' => false,
            'customer_order_modification_enabled' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.customer_order_cancellation_enabled', false)
        ->assertJsonPath('data.customer_order_modification_enabled', true);
});

test('customer cannot manage branches or delivery zones', function () {
    $customer = User::factory()->create();
    $branch = Branch::factory()->create();
    $zone = DeliveryZone::factory()->for($branch)->create();

    $this->actingAs($customer)
        ->postJson('/api/v1/admin/branches', [
            'name' => 'Hack',
            'slug' => 'hack',
            'address' => 'Nowhere',
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->patchJson('/api/v1/admin/delivery-zones/'.$zone->id, [
            'delivery_fee' => '1.00',
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->deleteJson('/api/v1/admin/branches/'.$branch->id)
        ->assertForbidden();
});
