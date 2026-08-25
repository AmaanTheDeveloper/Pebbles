<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Support\HomepageContent;
use App\Support\PublicCatalogCache;
use Illuminate\Http\JsonResponse;

class HomepageController extends Controller
{
    public function show(): JsonResponse
    {
        $payload = PublicCatalogCache::remember(
            PublicCatalogCache::HOMEPAGE_KEY,
            fn (): array => HomepageContent::publicPayload(),
        );

        return response()->json([
            'data' => $payload,
        ]);
    }
}
