<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\PaymentMethodCatalog;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function index(PaymentMethodCatalog $catalog): JsonResponse
    {
        return response()->json([
            'data' => $catalog->enabled(),
        ]);
    }
}
