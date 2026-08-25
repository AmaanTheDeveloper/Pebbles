<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OfferScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfferRequest;
use App\Http\Requests\Admin\UpdateOfferRequest;
use App\Models\Offer;
use App\Support\ProductOfferPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OfferController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();

        $offers = Offer::query()
            ->with(['categories:id,name', 'products:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('short_description', 'like', '%'.$search.'%');
                });
            })
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        $offers->setCollection(
            $offers->getCollection()->map(fn (Offer $offer): array => $this->transformOffer($offer))
        );

        return JsonResource::collection($offers);
    }

    public function store(StoreOfferRequest $request): JsonResponse
    {
        $data = collect($request->validated())->except(['image', 'category_ids', 'product_ids'])->all();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeOfferImage($request->file('image'));
        }

        $offer = Offer::query()->create($data);
        $this->syncOfferTargets($offer, $request->validated());
        ProductOfferPricing::flush();

        return response()->json(['data' => $this->transformOffer($offer->fresh(['categories:id,name', 'products:id,name']))], 201);
    }

    public function show(Offer $offer): JsonResponse
    {
        $offer->load(['categories:id,name', 'products:id,name']);

        return response()->json(['data' => $this->transformOffer($offer)]);
    }

    public function update(UpdateOfferRequest $request, Offer $offer): JsonResponse
    {
        $validated = $request->validated();
        $data = collect($validated)->except(['image', 'category_ids', 'product_ids'])->all();

        if ($request->hasFile('image')) {
            $previousPath = $offer->image_path;
            $data['image_path'] = $this->storeOfferImage($request->file('image'));
            $this->deleteStoredOfferImage($previousPath);
        }

        $offer->update($data);
        $this->syncOfferTargets($offer, $validated, partial: true);
        ProductOfferPricing::flush();

        return response()->json(['data' => $this->transformOffer($offer->fresh(['categories:id,name', 'products:id,name']))]);
    }

    public function destroy(Offer $offer): JsonResponse
    {
        $path = $offer->image_path;
        $offer->delete();
        $this->deleteStoredOfferImage($path);
        ProductOfferPricing::flush();

        return response()->json(null, 204);
    }

    private function storeOfferImage(UploadedFile $file): string
    {
        return $file->store('offers', 'public');
    }

    private function deleteStoredOfferImage(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'offers/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncOfferTargets(Offer $offer, array $validated, bool $partial = false): void
    {
        if (
            $partial
            && ! array_key_exists('scope', $validated)
            && ! array_key_exists('category_ids', $validated)
            && ! array_key_exists('product_ids', $validated)
        ) {
            return;
        }

        $scope = $offer->scope ?? OfferScope::EntireStore;

        if ($scope === OfferScope::EntireStore) {
            $offer->categories()->sync([]);
            $offer->products()->sync([]);

            return;
        }

        if ($scope === OfferScope::Categories) {
            $offer->products()->sync([]);
            if (! $partial || array_key_exists('category_ids', $validated) || array_key_exists('scope', $validated)) {
                $offer->categories()->sync($validated['category_ids'] ?? []);
            }

            return;
        }

        if ($scope === OfferScope::Products) {
            $offer->categories()->sync([]);
            if (! $partial || array_key_exists('product_ids', $validated) || array_key_exists('scope', $validated)) {
                $offer->products()->sync($validated['product_ids'] ?? []);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function transformOffer(Offer $offer): array
    {
        $offer->loadMissing(['categories:id,name', 'products:id,name']);

        return [
            'id' => $offer->id,
            'title' => $offer->title,
            'type' => $offer->type?->value,
            'type_label' => $offer->type?->label(),
            'short_description' => $offer->short_description,
            'description' => $offer->description,
            'image_path' => $offer->image_path,
            'image' => $offer->imageUrl() === null ? null : [
                'url' => $offer->imageUrl(),
            ],
            'offer_value' => $offer->offer_value,
            'scope' => $offer->scope?->value ?? OfferScope::EntireStore->value,
            'scope_label' => $offer->scope?->label() ?? OfferScope::EntireStore->label(),
            'maximum_discount_amount' => $offer->maximum_discount_amount,
            'category_ids' => $offer->categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'product_ids' => $offer->products->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'categories' => $offer->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values()->all(),
            'products' => $offer->products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
            ])->values()->all(),
            'cta_label' => $offer->cta_label,
            'cta_url' => $offer->cta_url,
            'starts_at' => $offer->starts_at?->toIso8601String(),
            'ends_at' => $offer->ends_at?->toIso8601String(),
            'is_active' => $offer->is_active,
            'sort_order' => $offer->sort_order,
            'created_at' => $offer->created_at?->toIso8601String(),
            'updated_at' => $offer->updated_at?->toIso8601String(),
        ];
    }
}
