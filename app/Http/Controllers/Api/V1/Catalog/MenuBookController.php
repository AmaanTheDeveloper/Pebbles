<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Models\MenuBookPage;
use Illuminate\Http\JsonResponse;

class MenuBookController extends Controller
{
    public function index(): JsonResponse
    {
        $pages = MenuBookPage::query()
            ->catalog()
            ->get()
            ->map(fn (MenuBookPage $page): array => $page->toCustomerArray())
            ->values();

        return response()->json(['data' => $pages]);
    }
}
