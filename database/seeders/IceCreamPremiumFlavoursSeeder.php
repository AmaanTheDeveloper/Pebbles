<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Idempotent import of the 22 Ice Cream Premium Flavours products.
 *
 * Uses existing image files under public/storage/products/{29-50}/ without moving them.
 * Safe to run multiple times — keyed by product slug and variant name.
 */
class IceCreamPremiumFlavoursSeeder extends Seeder
{
    /**
     * @var list<array{
     *     name: string,
     *     slug: string,
     *     sort_order: int,
     *     short_description: string,
     *     description: string,
     *     is_featured: bool,
     *     image_matchers: list<string>
     * }>
     */
    private array $products = [
        [
            'name' => 'Vanilla',
            'slug' => 'vanilla',
            'sort_order' => 30,
            'short_description' => 'Classic creamy vanilla',
            'description' => 'Classic creamy vanilla ice cream with a smooth and rich taste.',
            'is_featured' => false,
            'image_matchers' => ['vanilla.jpeg', 'vanilla.jpg', 'vanilla.png'],
        ],
        [
            'name' => 'Vanilla Oreo',
            'slug' => 'vanilla-oreo',
            'sort_order' => 31,
            'short_description' => 'Vanilla with Oreo pieces',
            'description' => 'Creamy vanilla ice cream blended with crunchy Oreo cookie pieces.',
            'is_featured' => false,
            'image_matchers' => ['vanilla_oreo', 'vanilla oreo'],
        ],
        [
            'name' => 'Vanilla Choc-Bar',
            'slug' => 'vanilla-choc-bar',
            'sort_order' => 32,
            'short_description' => 'Vanilla with chocolate bar pieces',
            'description' => 'Smooth vanilla ice cream with delicious crunchy chocolate bar pieces.',
            'is_featured' => false,
            'image_matchers' => ['vanilla_chocbar', 'vanilla choc-bar', 'vanilla chocbar', 'chocbar'],
        ],
        [
            'name' => 'Vanilla Crunch',
            'slug' => 'vanilla-crunch',
            'sort_order' => 33,
            'short_description' => 'Vanilla with crunchy bits',
            'description' => 'Creamy vanilla ice cream packed with delightful crunchy bits.',
            'is_featured' => false,
            'image_matchers' => ['vanilla_crunch', 'vanilla crunch'],
        ],
        [
            'name' => 'Pistachio',
            'slug' => 'pistachio',
            'sort_order' => 34,
            'short_description' => 'Creamy pistachio',
            'description' => 'Rich creamy pistachio ice cream with a delicate nutty flavor.',
            'is_featured' => false,
            'image_matchers' => ['pistachio'],
        ],
        [
            'name' => 'Chocolate Chip',
            'slug' => 'chocolate-chip',
            'sort_order' => 35,
            'short_description' => 'Loaded with chocolate chips',
            'description' => 'Creamy ice cream loaded with delicious chocolate chips.',
            'is_featured' => false,
            'image_matchers' => ['chocolate chip.png', 'chocolate_chip'],
        ],
        [
            'name' => 'Strawberry Seasonal',
            'slug' => 'strawberry-seasonal',
            'sort_order' => 36,
            'short_description' => 'Refreshing strawberry',
            'description' => 'Smooth strawberry ice cream with a refreshing fruity flavor.',
            'is_featured' => false,
            'image_matchers' => ['strawberry.png'],
            'seasonal' => true,
        ],
        [
            'name' => 'Blueberry',
            'slug' => 'blueberry',
            'sort_order' => 37,
            'short_description' => 'Creamy blueberry',
            'description' => 'Creamy blueberry ice cream with a delicious fruity taste.',
            'is_featured' => false,
            'image_matchers' => ['blueberry'],
        ],
        [
            'name' => 'Pineapple',
            'slug' => 'pineapple',
            'sort_order' => 38,
            'short_description' => 'Tropical pineapple',
            'description' => 'Refreshing creamy pineapple ice cream with tropical flavor.',
            'is_featured' => false,
            'image_matchers' => ['pineapple'],
        ],
        [
            'name' => 'Tutti Frutti',
            'slug' => 'tutti-frutti',
            'sort_order' => 39,
            'short_description' => 'Candied fruit pieces',
            'description' => 'Creamy ice cream filled with colorful sweet candied fruit pieces.',
            'is_featured' => false,
            'image_matchers' => ['tutti_frutti', 'tutti frutti'],
        ],
        [
            'name' => 'Kulfa',
            'slug' => 'kulfa',
            'sort_order' => 40,
            'short_description' => 'Traditional kulfa',
            'description' => 'Rich traditional Kulfa ice cream with a creamy, nutty and aromatic taste.',
            'is_featured' => false,
            'image_matchers' => ['kulfa'],
        ],
        [
            'name' => 'Chocolate Fudge Brownie',
            'slug' => 'chocolate-fudge-brownie',
            'sort_order' => 41,
            'short_description' => 'Fudgy brownie pieces',
            'description' => 'Rich chocolate ice cream loaded with fudgy brownie pieces.',
            'is_featured' => false,
            'image_matchers' => ['chocolate fudge brownie', 'fudge brownie'],
        ],
        [
            'name' => 'Mint Chocolate Chip',
            'slug' => 'mint-chocolate-chip',
            'sort_order' => 42,
            'short_description' => 'Mint with chocolate chips',
            'description' => 'Refreshing mint ice cream with delicious chocolate chips.',
            'is_featured' => false,
            'image_matchers' => ['mint chocolate chip', 'mint_chocolate'],
        ],
        [
            'name' => 'Roasted Almond',
            'slug' => 'roasted-almond',
            'sort_order' => 43,
            'short_description' => 'Crunchy roasted almonds',
            'description' => 'Rich creamy almond ice cream filled with crunchy roasted almond pieces.',
            'is_featured' => true,
            'image_matchers' => ['roasted almond', 'roasted_almond'],
        ],
        [
            'name' => 'Strawberry Cheesecake',
            'slug' => 'strawberry-cheesecake',
            'sort_order' => 44,
            'short_description' => 'Strawberry cheesecake',
            'description' => 'Creamy strawberry ice cream with cheesecake crumbs and strawberry pieces.',
            'is_featured' => false,
            'image_matchers' => ['strawberry cheesecake', 'cheesecake'],
        ],
        [
            'name' => 'Caramel Crunch',
            'slug' => 'caramel-crunch',
            'sort_order' => 45,
            'short_description' => 'Caramel with crunch',
            'description' => 'Smooth caramel ice cream with delicious crunchy caramel pieces.',
            'is_featured' => false,
            'image_matchers' => ['caramel crunch', 'caramel_crunch'],
        ],
        [
            'name' => 'Butter Scotch',
            'slug' => 'butter-scotch',
            'sort_order' => 46,
            'short_description' => 'Butterscotch with toffee',
            'description' => 'Creamy butterscotch ice cream with crunchy toffee pieces.',
            'is_featured' => false,
            'image_matchers' => ['butterscotch', 'butter scotch'],
        ],
        [
            'name' => 'Coffee Fudge',
            'slug' => 'coffee-fudge',
            'sort_order' => 47,
            'short_description' => 'Coffee with fudge',
            'description' => 'Rich coffee ice cream combined with smooth chocolate fudge pieces.',
            'is_featured' => false,
            'image_matchers' => ['coffee fudge', 'coffee_fudge'],
        ],
        [
            'name' => 'Bounty',
            'slug' => 'bounty',
            'sort_order' => 48,
            'short_description' => 'Coconut chocolate',
            'description' => 'Creamy coconut ice cream with delicious chocolate pieces.',
            'is_featured' => false,
            'image_matchers' => ['bounty'],
        ],
        [
            'name' => 'Mango Seasonal',
            'slug' => 'mango-seasonal',
            'sort_order' => 49,
            'short_description' => 'Tropical mango',
            'description' => 'Smooth creamy mango ice cream with a refreshing tropical flavor.',
            'is_featured' => false,
            'image_matchers' => ['mango'],
            'seasonal' => true,
        ],
        [
            'name' => 'Anjeer (Figs) Seasonal',
            'slug' => 'anjeer-figs-seasonal',
            'sort_order' => 50,
            'short_description' => 'Creamy fig anjeer',
            'description' => 'Rich creamy fig ice cream with delicate pieces of dried anjeer.',
            'is_featured' => false,
            'image_matchers' => ['anjeer', 'fig'],
            'seasonal' => true,
        ],
        [
            'name' => 'Chico Seasonal',
            'slug' => 'chico-seasonal',
            'sort_order' => 51,
            'short_description' => 'Tropical chico',
            'description' => 'Creamy Chico ice cream with a naturally rich tropical fruit flavor.',
            'is_featured' => false,
            'image_matchers' => ['chico', 'sapodilla'],
            'seasonal' => true,
        ],
    ];

