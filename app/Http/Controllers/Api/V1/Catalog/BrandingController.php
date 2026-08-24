<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Support\BrandingSettings;
use Illuminate\Http\JsonResponse;

class BrandingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => BrandingSettings::publicPayload(),
        ]);
    }
}
