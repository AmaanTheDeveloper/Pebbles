<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\ProcessAndStoreProductImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\PublicMediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'is_available' => ['sometimes', 'nullable', 'boolean'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $products = Product::query()
            ->with(['category', 'variants', 'images', 'addOnGroups'])
            ->when(
                filled($validated['search'] ?? null),
                fn ($q) => $q->where('name', 'like', '%'.$validated['search'].'%')
            )
            ->when(
                filled($validated['category_id'] ?? null),
                fn ($q) => $q->where('category_id', $validated['category_id'])
            )
            ->when(
                array_key_exists('is_available', $validated) && $validated['is_available'] !== null,
                fn ($q) => $q->where('is_available', (bool) $validated['is_available'])
            )
            ->when(
                array_key_exists('is_active', $validated) && $validated['is_active'] !== null,
                fn ($q) => $q->where('is_active', (bool) $validated['is_active'])
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->through(function (Product $product) {
                $primary = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

                $product->setAttribute('primary_image', $primary === null ? null : [
                    'id' => $primary->id,
                    'url' => PublicMediaUrl::fromPath($primary->path),
                    'alt_text' => $primary->alt_text,
                ]);

                return $product;
            });

        return JsonResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->safe()->except('add_on_group_ids');
        $product = Product::query()->create($data);

        if ($request->filled('add_on_group_ids')) {
            $product->addOnGroups()->sync($request->validated('add_on_group_ids'));
        }

        return response()->json([
            'data' => $product->fresh(['category', 'variants', 'images', 'addOnGroups']),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'variants.image', 'images', 'addOnGroups.addOns']);

        return response()->json([
            'data' => array_merge($product->toArray(), [
                'variants' => $product->variants->map(fn (ProductVariant $variant) => $this->variantPayload($variant))->values(),
                'images' => $product->images->map(fn (ProductImage $image) => [
                    'id' => $image->id,
                    'path' => $image->path,
                    'url' => PublicMediaUrl::fromPath($image->path),
                    'alt_text' => $image->alt_text,
                    'is_primary' => $image->is_primary,
                    'sort_order' => $image->sort_order,
                ]),
            ]),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product->id)],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'is_active' => ['sometimes', 'boolean'],
            'is_available' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'add_on_group_ids' => ['sometimes', 'array'],
            'add_on_group_ids.*' => ['integer', 'exists:add_on_groups,id'],
        ]);

        $product->update(collect($validated)->except('add_on_group_ids')->all());

        if (array_key_exists('add_on_group_ids', $validated)) {
            $product->addOnGroups()->sync($validated['add_on_group_ids'] ?? []);
        }

        return response()->json([
            'data' => $product->fresh(['category', 'variants', 'images', 'addOnGroups']),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->update(['is_active' => false, 'is_available' => false]);

        return response()->json(['data' => $product->fresh()]);
    }

    public function storeVariant(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'product_image_id' => [
                'nullable',
                'integer',
                Rule::exists('product_images', 'id')->where(fn ($query) => $query->where('product_id', $product->id)),
            ],
        ]);

        $variant = $product->variants()->create($validated);

        return response()->json(['data' => $this->variantPayload($variant->fresh('image'))], 201);
    }

    public function updateVariant(Request $request, Product $product, ProductVariant $variant): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        abort_unless($variant->product_id === $product->id, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'price' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'product_image_id' => [
                'nullable',
                'integer',
                Rule::exists('product_images', 'id')->where(fn ($query) => $query->where('product_id', $product->id)),
            ],
        ]);

        $variant->update($validated);

        return response()->json(['data' => $this->variantPayload($variant->fresh('image'))]);
    }

    public function storeImage(Request $request, Product $product, ProcessAndStoreProductImage $processor): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.ProcessAndStoreProductImage::MAX_UPLOAD_KB,
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        $payload = $processor->handle(
            $product,
            $request->file('image'),
            $validated['alt_text'] ?? null,
            array_key_exists('is_primary', $validated) ? $request->boolean('is_primary') : null,
        );

        return response()->json(['data' => $payload], 201);
    }

    public function setPrimaryImage(Request $request, Product $product, ProductImage $image, ProcessAndStoreProductImage $processor): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $updated = $processor->setPrimary($product, $image);

        return response()->json(['data' => $processor->serialize($updated)]);
    }

    public function destroyImage(Request $request, Product $product, ProductImage $image, ProcessAndStoreProductImage $processor): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $processor->delete($product, $image);

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function variantPayload(ProductVariant $variant): array
    {
        $image = $variant->relationLoaded('image') ? $variant->image : $variant->image()->first();

        return [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'name' => $variant->name,
            'sku' => $variant->sku,
            'price' => $variant->price,
            'is_active' => $variant->is_active,
            'sort_order' => $variant->sort_order,
            'product_image_id' => $variant->product_image_id,
            'image' => $image === null ? null : [
                'id' => $image->id,
                'url' => PublicMediaUrl::fromPath($image->path),
                'alt_text' => $image->alt_text,
            ],
            'created_at' => $variant->created_at,
            'updated_at' => $variant->updated_at,
        ];
    }
}
