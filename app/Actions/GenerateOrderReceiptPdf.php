<?php

namespace App\Actions;

use App\Models\Order;
use App\Support\Orders\OrderReceiptData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class GenerateOrderReceiptPdf
{
    /**
     * Generate a downloadable PDF response from historical order data.
     *
     * Read-only: does not mutate order, payment, notifications, or email state.
     */
    public function download(Order $order): Response
    {
        $payload = OrderReceiptData::for($order);

        $pdf = Pdf::loadView('pdf.order-receipt', ['receipt' => $payload])
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true);

        return $pdf->download($payload['filename']);
    }
}
