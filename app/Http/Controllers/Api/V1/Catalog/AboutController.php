<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Support\AboutContent;
use App\Support\PublicCatalogCache;
use Illuminate\Http\JsonResponse;

class AboutController extends Controller
{
    public function show(): JsonResponse
    {
        $payload = PublicCatalogCache::remember(
            PublicCatalogCache::ABOUT_KEY,
            fn (): array => AboutContent::publicPayload(),
        );

        return response()->json([
            'data' => $payload,
        ]);
    }
}
