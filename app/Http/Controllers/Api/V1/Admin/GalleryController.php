<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGalleryItemRequest;
use App\Http\Requests\Admin\UpdateGalleryItemRequest;
use App\Models\GalleryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();
        $status = $request->string('status')->toString();
        $sort = $request->string('sort')->toString();

        $items = GalleryItem::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when(
                $category !== '' && $category !== 'All',
                fn ($query) => $query->where('category', $category),
            )
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($sort === 'oldest', fn ($query) => $query->orderBy('id'))
            ->when($sort === 'title', fn ($query) => $query->orderBy('title'))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('id'))
            ->when(! in_array($sort, ['oldest', 'title', 'newest'], true), fn ($query) => $query->orderBy('display_order')->orderByDesc('id'))
            ->paginate(24);

        return response()->json([
            'data' => $items->getCollection()->map(fn (GalleryItem $item): array => $this->transform($item))->values(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
            'links' => [
                'first' => $items->url(1),
                'last' => $items->url($items->lastPage()),
                'prev' => $items->previousPageUrl(),
                'next' => $items->nextPageUrl(),
            ],
        ]);
    }

    public function store(StoreGalleryItemRequest $request): JsonResponse
    {
        $data = collect($request->validated())->except('image')->all();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeGalleryImage($request->file('image'));
        }

        $item = GalleryItem::query()->create($data);

        return response()->json([
            'data' => $this->transform($item),
            'message' => 'Gallery image added successfully.',
        ], 201);
    }

    public function show(GalleryItem $galleryItem): JsonResponse
    {
        return response()->json(['data' => $this->transform($galleryItem)]);
    }

    public function update(UpdateGalleryItemRequest $request, GalleryItem $galleryItem): JsonResponse
    {
        $data = collect($request->validated())->except('image')->all();

        if ($request->hasFile('image')) {
            $previousPath = $galleryItem->image_path;
            $data['image_path'] = $this->storeGalleryImage($request->file('image'));
            $this->deleteStoredGalleryImage($previousPath);
        }

        $galleryItem->update($data);

        return response()->json([
            'data' => $this->transform($galleryItem->fresh()),
            'message' => 'Gallery image updated successfully.',
        ]);
    }

    public function destroy(GalleryItem $galleryItem): JsonResponse
    {
        $path = $galleryItem->image_path;
        $galleryItem->delete();
        $this->deleteStoredGalleryImage($path);

        return response()->json([
            'message' => 'Gallery image deleted successfully.',
        ]);
    }

    public function toggleStatus(Request $request, GalleryItem $galleryItem): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $galleryItem->update($validated);

        return response()->json([
            'data' => $this->transform($galleryItem->fresh()),
            'message' => $validated['is_active']
                ? 'Gallery image activated successfully.'
                : 'Gallery image hidden successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(GalleryItem $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'category' => $item->category->value,
            'display_order' => $item->display_order,
            'is_active' => $item->is_active,
            'gradient' => $item->gradient,
            'alt_text' => $item->alt_text,
            'legacy_key' => $item->legacy_key,
            'image' => [
                'url' => $item->imageUrl(),
                'path' => $item->image_path,
            ],
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    private function storeGalleryImage(UploadedFile $file): string
    {
        return $file->store('gallery', 'public');
    }

    private function deleteStoredGalleryImage(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'gallery/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
