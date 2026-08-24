<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CheckoutCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CheckoutRequest;
use App\Http\Resources\Order\CheckoutOrderResource;
use App\Models\Order;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutCart $checkoutCart): CheckoutOrderResource
    {
        $this->authorize('create', Order::class);

        $order = $checkoutCart->handle($request->user(), $request->validated());

        return new CheckoutOrderResource($order);
    }
}
