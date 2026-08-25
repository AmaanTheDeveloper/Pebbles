<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Support\BrandingSettings;
use App\Support\PublicCatalogCache;
use Illuminate\Http\JsonResponse;

class BrandingController extends Controller
{
    public function show(): JsonResponse
    {
        $payload = PublicCatalogCache::remember(
            PublicCatalogCache::BRANDING_KEY,
            fn (): array => BrandingSettings::publicPayload(),
        );

        return response()->json([
            'data' => $payload,
        ]);
    }
}
