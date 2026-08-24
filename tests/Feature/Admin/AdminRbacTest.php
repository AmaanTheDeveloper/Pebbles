<?php

use App\Enums\AdminNotificationType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\OrderPlaced;
use App\Models\AdminNotification;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('manager can access admin ping and dashboard but not users', function () {
    $branch = Branch::factory()->create(['is_active' => true]);
    $manager = User::factory()->manager($branch)->create();

    $this->actingAs($manager)
        ->getJson('/api/admin/ping')
        ->assertSuccessful();

    $this->actingAs($manager)
        ->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.branch_id', $branch->id);

    $this->actingAs($manager)
        ->getJson('/api/v1/admin/users')
        ->assertForbidden();

    expect(Gate::forUser($manager)->allows('access-admin'))->toBeTrue()
        ->and(Gate::forUser($manager)->denies('manage-users'))->toBeTrue();
});

test('manager only sees orders for their assigned branch', function () {
    $dha = Branch::factory()->create(['is_active' => true, 'name' => 'DHA']);
    $gulberg = Branch::factory()->create(['is_active' => true, 'name' => 'Gulberg']);
    $manager = User::factory()->manager($dha)->create();

    $dhaOrder = Order::factory()->create([
        'branch_id' => $dha->id,
        'status' => OrderStatus::Pending,
        'public_order_number' => 'PB-DHA-1',
    ]);
    Order::factory()->create([
        'branch_id' => $gulberg->id,
        'status' => OrderStatus::Pending,
        'public_order_number' => 'PB-GUL-1',
    ]);

    $response = $this->actingAs($manager)
        ->getJson('/api/v1/admin/orders')
        ->assertSuccessful();

    $numbers = collect($response->json('data'))->pluck('order_number')->all();

    expect($numbers)->toContain('PB-DHA-1')
        ->and($numbers)->not->toContain('PB-GUL-1');

    $this->actingAs($manager)
        ->getJson('/api/v1/admin/orders/PB-GUL-1')
        ->assertForbidden();

    $this->actingAs($manager)
        ->getJson('/api/v1/admin/orders/'.$dhaOrder->public_order_number)
        ->assertSuccessful();
});

test('super admin can assign manager role with required branch', function () {
    $admin = User::factory()->superAdmin()->create();
    $branch = Branch::factory()->create(['is_active' => true]);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/users/'.$user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => UserRole::Manager->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['branch_id']);

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/users/'.$user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => UserRole::Manager->value,
            'branch_id' => $branch->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.role', UserRole::Manager->value)
        ->assertJsonPath('data.branch_id', $branch->id);

    expect($user->fresh()->isManager())->toBeTrue()
        ->and($user->fresh()->branch_id)->toBe($branch->id);
});

test('demoting manager to user clears branch assignment', function () {
    $admin = User::factory()->superAdmin()->create();
    $branch = Branch::factory()->create(['is_active' => true]);
    $manager = User::factory()->manager($branch)->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/users/'.$manager->id, [
            'name' => $manager->name,
            'email' => $manager->email,
            'role' => UserRole::User->value,
            'branch_id' => $branch->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.role', UserRole::User->value)
        ->assertJsonPath('data.branch_id', null);
});

test('cannot demote the last remaining super admin', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/users/'.$admin->id, [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => UserRole::User->value,
        ])
        ->assertUnprocessable();

    expect($admin->fresh()->isSuperAdmin())->toBeTrue();
});

test('branch managers receive notifications for their branch orders', function () {
    $branch = Branch::factory()->create(['is_active' => true]);
    $otherBranch = Branch::factory()->create(['is_active' => true]);
    $admin = User::factory()->superAdmin()->create();
    $manager = User::factory()->manager($branch)->create();
    $otherManager = User::factory()->manager($otherBranch)->create();
    $customer = User::factory()->create();

    $order = Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'public_order_number' => 'PB-MGR-1',
    ]);
    Payment::factory()->for($order)->create();

    event(new OrderPlaced($order->fresh(['branch', 'user', 'payment'])));

    expect(AdminNotification::query()
        ->where('recipient_user_id', $manager->id)
        ->where('type', AdminNotificationType::OrderCreated)
        ->exists())->toBeTrue()
        ->and(AdminNotification::query()
            ->where('recipient_user_id', $otherManager->id)
            ->where('type', AdminNotificationType::OrderCreated)
            ->exists())->toBeFalse()
        ->and(AdminNotification::query()
            ->where('recipient_user_id', $admin->id)
            ->where('type', AdminNotificationType::OrderCreated)
            ->exists())->toBeTrue();
});

test('regular users still cannot access admin routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/admin/orders')
        ->assertForbidden();
});
