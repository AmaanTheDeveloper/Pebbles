<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Models\Branch;
use App\Support\OpeningHours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $branches = Branch::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(20);

        return JsonResource::collection($branches);
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $data = collect($request->validated())->except('image')->all();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeBranchImage($request->file('image'));
        }

        $branch = Branch::query()->create($data);

        return response()->json(['data' => $this->transformBranch($branch)], 201);
    }

    public function show(Branch $branch): JsonResponse
    {
        return response()->json(['data' => $this->transformBranch($branch->load('deliveryZones'))]);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        if (is_string($request->input('opening_hours'))) {
            $decoded = json_decode($request->input('opening_hours'), true);
            if (is_array($decoded)) {
                $request->merge(['opening_hours' => $decoded]);
            }
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('branches', 'slug')->ignore($branch->id)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branch->id)],
            'address' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'opens_at' => ['nullable', 'date_format:H:i'],
            'closes_at' => ['nullable', 'date_format:H:i'],
            'opening_hours' => ['nullable', 'array'],
            'opening_hours.*.closed' => ['sometimes', 'boolean'],
            'opening_hours.*.opens' => ['nullable', 'date_format:H:i'],
            'opening_hours.*.closes' => ['nullable', 'date_format:H:i'],
            'opening_hours.*.note' => ['nullable', 'string', 'max:160'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $data = collect($validated)->except('image')->all();

        if ($request->hasFile('image')) {
            $previousPath = $branch->image_path;
            $data['image_path'] = $this->storeBranchImage($request->file('image'));
            $this->deleteStoredBranchImage($previousPath);
        }

        $branch->update($data);

        return response()->json(['data' => $this->transformBranch($branch->fresh())]);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        $branch->update(['is_active' => false]);

        return response()->json(['data' => $this->transformBranch($branch->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformBranch(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
            'code' => $branch->code,
            'address' => $branch->address,
            'phone' => $branch->phone,
            'whatsapp' => $branch->whatsapp,
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'map_url' => $branch->map_url,
            'image_path' => $branch->image_path,
            'image_url' => $branch->imageUrl(),
            'opens_at' => $branch->opens_at,
            'closes_at' => $branch->closes_at,
            'opening_hours' => $branch->resolvedOpeningHours(),
            'opening_hours_schedule' => OpeningHours::formattedSchedule($branch->resolvedOpeningHours()),
            'display_order' => $branch->display_order,
            'is_active' => $branch->is_active,
            'delivery_zones' => $branch->relationLoaded('deliveryZones') ? $branch->deliveryZones : null,
        ];
    }

    private function storeBranchImage(UploadedFile $file): string
    {
        return $file->store('branches', 'public');
    }

    private function deleteStoredBranchImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
