<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Banner;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\SiteContent;
use App\Models\User;
use App\Support\AboutContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Setting::setValue('customer_order_cancellation_enabled', true, 'boolean');
        Setting::setValue('customer_order_modification_enabled', true, 'boolean');

        $superadmin = User::query()->updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'superadmin@pebbles.test')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Pebbles Super Admin'),
                'password' => Hash::make((string) env('SUPER_ADMIN_PASSWORD', 'password')),
                'role' => UserRole::SuperAdmin,
                'branch_id' => null,
                'email_verified_at' => now(),
            ],
        );

        $customers = collect([
            ['name' => 'Ayesha Khan', 'email' => 'ayesha@example.com'],
            ['name' => 'Bilal Ahmed', 'email' => 'bilal@example.com'],
            ['name' => 'Sara Ali', 'email' => 'sara@example.com'],
        ])->map(fn (array $customer) => User::query()->updateOrCreate(
            ['email' => $customer['email']],
            [
                'name' => $customer['name'],
                'password' => Hash::make('password'),
                'role' => UserRole::User,
                'email_verified_at' => now(),
            ],
        ));

        $branch = Branch::query()->updateOrCreate(
            ['slug' => 'pebbles-dha-phase-7'],
            [
                'name' => 'Pebbles - DHA Phase 7',
                'code' => 'DHA7',
                'address' => 'DHA Phase 7, Karachi',
                'phone' => '021-111-732-253',
                'latitude' => 24.8142,
                'longitude' => 67.0682,
                'opens_at' => '11:00:00',
                'closes_at' => '23:30:00',
                'is_active' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'manager.dha@pebbles.test'],
            [
                'name' => 'DHA Manager',
                'password' => Hash::make('password'),
                'role' => UserRole::Manager,
                'branch_id' => $branch->id,
                'email_verified_at' => now(),
            ],
        );

        $zone = DeliveryZone::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'name' => 'DHA Phase 7'],
            [
                'delivery_fee' => '149.00',
                'minimum_order_amount' => '500.00',
                'estimated_minutes' => 45,
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        DeliveryZone::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Clifton'],
            [
                'delivery_fee' => '199.00',
                'minimum_order_amount' => '700.00',
                'estimated_minutes' => 55,
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        $categories = [
            'Brownies' => 'brownies',
            'Cookies' => 'cookies',
            'Drinks' => 'drinks',
        ];

        $createdCategories = [];

        foreach ($categories as $name => $slug) {
            $createdCategories[$slug] = Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => "Fresh {$name} from Pebbles.",
                    'is_active' => true,
                    'sort_order' => count($createdCategories) + 1,
                ],
            );
        }

        $extras = AddOnGroup::query()->updateOrCreate(
            ['slug' => 'extras'],
            [
                'name' => 'Extras',
                'description' => 'Optional extras for your order',
                'is_required' => false,
                'min_selections' => 0,
                'max_selections' => 5,
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        foreach (['Extra Chocolate' => '80.00', 'Oreo' => '100.00', 'Extra Cream' => '70.00'] as $name => $price) {
            AddOn::query()->updateOrCreate(
                ['add_on_group_id' => $extras->id, 'name' => $name],
                [
                    'price' => $price,
                    'is_active' => true,
                ],
            );
        }

        $brownie = Product::query()->updateOrCreate(
            ['slug' => 'classic-chocolate-brownie'],
            [
                'category_id' => $createdCategories['brownies']->id,
                'name' => 'Classic Chocolate Brownie',
                'short_description' => 'Rich fudgy brownie',
                'description' => 'Our signature chocolate brownie made fresh daily.',
                'base_price' => '499.00',
                'is_active' => true,
                'is_available' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
        );

        $cookie = Product::query()->updateOrCreate(
            ['slug' => 'double-chocolate-cookie'],
            [
                'category_id' => $createdCategories['cookies']->id,
                'name' => 'Double Chocolate Cookie',
                'short_description' => 'Loaded chocolate cookie',
                'description' => 'Soft cookie with dark chocolate chunks.',
                'base_price' => '349.00',
                'is_active' => true,
                'is_available' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
        );

        $drink = Product::query()->updateOrCreate(
            ['slug' => 'iced-chocolate'],
            [
                'category_id' => $createdCategories['drinks']->id,
                'name' => 'Iced Chocolate',
                'short_description' => 'Chilled chocolate drink',
                'description' => 'Creamy iced chocolate topped with whipped cream.',
                'base_price' => '399.00',
                'is_active' => true,
                'is_available' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
        );

        foreach ([$brownie, $cookie, $drink] as $index => $product) {
            ProductImage::query()->updateOrCreate(
                ['product_id' => $product->id, 'path' => 'products/'.$product->slug.'.jpg'],
                [
                    'alt_text' => $product->name,
                    'is_primary' => true,
                    'sort_order' => 0,
                ],
            );

            $product->addOnGroups()->syncWithoutDetaching([$extras->id]);
        }

        ProductVariant::query()->updateOrCreate(
            ['product_id' => $brownie->id, 'name' => 'Regular'],
            ['sku' => 'BRW-REG', 'price' => '499.00', 'is_active' => true, 'sort_order' => 1],
        );
        ProductVariant::query()->updateOrCreate(
            ['product_id' => $brownie->id, 'name' => 'Large'],
            ['sku' => 'BRW-LRG', 'price' => '699.00', 'is_active' => true, 'sort_order' => 2],
        );
        ProductVariant::query()->updateOrCreate(
            ['product_id' => $drink->id, 'name' => '500ml'],
            ['sku' => 'DRK-500', 'price' => '399.00', 'is_active' => true, 'sort_order' => 1],
        );
        ProductVariant::query()->updateOrCreate(
            ['product_id' => $drink->id, 'name' => '1 Liter'],
            ['sku' => 'DRK-1L', 'price' => '599.00', 'is_active' => true, 'sort_order' => 2],
        );

        $customers->each(function (User $customer) use ($zone): void {
            $customer->addresses()->updateOrCreate(
                ['label' => 'Home', 'line1' => 'House 12, Street 4'],
                [
                    'recipient_name' => $customer->name,
                    'phone' => '03001234567',
                    'area' => 'DHA Phase 7',
                    'city' => 'Karachi',
                    'delivery_zone_id' => $zone->id,
                    'is_default' => true,
                ],
            );
        });

        // Legacy sample only - banners are deprecated in favor of Homepage CMS.
        Banner::query()->updateOrCreate(
            ['title' => 'Welcome to Pebbles'],
            [
                'subtitle' => 'Brownies, cookies, and chilled drinks delivered fresh.',
                'image_path' => 'banners/welcome.jpg',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        SiteContent::query()->updateOrCreate(
            ['key' => AboutContent::SITE_CONTENT_KEY],
            ['content' => AboutContent::defaults()],
        );

        unset($superadmin);

        $this->call(GalleryItemSeeder::class);
        $this->call(HomepageSeeder::class);
    }
}
