<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Support\PopupSettings;
use Illuminate\Http\JsonResponse;

class PopupController extends Controller
{
    public function show(): JsonResponse
    {
        $popup = PopupSettings::publicPayload();

        return response()->json([
            'data' => $popup,
        ]);
    }
}
