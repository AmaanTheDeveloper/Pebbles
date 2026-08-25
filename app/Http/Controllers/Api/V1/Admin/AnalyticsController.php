<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsFilterRequest;
use App\Services\Admin\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin analytics HTTP surface.
 *
 * Heavy reports: overview (dual-period KPIs), customers (cohort scans),
 * products (order_items rollups), and export (streamed CSV). Prefer narrow date filters.
 */
class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analytics,
    ) {}

    public function overview(AnalyticsFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->overview($request->filters()),
        ]);
    }

    public function sales(AnalyticsFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->sales($request->filters()),
        ]);
    }

    public function orders(AnalyticsFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->orders($request->filters()),
        ]);
    }

    public function products(AnalyticsFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->products($request->filters()),
        ]);
    }

    public function categories(AnalyticsFilterRequest $request): JsonResponse
    {
        $payload = $this->analytics->products($request->filters());

        return response()->json([
            'data' => [
                'filters' => $payload['filters'],
                'categories' => $payload['categories'],
            ],
        ]);
    }

    public function branches(AnalyticsFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->branches($request->filters()),
        ]);
    }

    public function customers(AnalyticsFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->customers($request->filters()),
        ]);
    }

    public function paymentMethods(AnalyticsFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->paymentMethods($request->filters()),
        ]);
    }

    public function deliveryZones(AnalyticsFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->deliveryZones($request->filters()),
        ]);
    }

    public function export(AnalyticsFilterRequest $request): StreamedResponse
    {
        return $this->analytics->exportCsv($request->filters());
    }
}
