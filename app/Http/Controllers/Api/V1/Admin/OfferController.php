<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfferRequest;
use App\Http\Requests\Admin\UpdateOfferRequest;
use App\Models\Offer;
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
        $data = collect($request->validated())->except('image')->all();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeOfferImage($request->file('image'));
        }

        $offer = Offer::query()->create($data);

        return response()->json(['data' => $this->transformOffer($offer)], 201);
    }

    public function show(Offer $offer): JsonResponse
    {
        return response()->json(['data' => $this->transformOffer($offer)]);
    }

    public function update(UpdateOfferRequest $request, Offer $offer): JsonResponse
    {
        $data = collect($request->validated())->except('image')->all();

        if ($request->hasFile('image')) {
            $previousPath = $offer->image_path;
            $data['image_path'] = $this->storeOfferImage($request->file('image'));
            $this->deleteStoredOfferImage($previousPath);
        }

        $offer->update($data);

        return response()->json(['data' => $this->transformOffer($offer->fresh())]);
    }

    public function destroy(Offer $offer): JsonResponse
    {
        $path = $offer->image_path;
        $offer->delete();
        $this->deleteStoredOfferImage($path);

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
     * @return array<string, mixed>
     */
    private function transformOffer(Offer $offer): array
    {
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
