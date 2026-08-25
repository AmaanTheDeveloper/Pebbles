<?php

use App\Enums\CouponDiscountType;
use App\Models\Branch;
use App\Models\Coupon;
use App\Models\User;

test('super admin can create a coupon', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/coupons', [
            'code' => 'pebbles100',
            'discount_type' => CouponDiscountType::Fixed->value,
            'discount_value' => 100,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'PEBBLES100')
        ->assertJsonPath('data.discount_type', 'fixed')
        ->assertJsonPath('data.status', 'Active');

    expect(Coupon::query()->where('code', 'PEBBLES100')->exists())->toBeTrue();
});

test('manager cannot create coupons', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->postJson('/api/v1/admin/coupons', [
            'code' => 'MANAGER',
            'discount_type' => CouponDiscountType::Fixed->value,
            'discount_value' => 50,
        ])
        ->assertForbidden();
});

test('normal user cannot access coupon management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/admin/coupons')
        ->assertForbidden();
});

test('super admin can list filter and deactivate coupons', function () {
    $admin = User::factory()->superAdmin()->create();
    Coupon::factory()->fixed(100)->create(['code' => 'ACTIVE1', 'is_active' => true]);
    Coupon::factory()->percentage(10)->create(['code' => 'PCT10', 'is_active' => false]);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/coupons?search=ACTIVE&status=active')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'ACTIVE1');

    $coupon = Coupon::query()->where('code', 'ACTIVE1')->first();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/coupons/'.$coupon->id, [
            'is_active' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.status', 'Inactive');
});

test('percentage coupon validation rejects values over 100', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/coupons', [
            'code' => 'BADPCT',
            'discount_type' => CouponDiscountType::Percentage->value,
            'discount_value' => 150,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['discount_value']);
});

test('coupon with usage history is soft deleted instead of force deleted', function () {
    $admin = User::factory()->superAdmin()->create();
    $coupon = Coupon::factory()->fixed(50)->create([
        'code' => 'USED50',
        'usage_count' => 1,
    ]);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/coupons/'.$coupon->id)
        ->assertNoContent();

    expect(Coupon::withTrashed()->find($coupon->id))->not->toBeNull()
        ->and(Coupon::withTrashed()->find($coupon->id)->trashed())->toBeTrue()
        ->and(Coupon::withTrashed()->find($coupon->id)->is_active)->toBeFalse();
});

test('branch-scoped coupon can be created for a specific branch', function () {
    $admin = User::factory()->superAdmin()->create();
    $branch = Branch::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/coupons', [
            'code' => 'BRANCH1',
            'discount_type' => CouponDiscountType::Fixed->value,
            'discount_value' => 75,
            'branch_id' => $branch->id,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.branch_id', $branch->id);
});
