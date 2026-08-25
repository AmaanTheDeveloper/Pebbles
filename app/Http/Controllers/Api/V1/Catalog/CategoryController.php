<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\CategoryResource;
use App\Models\Category;
use App\Support\PublicCatalogCache;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = PublicCatalogCache::remember(PublicCatalogCache::CATEGORIES_KEY, fn () => Category::query()->catalog()->get());

        return CategoryResource::collection($categories);
    }
}
