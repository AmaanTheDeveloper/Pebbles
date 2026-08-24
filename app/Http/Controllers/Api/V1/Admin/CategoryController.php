<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $categories = Category::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return JsonResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create($request->validated());

        return response()->json(['data' => $category], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json(['data' => $category]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $category->update($validated);

        return response()->json(['data' => $category->fresh()]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Category has products. Deactivate it instead of deleting.',
            ], 422);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