    /**
     * Exact variant names and prices required by the business.
     *
     * @var list<array{name: string, price: string, sort_order: int}>
     */
    private array $variants = [
        ['name' => 'Single Scoop', 'price' => '170.00', 'sort_order' => 1],
        ['name' => 'Double Scoop', 'price' => '300.00', 'sort_order' => 2],
        ['name' => 'Tripple Scoop', 'price' => '410.00', 'sort_order' => 3],
        ['name' => 'Single Cone', 'price' => '220.00', 'sort_order' => 4],
        ['name' => 'Double cone', 'price' => '350.00', 'sort_order' => 5],
        ['name' => 'Half Pack', 'price' => '450.00', 'sort_order' => 6],
        ['name' => 'Family Tub', 'price' => '880.00', 'sort_order' => 7],
    ];

    /**
     * Seasonal flavor variants (capital Double Cone + seasonal pricing).
     *
     * @var list<array{name: string, price: string, sort_order: int}>
     */
    private array $seasonalVariants = [
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
        $category = Category::query()
            ->where('name', 'Ice Cream Premium Flavours')
            ->orWhere('slug', 'ice-cream-premium-flavours')
            ->first();

        if ($category === null) {
            throw new RuntimeException('Category "Ice Cream Premium Flavours" was not found.');
        }

        $imageIndex = $this->buildImageIndex();

        // Convert the incomplete duplicate Vanilla row into Vanilla Oreo when needed.
        $duplicateVanilla = Product::query()->where('slug', 'vanilla-icecream')->first();
        if ($duplicateVanilla !== null && ! Product::query()->where('slug', 'vanilla-oreo')->exists()) {
            $duplicateVanilla->update(['slug' => 'vanilla-oreo', 'name' => 'Vanilla Oreo']);
        }

        foreach ([
            'strawberry' => 'strawberry-seasonal',
            'mango' => 'mango-seasonal',
            'anjeer-figs' => 'anjeer-figs-seasonal',
            'chico' => 'chico-seasonal',
        ] as $from => $to) {
            $legacy = Product::query()->where('slug', $from)->first();
            if ($legacy !== null && ! Product::query()->where('slug', $to)->exists()) {
                $legacy->update(['slug' => $to]);
            }
        }

        foreach ($this->products as $definition) {
            $relativePath = $this->resolveImagePath($definition['image_matchers'], $imageIndex);
            $isSeasonal = (bool) ($definition['seasonal'] ?? false);

            $product = Product::query()->firstOrNew(['slug' => $definition['slug']]);

            $product->fill([
                'category_id' => $category->id,
                'name' => $definition['name'],
                'short_description' => $definition['short_description'],
                'description' => $definition['description'],
                'is_active' => true,
                'is_available' => true,
                'is_featured' => $definition['is_featured'],
                'sort_order' => $definition['sort_order'],
            ]);

            // Preserve existing base_price for seasonal products; default 170 for new rows.
            if (! $product->exists || ! $isSeasonal) {
                $product->base_price = '170.00';
            }

            $product->save();

            if (! $isSeasonal || $product->images()->count() === 0) {
                $this->syncPrimaryImage($product, $relativePath);
            }

            $this->syncVariants($product, $isSeasonal ? $this->seasonalVariants : $this->variants);
        }

        $this->assertImport($category->id);
    }

