<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Models\AddOnGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

class AddOnGroupController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $groups = AddOnGroup::query()
            ->with('addOns')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

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

        return response()->json(['data' => $group->load('addOns')], 201);
    }

    public function show(AddOnGroup $addOnGroup): JsonResponse
    {
        return response()->json(['data' => $addOnGroup->load('addOns')]);
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

        return response()->json(['data' => $addOnGroup->fresh('addOns')]);
    }

    public function destroy(AddOnGroup $addOnGroup): JsonResponse
    {
        $addOnGroup->update(['is_active' => false]);

        return response()->json(['data' => $addOnGroup->fresh()]);
    }

    public function storeOption(Request $request, AddOnGroup $addOnGroup): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $addOn = $addOnGroup->addOns()->create($validated);

        return response()->json(['data' => $addOn], 201);
    }

    public function updateOption(Request $request, AddOnGroup $addOnGroup, AddOn $addOn): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        abort_unless($addOn->add_on_group_id === $addOnGroup->id, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $addOn->update($validated);

        return response()->json(['data' => $addOn->fresh()]);
    }
}
