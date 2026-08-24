<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Show an order by public order number for the authenticated owner or superadmin.
     */
    public function show(Request $request, Order $order): OrderResource
    {
        $this->authorize('view', $order);

        $order->load(['items.addOns', 'payment', 'statusHistories', 'branch']);

        return new OrderResource($order);
    }
}
