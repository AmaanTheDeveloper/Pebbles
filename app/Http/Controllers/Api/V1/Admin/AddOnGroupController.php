<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Models\AddOnGroup;
use App\Models\Category;
use App\Support\ProductAddOnRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AddOnGroupController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $groups = AddOnGroup::query()
            ->with(['addOns.categories:id,name,slug'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        $groups->setCollection(
            $groups->getCollection()->map(fn (AddOnGroup $group): array => $this->transformGroup($group))
        );

        return JsonResource::collection($groups);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:add_on_groups,slug'],
            'description' => ['nullable', 'string'],
            'is_required' => ['sometimes', 'boolean'],
            'min_selections' => ['sometimes', 'integer', 'min:0'],
            'max_selections' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $group = AddOnGroup::query()->create($validated);

        return response()->json(['data' => $this->transformGroup($group->load(['addOns.categories:id,name,slug']))], 201);
    }

    public function show(AddOnGroup $addOnGroup): JsonResponse
    {
        return response()->json([
            'data' => $this->transformGroup($addOnGroup->load(['addOns.categories:id,name,slug'])),
        ]);
    }

    public function update(Request $request, AddOnGroup $addOnGroup): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('add_on_groups', 'slug')->ignore($addOnGroup->id)],
            'description' => ['nullable', 'string'],
            'is_required' => ['sometimes', 'boolean'],
            'min_selections' => ['sometimes', 'integer', 'min:0'],
            'max_selections' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $addOnGroup->update($validated);

        return response()->json([
            'data' => $this->transformGroup($addOnGroup->fresh(['addOns.categories:id,name,slug'])),
        ]);
    }

    public function destroy(AddOnGroup $addOnGroup): JsonResponse
    {
        $addOnGroup->update(['is_active' => false]);

        return response()->json(['data' => $this->transformGroup($addOnGroup->fresh(['addOns.categories:id,name,slug']))]);
    }

    public function assignableCategories(Request $request, AddOnGroup $addOnGroup): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active', 'sort_order']);

        return response()->json([
            'data' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
            ])->values(),
        ]);
    }

    public function storeOption(Request $request, AddOnGroup $addOnGroup): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $requiresCategories = ProductAddOnRules::categorySlugsForGroup($addOnGroup->slug) !== [];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'category_ids' => $requiresCategories
                ? ['required', 'array', 'min:1']
                : ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ]);

        $categoryIds = array_values(array_map('intval', $validated['category_ids'] ?? []));

        if ($categoryIds !== []) {
            $this->assertCategoriesAssignable($categoryIds);
        } elseif ($requiresCategories) {
            throw ValidationException::withMessages([
                'category_ids' => 'Please select at least one category.',
            ]);
        }

        $addOn = $addOnGroup->addOns()->create(collect($validated)->except('category_ids')->all());
        $addOn->categories()->sync($categoryIds);

        return response()->json([
            'data' => $this->transformAddOn($addOn->fresh(['categories:id,name,slug'])),
        ], 201);
    }

    public function updateOption(Request $request, AddOnGroup $addOnGroup, AddOn $addOn): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        abort_unless($addOn->add_on_group_id === $addOnGroup->id, 404);

        $requiresCategories = ProductAddOnRules::categorySlugsForGroup($addOnGroup->slug) !== [];

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'category_ids' => $requiresCategories
                ? ['sometimes', 'array', 'min:1']
                : ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ]);

        if (array_key_exists('category_ids', $validated)) {
            $categoryIds = array_values(array_map('intval', $validated['category_ids']));

            if ($requiresCategories && $categoryIds === []) {
                throw ValidationException::withMessages([
                    'category_ids' => 'Please select at least one category.',
                ]);
            }

            if ($categoryIds !== []) {
                $this->assertCategoriesAssignable($categoryIds);
            }

            $addOn->categories()->sync($categoryIds);
        }

        $addOn->update(collect($validated)->except('category_ids')->all());

        return response()->json([
            'data' => $this->transformAddOn($addOn->fresh(['categories:id,name,slug'])),
        ]);
    }

    /**
     * @param  list<int>  $categoryIds
     */
    private function assertCategoriesAssignable(array $categoryIds): void
    {
        $count = Category::query()->whereIn('id', $categoryIds)->count();

        if ($count !== count($categoryIds)) {
            throw ValidationException::withMessages([
                'category_ids' => 'One or more selected categories are invalid.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function transformGroup(AddOnGroup $group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'slug' => $group->slug,
            'description' => $group->description,
            'is_required' => $group->is_required,
            'min_selections' => $group->min_selections,
            'max_selections' => $group->max_selections,
            'is_active' => $group->is_active,
            'sort_order' => $group->sort_order,
            'created_at' => $group->created_at,
            'updated_at' => $group->updated_at,
            'add_ons' => $group->relationLoaded('addOns')
                ? $group->addOns->map(fn (AddOn $addOn): array => $this->transformAddOn($addOn))->values()->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformAddOn(AddOn $addOn): array
    {
        $categories = $addOn->relationLoaded('categories') ? $addOn->categories : collect();

        return [
            'id' => $addOn->id,
            'add_on_group_id' => $addOn->add_on_group_id,
            'name' => $addOn->name,
            'price' => $addOn->price,
            'is_active' => $addOn->is_active,
            'sort_order' => $addOn->sort_order,
            'category_ids' => $categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'categories' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->values()->all(),
            'created_at' => $addOn->created_at,
            'updated_at' => $addOn->updated_at,
        ];
    }
}
