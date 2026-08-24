<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\IndexCatalogDeliveryZoneRequest;
use App\Http\Resources\Catalog\DeliveryZoneResource;
use App\Models\DeliveryZone;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryZoneController extends Controller
{
    public function index(IndexCatalogDeliveryZoneRequest $request): AnonymousResourceCollection
    {
        $zones = DeliveryZone::query()
            ->catalog()
            ->with('branch')
            ->when(
                $request->filled('branch'),
                fn ($query) => $query->whereHas(
                    'branch',
                    fn ($branchQuery) => $branchQuery->catalog()->where('slug', $request->string('branch')->toString())
                )
            )
            ->get();

        return DeliveryZoneResource::collection($zones);
    }
}
