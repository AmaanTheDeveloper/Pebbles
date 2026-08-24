<?php

namespace App\Support;

use App\Enums\GalleryCategory;
use App\Models\GalleryItem;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductImage;

class GalleryContentImporter
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildLegacyItems(): array
    {
        $items = [];
        $displayOrder = 0;
        $usedImageUrls = [];

        $products = Product::query()
            ->catalog()
            ->with(['category', 'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
            ->get();

        foreach ($products as $product) {
            /** @var ProductImage|null $image */
            $image = $product->images->first();

            if ($image === null) {
                continue;
            }

            $usedImageUrls[] = $image->path;
            $items[] = $this->itemPayload(
                legacyKey: 'product-'.$product->id,
                title: $product->name,
                description: $product->short_description ?: ($product->category?->name ? 'From the Pebbles menu.' : null),
                category: $this->categorizeProduct($product),
                imagePath: $image->path,
                altText: $image->alt_text ?: $product->name,
                displayOrder: $displayOrder++,
            );
        }

        $offers = Offer::query()->currentlyAvailable()->get();

        foreach ($offers as $offer) {
            if (! $offer->image_path) {
                continue;
            }

            $items[] = $this->itemPayload(
                legacyKey: 'offer-'.$offer->id,
                title: $offer->title,
                description: $offer->short_description ?: 'A special Pebbles moment.',
                category: GalleryCategory::Events,
                imagePath: $offer->image_path,
                altText: $offer->title,
                displayOrder: $displayOrder++,
            );
        }

        foreach ($this->interiorPlaceholders() as $placeholder) {
            $items[] = $this->itemPayload(
                legacyKey: $placeholder['legacy_key'],
                title: $placeholder['title'],
                description: $placeholder['description'],
                category: GalleryCategory::Interior,
                gradient: $placeholder['gradient'],
                displayOrder: $displayOrder++,
            );
        }

        $atmosphereCandidates = $products->filter(
            fn (Product $product): bool => $product->is_featured
                && $this->categorizeProduct($product) === GalleryCategory::Food
                && $product->images->first()?->path,
        );

        foreach ($atmosphereCandidates->take(4)->values() as $index => $product) {
            /** @var ProductImage $image */
            $image = $product->images->first();

            $items[] = $this->itemPayload(
                legacyKey: 'atmosphere-'.$product->id,
                title: $index === 0 ? 'Pebbles Vibes' : 'Shared moments · '.$product->name,
                description: 'Good food, good drinks, and memories made together.',
                category: GalleryCategory::Atmosphere,
                imagePath: $image->path,
                altText: $image->alt_text ?: $product->name,
                displayOrder: $displayOrder++,
            );
        }

        if (! collect($items)->contains(fn (array $item): bool => $item['category'] === GalleryCategory::Atmosphere)) {
            $fallback = $products->first(
                fn (Product $product): bool => $product->images->first()?->path
                    && ! in_array($product->images->first()->path, $usedImageUrls, true),
            );

            if ($fallback !== null) {
                /** @var ProductImage $image */
                $image = $fallback->images->first();

                $items[] = $this->itemPayload(
                    legacyKey: 'atmosphere-fallback-'.$fallback->id,
                    title: 'The Pebbles Experience',
                    description: 'Relaxed, refreshing, and made for good company.',
                    category: GalleryCategory::Atmosphere,
                    imagePath: $image->path,
                    altText: $image->alt_text ?: $fallback->name,
                    displayOrder: $displayOrder++,
                );
            }
        }

        return $items;
    }

    public function importIfEmpty(): int
    {
        if (GalleryItem::query()->exists()) {
            return 0;
        }

        $created = 0;

        foreach ($this->buildLegacyItems() as $payload) {
            GalleryItem::query()->create($payload);
            $created++;
        }

        return $created;
    }

    private function categorizeProduct(Product $product): GalleryCategory
    {
        $slug = $product->category?->slug ?? '';
        $atmosphereSlugs = ['limca-flavors', 'gola-ganda', 'coffee'];
        $foodSlugs = ['ice-cream-premium-flavours', 'ice-cream-shakes', 'falooda', 'gol-gappay', 'channa-chaat'];

        if (in_array($slug, $atmosphereSlugs, true)) {
            return GalleryCategory::Atmosphere;
        }

        if (in_array($slug, $foodSlugs, true)) {
            return GalleryCategory::Food;
        }

        $name = $product->name ?? '';

        if (preg_match('/kulfi|kulfa|shake|faluda|falooda|ice cream|scoops/i', $name)) {
            return GalleryCategory::Food;
        }

        if (preg_match('/limca|refresh|drink|gola|coffee/i', $name)) {
            return GalleryCategory::Atmosphere;
        }

        if ($product->is_featured) {
            return GalleryCategory::Atmosphere;
        }

        return GalleryCategory::Food;
    }

    /**
     * @return list<array{legacy_key: string, title: string, description: string, gradient: string}>
     */
    private function interiorPlaceholders(): array
    {
        return [
            [
                'legacy_key' => 'interior-counter',
                'title' => 'Pebbles Counter',
                'description' => 'A modern service counter built for friendly, fast orders.',
                'gradient' => 'from-brand-green to-brand-green-light',
            ],
            [
                'legacy_key' => 'interior-seating',
                'title' => 'Cozy Seating',
                'description' => 'Comfortable spots to linger over shakes and scoops.',
                'gradient' => 'from-brand-green-light/90 to-brand-cream',
            ],
            [
                'legacy_key' => 'interior-decor',
                'title' => 'Warm Decor',
                'description' => 'Fresh greens and playful accents throughout the space.',
                'gradient' => 'from-brand-orange to-[#ea580c]',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemPayload(
        string $legacyKey,
        string $title,
        ?string $description,
        GalleryCategory $category,
        ?string $imagePath = null,
        ?string $altText = null,
        ?string $gradient = null,
        int $displayOrder = 0,
    ): array {
        return [
            'legacy_key' => $legacyKey,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'image_path' => $imagePath,
            'alt_text' => $altText,
            'gradient' => $gradient,
            'display_order' => $displayOrder,
            'is_active' => true,
        ];
    }
}
