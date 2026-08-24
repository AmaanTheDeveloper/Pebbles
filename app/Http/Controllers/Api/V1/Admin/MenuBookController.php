<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMenuBookPageRequest;
use App\Http\Requests\Admin\UpdateMenuBookPageRequest;
use App\Models\MenuBookPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MenuBookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $sort = $request->string('sort')->toString();

        $pages = MenuBookPage::query()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($sort === 'oldest', fn ($query) => $query->orderBy('id'))
            ->when($sort === 'title', fn ($query) => $query->orderBy('title'))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('id'))
            ->when(! in_array($sort, ['oldest', 'title', 'newest'], true), fn ($query) => $query->orderBy('display_order')->orderBy('id'))
            ->paginate(24);

        return response()->json([
            'data' => $pages->getCollection()->map(fn (MenuBookPage $page): array => $this->transform($page))->values(),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
                'from' => $pages->firstItem(),
                'to' => $pages->lastItem(),
            ],
            'links' => [
                'first' => $pages->url(1),
                'last' => $pages->url($pages->lastPage()),
                'prev' => $pages->previousPageUrl(),
                'next' => $pages->nextPageUrl(),
            ],
        ]);
    }

    public function store(StoreMenuBookPageRequest $request): JsonResponse
    {
        $data = collect($request->validated())->except('image')->all();
        $data['image_path'] = $this->storeImage($request->file('image'));

        $page = MenuBookPage::query()->create($data);

        return response()->json([
            'data' => $this->transform($page),
            'message' => 'Menu page added successfully.',
        ], 201);
    }

    public function show(MenuBookPage $menuBookPage): JsonResponse
    {
        return response()->json(['data' => $this->transform($menuBookPage)]);
    }

    public function update(UpdateMenuBookPageRequest $request, MenuBookPage $menuBookPage): JsonResponse
    {
        $data = collect($request->validated())->except('image')->all();

        if ($request->hasFile('image')) {
            $previousPath = $menuBookPage->image_path;
            $data['image_path'] = $this->storeImage($request->file('image'));
            $this->deleteStoredImage($previousPath);
        }

        $menuBookPage->update($data);

        return response()->json([
            'data' => $this->transform($menuBookPage->fresh()),
            'message' => 'Menu page updated successfully.',
        ]);
    }

    public function destroy(MenuBookPage $menuBookPage): JsonResponse
    {
        $path = $menuBookPage->image_path;
        $menuBookPage->delete();
        $this->deleteStoredImage($path);

        return response()->json([
            'message' => 'Menu page deleted successfully.',
        ]);
    }

    public function toggleStatus(Request $request, MenuBookPage $menuBookPage): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $menuBookPage->update($validated);

        return response()->json([
            'data' => $this->transform($menuBookPage->fresh()),
            'message' => $validated['is_active']
                ? 'Menu page activated successfully.'
                : 'Menu page hidden successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(MenuBookPage $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'display_order' => $page->display_order,
            'is_active' => $page->is_active,
            'image' => [
                'url' => $page->imageUrl(),
                'path' => $page->image_path,
            ],
            'created_at' => $page->created_at?->toIso8601String(),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }

    private function storeImage(UploadedFile $file): string
    {
        return $file->store('menu-book', 'public');
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'menu-book/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
