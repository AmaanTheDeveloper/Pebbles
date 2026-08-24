<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Delivery\StoreDeliveryZoneRequest;
use App\Models\DeliveryZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryZoneController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $zones = DeliveryZone::query()
            ->with('branch')
            ->when(
                $request->filled('branch_id'),
                fn ($q) => $q->where('branch_id', $request->integer('branch_id'))
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return JsonResource::collection($zones);
    }

    public function store(StoreDeliveryZoneRequest $request): JsonResponse
    {
        $zone = DeliveryZone::query()->create($request->validated());

        return response()->json(['data' => $zone->load('branch')], 201);
    }

    public function show(DeliveryZone $deliveryZone): JsonResponse
    {
        return response()->json(['data' => $deliveryZone->load('branch')]);
    }

    public function update(Request $request, DeliveryZone $deliveryZone): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'branch_id' => ['sometimes', 'exists:branches,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'delivery_fee' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $deliveryZone->update($validated);

        return response()->json(['data' => $deliveryZone->fresh('branch')]);
    }

    public function destroy(DeliveryZone $deliveryZone): JsonResponse
    {
        $deliveryZone->update(['is_active' => false]);

        return response()->json(['data' => $deliveryZone->fresh()]);
    }
}
