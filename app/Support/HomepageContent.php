<?php

namespace App\Support;

use App\Models\HomepageCtaCard;
use App\Models\HomepageFeature;
use App\Models\HomepageSection;
use App\Models\HomepageShowcaseItem;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class HomepageContent
{
    public static function seedIfEmpty(): void
    {
        if (HomepageSection::query()->exists()) {
            return;
        }

        foreach (HomepageDefaults::sections() as $section) {
            HomepageSection::query()->create($section);
        }

        foreach (HomepageDefaults::features() as $feature) {
            HomepageFeature::query()->create($feature);
        }

        foreach (HomepageDefaults::ctaCards() as $card) {
            HomepageCtaCard::query()->create($card);
        }

        foreach (HomepageDefaults::showcaseItems() as $item) {
            HomepageShowcaseItem::query()->create($item);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicPayload(): array
    {
        self::seedIfEmpty();

        return [
            'sections' => HomepageSection::query()->catalog()->get()->map(fn (HomepageSection $section): array => self::transformSection($section, false))->values()->all(),
            'features' => HomepageFeature::query()->catalog()->get()->map(fn (HomepageFeature $feature): array => self::transformFeature($feature))->values()->all(),
            'cta_cards' => HomepageCtaCard::query()->catalog()->get()->map(fn (HomepageCtaCard $card): array => self::transformCtaCard($card))->values()->all(),
            'showcase_items' => HomepageShowcaseItem::query()->catalog()->with('category')->get()->map(fn (HomepageShowcaseItem $item): array => self::transformShowcaseItem($item))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminPayload(): array
    {
        self::seedIfEmpty();

        $sections = HomepageSection::query()->orderBy('display_order')->orderBy('id')->get();
        $lastUpdated = collect([
            $sections->max('updated_at'),
            HomepageFeature::query()->max('updated_at'),
            HomepageCtaCard::query()->max('updated_at'),
            HomepageShowcaseItem::query()->max('updated_at'),
        ])->filter()->max();

        return [
            'summary' => [
                'active_sections' => $sections->where('is_active', true)->count(),
                'hidden_sections' => $sections->where('is_active', false)->count(),
                'feature_count' => HomepageFeature::query()->count(),
                'cta_card_count' => HomepageCtaCard::query()->count(),
                'showcase_count' => HomepageShowcaseItem::query()->count(),
                'last_updated' => $lastUpdated?->toIso8601String(),
            ],
            'sections' => $sections->map(fn (HomepageSection $section): array => self::transformSection($section, true))->values()->all(),
            'features' => HomepageFeature::query()->orderBy('display_order')->orderBy('id')->get()->map(fn (HomepageFeature $feature): array => self::transformFeature($feature))->values()->all(),
            'cta_cards' => HomepageCtaCard::query()->orderBy('display_order')->orderBy('id')->get()->map(fn (HomepageCtaCard $card): array => self::transformCtaCard($card))->values()->all(),
            'showcase_items' => HomepageShowcaseItem::query()->with('category')->orderBy('display_order')->orderBy('id')->get()->map(fn (HomepageShowcaseItem $item): array => self::transformShowcaseItem($item))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function transformSection(HomepageSection $section, bool $includeMeta = true): array
    {
        $settings = self::normalizeSectionSettings($section->key, $section->settings ?? []);

        if ($section->key === 'hero' && ! empty($settings['hero_image_path'])) {
            $settings['hero_image'] = [
                'url' => PublicMediaUrl::fromPath($settings['hero_image_path']),
                'path' => $settings['hero_image_path'],
            ];
        }

        if ($section->key === 'hero' && ! empty($settings['hero_product_id'])) {
            $product = Product::query()->catalog()->with(['images', 'category'])->find($settings['hero_product_id']);
            if ($product) {
                $settings['hero_product'] = self::transformProductSummary($product);
            }
        }

        $payload = [
            'key' => $section->key,
            'label' => $section->label,
            'display_order' => $section->display_order,
            'is_active' => $section->is_active,
            'settings' => $settings,
        ];

        if ($includeMeta) {
            $payload['updated_at'] = $section->updated_at?->toIso8601String();
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function transformFeature(HomepageFeature $feature): array
    {
        return [
            'id' => $feature->id,
            'title' => $feature->title,
            'description' => $feature->description,
            'icon_letter' => $feature->icon_letter,
            'accent' => $feature->accent,
            'glow' => $feature->glow,
            'display_order' => $feature->display_order,
            'is_active' => $feature->is_active,
            'updated_at' => $feature->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function transformCtaCard(HomepageCtaCard $card): array
    {
        return [
            'id' => $card->id,
            'title' => $card->title,
            'description' => $card->description,
            'link_url' => $card->link_url,
            'tone' => $card->tone,
            'display_order' => $card->display_order,
            'is_active' => $card->is_active,
            'updated_at' => $card->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function transformShowcaseItem(HomepageShowcaseItem $item): array
    {
        return [
            'id' => $item->id,
            'pillar_key' => $item->pillar_key,
            'category_id' => $item->category_id,
            'category' => $item->category ? [
                'id' => $item->category->id,
                'name' => $item->category->name,
                'slug' => $item->category->slug,
            ] : null,
            'label' => $item->label,
            'blurb' => $item->blurb,
            'badge' => $item->badge,
            'accent' => $item->accent,
            'is_special' => $item->is_special,
            'display_order' => $item->display_order,
            'is_active' => $item->is_active,
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array<string, mixed>
     */
    public static function normalizeSectionSettings(string $key, ?array $settings): array
    {
        $defaults = collect(HomepageDefaults::sections())
            ->firstWhere('key', $key)['settings'] ?? [];

        return array_merge($defaults, array_filter($settings ?? [], fn ($value): bool => $value !== null));
    }

    /**
     * @return array<string, mixed>
     */
    public static function transformProductSummary(Product $product): array
    {
        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'short_description' => $product->short_description,
            'image' => $primaryImage ? [
                'url' => PublicMediaUrl::fromPath($primaryImage->path),
                'alt_text' => $primaryImage->alt_text ?: $product->name,
            ] : null,
        ];
    }

    public static function storeHeroImage(UploadedFile $file): string
    {
        return $file->store('homepage', 'public');
    }

    public static function deleteHeroImage(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'homepage/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
