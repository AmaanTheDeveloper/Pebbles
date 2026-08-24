<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Targeted update for the 4 seasonal ice cream products only.
 * Idempotent — safe to run multiple times.
 */
class SeasonalIceCreamProductsSeeder extends Seeder
{
    /**
     * @var list<array{
     *     find_slugs: list<string>,
     *     find_names: list<string>,
     *     name: string,
     *     slug: string
     * }>
     */
    private array $products = [
        [
            'find_slugs' => ['anjeer-figs', 'anjeer-figs-seasonal'],
            'find_names' => ['Anjeer (Figs)', 'Anjeer (Figs) (Seasonal)', 'Anjeer (Figs) Seasonal'],
            'name' => 'Anjeer (Figs) Seasonal',
            'slug' => 'anjeer-figs-seasonal',
        ],
        [
            'find_slugs' => ['mango', 'mango-seasonal'],
            'find_names' => ['Mango', 'Mango Seasonal'],
            'name' => 'Mango Seasonal',
            'slug' => 'mango-seasonal',
        ],
        [
            'find_slugs' => ['chico', 'chico-seasonal'],
            'find_names' => ['Chico', 'Chico Seasonal'],
            'name' => 'Chico Seasonal',
            'slug' => 'chico-seasonal',
        ],
        [
            'find_slugs' => ['strawberry', 'strawberry-seasonal'],
            'find_names' => ['Strawberry', 'Strawberry Seasonal'],
            'name' => 'Strawberry Seasonal',
            'slug' => 'strawberry-seasonal',
        ],
    ];

    /**
     * @var list<array{name: string, price: string, sort_order: int}>
     */
    private array $variants = [
        ['name' => 'Single Scoop', 'price' => '230.00', 'sort_order' => 1],
        ['name' => 'Double Scoop', 'price' => '360.00', 'sort_order' => 2],
        ['name' => 'Tripple Scoop', 'price' => '470.00', 'sort_order' => 3],
        ['name' => 'Single Cone', 'price' => '250.00', 'sort_order' => 4],
        ['name' => 'Double Cone', 'price' => '360.00', 'sort_order' => 5],
        ['name' => 'Half Pack', 'price' => '600.00', 'sort_order' => 6],
        ['name' => 'Family Tub', 'price' => '950.00', 'sort_order' => 7],
    ];

    public function run(): void
    {
        foreach ($this->products as $definition) {
            $product = Product::query()
                ->where(function ($query) use ($definition): void {
                    $query->whereIn('slug', $definition['find_slugs'])
                        ->orWhereIn('name', $definition['find_names']);
                })
                ->first();

            if ($product === null) {
                throw new RuntimeException('Seasonal product not found for slug '.$definition['slug']);
            }

            // Name + slug only — preserve base_price, category, sort, status, images.
            $product->update([
                'name' => $definition['name'],
                'slug' => $definition['slug'],
            ]);

            $this->replaceVariants($product->fresh());
        }

        $this->assertResult();
    }

    private function replaceVariants(Product $product): void
    {
        $product->variants()->delete();

        foreach ($this->variants as $variant) {
            ProductVariant::query()->create([
                'product_id' => $product->id,
                'name' => $variant['name'],
                'price' => $variant['price'],
                'is_active' => true,
                'sort_order' => $variant['sort_order'],
                'product_image_id' => null,
            ]);
        }

        // Force exact casing under case-insensitive collations.
        foreach ($this->variants as $variant) {
            ProductVariant::query()
                ->where('product_id', $product->id)
                ->whereRaw('LOWER(name) = ?', [Str::lower($variant['name'])])
                ->update([
                    'name' => $variant['name'],
                    'price' => $variant['price'],
                    'sort_order' => $variant['sort_order'],
                    'is_active' => true,
                    'product_image_id' => null,
                ]);
        }
    }

    private function assertResult(): void
    {
        $slugs = collect($this->products)->pluck('slug');
        $products = Product::query()->whereIn('slug', $slugs)->with('variants')->get();

        if ($products->count() !== 4) {
            throw new RuntimeException('Expected 4 seasonal products, found '.$products->count());
        }

        foreach ($products as $product) {
            if ($product->variants->count() !== 7) {
                throw new RuntimeException("Product {$product->slug} must have exactly 7 variants.");
            }

            foreach ($this->variants as $expected) {
                $match = $product->variants->first(
                    fn (ProductVariant $variant) => $variant->name === $expected['name']
                );

                if ($match === null || (string) $match->price !== $expected['price']) {
                    throw new RuntimeException("Variant mismatch on {$product->slug} for {$expected['name']}.");
                }
            }
        }
    }
}
