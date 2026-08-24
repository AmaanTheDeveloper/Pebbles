<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\OfferResource;
use App\Models\Offer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OfferController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $offers = Offer::query()->currentlyAvailable()->get();

        return OfferResource::collection($offers);
    }
}