    /**
     * @return array<string, string> lowercase basename => relative public-disk path
     */
    private function buildImageIndex(): array
    {
        $root = public_path('storage/products');
        $index = [];

        foreach (range(29, 50) as $folder) {
            $directory = $root.DIRECTORY_SEPARATOR.$folder;

            if (! is_dir($directory)) {
                continue;
            }

            foreach (File::files($directory) as $file) {
                $extension = strtolower($file->getExtension());

                if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    continue;
                }

                $basename = $file->getFilename();
                $relative = 'products/'.$folder.'/'.$basename;
                $index[Str::lower($basename)] = $relative;
            }
        }

        if ($index === []) {
            throw new RuntimeException('No ice cream images found under public/storage/products/29-50.');
        }

        return $index;
    }

    /**
     * @param  list<string>  $matchers
     * @param  array<string, string>  $imageIndex
     */
    private function resolveImagePath(array $matchers, array $imageIndex): string
    {
        foreach ($matchers as $matcher) {
            $needle = Str::lower($matcher);

            foreach ($imageIndex as $basename => $path) {
                $exact = $basename === $needle;
                $partial = str_contains($basename, $needle);

                if (! $exact && ! $partial) {
                    continue;
                }

                // Prefer exact basename matches when provided with an extension.
                if (str_contains($needle, '.') && ! $exact && $partial) {
                    continue;
                }

                if ($needle === 'chocolate chip.png' && str_contains($basename, 'mint')) {
                    continue;
                }

                if ($needle === 'strawberry.png' && str_contains($basename, 'cheesecake')) {
                    continue;
                }

                return $path;
            }
        }

        throw new RuntimeException('Unable to resolve image for matchers: '.implode(', ', $matchers));
    }

    private function syncPrimaryImage(Product $product, string $relativePath): void
    {
        $absolute = public_path('storage/'.$relativePath);

        if (! is_file($absolute)) {
            throw new RuntimeException("Missing image file: {$relativePath}");
        }

        $existingPrimary = $product->images()->where('is_primary', true)->first()
            ?? $product->images()->orderBy('sort_order')->first();

        if ($existingPrimary !== null) {
            $existingPrimary->update([
                'path' => $relativePath,
                'alt_text' => $product->name,
                'is_primary' => true,
                'sort_order' => 0,
            ]);

            $product->images()
                ->whereKeyNot($existingPrimary->id)
                ->update(['is_primary' => false]);

            return;
        }

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $relativePath,
            'alt_text' => $product->name,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }

    private function syncVariants(Product $product, ?array $variants = null): void
    {
        $variants ??= $this->variants;
        $allowedNames = collect($variants)->pluck('name')->all();
        $allowedLookup = [];

        foreach ($allowedNames as $name) {
            $allowedLookup[Str::lower($name)] = $name;
        }

        foreach ($product->variants()->get() as $existing) {
            $key = Str::lower($existing->name);

            if (! array_key_exists($key, $allowedLookup)) {
                $existing->delete();

                continue;
            }

            $canonical = $allowedLookup[$key];

            if ($existing->name !== $canonical) {
                $existing->update(['name' => $canonical]);
            }
        }

        foreach ($variants as $variant) {
            ProductVariant::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'name' => $variant['name'],
                ],
                [
                    'price' => $variant['price'],
                    'is_active' => true,
                    'sort_order' => $variant['sort_order'],
                    'product_image_id' => null,
                ],
            );
        }

        // Force exact casing when the DB collation is case-insensitive.
        foreach ($variants as $variant) {
            ProductVariant::query()
                ->where('product_id', $product->id)
                ->whereRaw('LOWER(name) = ?', [Str::lower($variant['name'])])
                ->update([
                    'name' => $variant['name'],
                    'price' => $variant['price'],
                    'is_active' => true,
                    'sort_order' => $variant['sort_order'],
                    'product_image_id' => null,
                ]);
        }
    }

    private function assertImport(int $categoryId): void
    {
        $slugs = collect($this->products)->pluck('slug');

        $products = Product::query()
            ->where('category_id', $categoryId)
            ->whereIn('slug', $slugs)
            ->withCount('variants')
            ->with('images')
            ->get();

        if ($products->count() !== 22) {
            throw new RuntimeException('Expected 22 ice cream products, found '.$products->count());
        }

        $variantTotal = $products->sum('variants_count');

        if ($variantTotal !== 154) {
            throw new RuntimeException("Expected 154 variants, found {$variantTotal}");
        }

        foreach ($products as $product) {
            if ($product->images->isEmpty()) {
                throw new RuntimeException("Product {$product->slug} has no image.");
            }

            $path = $product->images->firstWhere('is_primary', true)?->path
                ?? $product->images->first()?->path;

            if ($path === null || ! is_file(public_path('storage/'.$path))) {
                throw new RuntimeException("Product {$product->slug} image file missing: {$path}");
            }
        }
    }
}
