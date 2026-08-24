<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use Illuminate\Http\JsonResponse;

class GalleryController extends Controller
{
    public function index(): JsonResponse
    {
        $items = GalleryItem::query()
            ->catalog()
            ->get()
            ->map(fn (GalleryItem $item): array => $item->toCustomerArray())
            ->values();

        return response()->json(['data' => $items]);
    }
}
