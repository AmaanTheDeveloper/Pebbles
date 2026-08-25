<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\GenerateOrderReceiptPdf;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Response;

class OrderReceiptController extends Controller
{
    /**
     * Download a professional order receipt PDF.
     *
     * Authorization mirrors order viewing:
     * - Customer: own orders only
     * - Manager: assigned branch only
     * - Super Admin: all orders
     */
    public function download(Order $order, GenerateOrderReceiptPdf $generateOrderReceiptPdf): Response
    {
        $this->authorize('view', $order);

        return $generateOrderReceiptPdf->download($order);
    }
}
