<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageCtaCard;
use App\Models\HomepageFeature;
use App\Models\HomepageSection;
use App\Models\HomepageShowcaseItem;
use App\Support\HomepageContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomepageController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => HomepageContent::adminPayload(),
        ]);
    }

    public function updateSection(Request $request, HomepageSection $homepageSection): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:255'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'array'],
            'settings.badge' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.heading_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.heading_accent' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.subtitle' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'settings.primary_cta_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.primary_cta_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.secondary_cta_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.secondary_cta_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.chips' => ['sometimes', 'array', 'max:8'],
            'settings.chips.*' => ['string', 'max:50'],
            'settings.hero_product_id' => ['sometimes', 'nullable', 'integer', 'exists:products,id'],
            'settings.featured_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.eyebrow' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'settings.section_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.cta_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.category_slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.tone' => ['sometimes', 'nullable', 'string', Rule::in(['white', 'cream', 'soft-green'])],
            'settings.product_limit' => ['sometimes', 'integer', 'min:1', 'max:24'],
            'settings.offer_limit' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'settings.item_limit' => ['sometimes', 'integer', 'min:1', 'max:24'],
            'settings.review_limit' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'settings.shake_cta' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.faluda_cta' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.fallback_badge' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.fallback_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.fallback_cta_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.empty_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.empty_description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'settings.share_cta_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.view_all_cta_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.empty_eyebrow' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.empty_description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'settings.card_eyebrow' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.visit_eyebrow' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.visit_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings.visit_description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'settings.directions_cta_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'hero_image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_hero_image' => ['sometimes', 'boolean'],
        ]);

        $data = collect($validated)->except(['settings', 'hero_image', 'remove_hero_image'])->all();

        if (array_key_exists('settings', $validated)) {
            $data['settings'] = array_merge(
                $homepageSection->settings ?? [],
                $validated['settings'],
            );
        }

        if ($homepageSection->key === 'hero') {
            if ($request->boolean('remove_hero_image')) {
                HomepageContent::deleteHeroImage($homepageSection->settings['hero_image_path'] ?? null);
                $data['settings'] = array_merge($data['settings'] ?? $homepageSection->settings ?? [], [
                    'hero_image_path' => null,
                ]);
            }

            if ($request->hasFile('hero_image')) {
                HomepageContent::deleteHeroImage($homepageSection->settings['hero_image_path'] ?? null);
                $data['settings'] = array_merge($data['settings'] ?? $homepageSection->settings ?? [], [
                    'hero_image_path' => HomepageContent::storeHeroImage($request->file('hero_image')),
                ]);
            }
        }

        $homepageSection->update($data);

        return response()->json([
            'data' => HomepageContent::transformSection($homepageSection->fresh(), true),
            'message' => 'Homepage section updated successfully.',
        ]);
    }

    public function reorderSections(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.key' => ['required', 'string', 'exists:homepage_sections,key'],
            'sections.*.display_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        foreach ($validated['sections'] as $sectionData) {
            HomepageSection::query()
                ->where('key', $sectionData['key'])
                ->update(['display_order' => $sectionData['display_order']]);
        }

        return response()->json([
            'data' => HomepageContent::adminPayload(),
            'message' => 'Homepage section order updated successfully.',
        ]);
    }

    public function storeFeature(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'icon_letter' => ['required', 'string', 'max:2'],
            'accent' => ['required', 'string', 'max:255'],
            'glow' => ['required', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $feature = HomepageFeature::query()->create($validated);

        return response()->json([
            'data' => HomepageContent::transformFeature($feature),
            'message' => 'Homepage feature added successfully.',
        ], 201);
    }

    public function updateFeature(Request $request, HomepageFeature $homepageFeature): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'icon_letter' => ['sometimes', 'string', 'max:2'],
            'accent' => ['sometimes', 'string', 'max:255'],
            'glow' => ['sometimes', 'string', 'max:255'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $homepageFeature->update($validated);

        return response()->json([
            'data' => HomepageContent::transformFeature($homepageFeature->fresh()),
            'message' => 'Homepage feature updated successfully.',
        ]);
    }

    public function destroyFeature(Request $request, HomepageFeature $homepageFeature): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $homepageFeature->delete();

        return response()->json([
            'message' => 'Homepage feature deleted successfully.',
        ]);
    }

    public function storeCtaCard(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'link_url' => ['required', 'string', 'max:255'],
            'tone' => ['required', 'string', Rule::in(['orange', 'green', 'white'])],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $card = HomepageCtaCard::query()->create($validated);

        return response()->json([
            'data' => HomepageContent::transformCtaCard($card),
            'message' => 'Homepage card added successfully.',
        ], 201);
    }

    public function updateCtaCard(Request $request, HomepageCtaCard $homepageCtaCard): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'link_url' => ['sometimes', 'string', 'max:255'],
            'tone' => ['sometimes', 'string', Rule::in(['orange', 'green', 'white'])],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $homepageCtaCard->update($validated);

        return response()->json([
            'data' => HomepageContent::transformCtaCard($homepageCtaCard->fresh()),
            'message' => 'Homepage card updated successfully.',
        ]);
    }

    public function destroyCtaCard(Request $request, HomepageCtaCard $homepageCtaCard): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $homepageCtaCard->delete();

        return response()->json([
            'message' => 'Homepage card deleted successfully.',
        ]);
    }

    public function storeShowcaseItem(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'pillar_key' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'blurb' => ['nullable', 'string', 'max:1000'],
            'badge' => ['nullable', 'string', 'max:255'],
            'accent' => ['required', 'string', Rule::in(['orange', 'green'])],
            'is_special' => ['sometimes', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item = HomepageShowcaseItem::query()->create($validated);

        return response()->json([
            'data' => HomepageContent::transformShowcaseItem($item->load('category')),
            'message' => 'Homepage showcase item added successfully.',
        ], 201);
    }

    public function updateShowcaseItem(Request $request, HomepageShowcaseItem $homepageShowcaseItem): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'pillar_key' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'blurb' => ['nullable', 'string', 'max:1000'],
            'badge' => ['nullable', 'string', 'max:255'],
            'accent' => ['sometimes', 'string', Rule::in(['orange', 'green'])],
            'is_special' => ['sometimes', 'boolean'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $homepageShowcaseItem->update($validated);

        return response()->json([
            'data' => HomepageContent::transformShowcaseItem($homepageShowcaseItem->fresh()->load('category')),
            'message' => 'Homepage showcase item updated successfully.',
        ]);
    }

    public function destroyShowcaseItem(Request $request, HomepageShowcaseItem $homepageShowcaseItem): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $homepageShowcaseItem->delete();

        return response()->json([
            'message' => 'Homepage showcase item deleted successfully.',
        ]);
    }
}
