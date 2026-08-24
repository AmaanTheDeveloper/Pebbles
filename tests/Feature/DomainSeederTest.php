<?php

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('database seeder creates the expected foundation data', function () {
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder']);

    expect(Setting::getValue('customer_order_cancellation_enabled', true))->toBeTrue()
        ->and(User::query()->where('email', 'superadmin@pebbles.test')->first()?->role)->toBe(UserRole::SuperAdmin)
        ->and(User::query()->where('role', UserRole::User)->count())->toBeGreaterThanOrEqual(3)
        ->and(Branch::query()->where('slug', 'pebbles-dha-phase-7')->exists())->toBeTrue()
        ->and(Category::query()->count())->toBeGreaterThanOrEqual(3)
        ->and(Product::query()->count())->toBeGreaterThanOrEqual(3);
});
