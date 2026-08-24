<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Support\HomepageContent;
use Illuminate\Http\JsonResponse;

class HomepageController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => HomepageContent::publicPayload(),
        ]);
    }
}
