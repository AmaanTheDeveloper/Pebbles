<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\IndexCatalogProductRequest;
use App\Http\Resources\Catalog\ProductDetailResource;
use App\Http\Resources\Catalog\ProductListResource;
use App\Models\Product;
use App\Support\ProductAddOnRules;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(IndexCatalogProductRequest $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->catalog()
            ->with([
                'category',
                'images',
                'variants' => fn ($query) => $query->catalog()->with('image'),
            ])
            ->when(
                $request->filled('category'),
                fn ($query) => $query->whereHas(
                    'category',
                    fn ($categoryQuery) => $categoryQuery->catalog()->where('slug', $request->string('category')->toString())
                )
            )
            ->when(
                $request->filled('search'),
                function ($query) use ($request): void {
                    $term = '%'.$request->string('search')->toString().'%';

                    $query->where(function ($searchQuery) use ($term): void {
                        $searchQuery
                            ->where('name', 'like', $term)
                            ->orWhere('short_description', 'like', $term);
                    });
                }
            )
            ->when(
                $request->boolean('featured'),
                fn ($query) => $query->featured()
            )
            ->get();

        return ProductListResource::collection($products);
    }

    public function show(Product $product): ProductDetailResource
    {
        $product->load([
            'category',
            'images',
            'variants' => fn ($query) => $query->catalog()->with('image'),
        ]);

        $product->setRelation('addOnGroups', ProductAddOnRules::groupsForProduct($product));

        return new ProductDetailResource($product);
    }
}
