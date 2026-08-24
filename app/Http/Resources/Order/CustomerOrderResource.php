<?php

namespace App\Http\Resources\Order;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Support\CustomerOrderActions;
use App\Support\CustomerOrderLifecycle;
use App\Support\OrderEstimatePayload;
use App\Support\PaymentMethodCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer-facing order detail (no internal numeric order id).
 *
 * @mixin Order
 */
class CustomerOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $catalog = app(PaymentMethodCatalog::class);
        $payment = $this->relationLoaded('payment') ? $this->payment : null;
        $methodMeta = null;

        if ($payment?->method) {
            try {
                $methodMeta = $catalog->get($payment->method);
            } catch (\InvalidArgumentException) {
                $methodMeta = null;
            }
        }

        $latestProof = $payment?->relationLoaded('proofs')
            ? $payment->proofs->sortByDesc('id')->first()
            : $payment?->latestProof();

        return [
            'order_number' => $this->public_order_number,
            'status' => $this->status?->value,
            'confirmed_at' => $this->confirmed_at,
            'estimated_minutes' => $this->estimated_minutes,
            'estimated_time' => OrderEstimatePayload::for($this->resource),
            'fulfillment_type' => $this->type?->value,
            'created_at' => $this->created_at,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'delivery_fee' => $this->delivery_fee,
            'tax_amount' => $this->tax_amount,
            'total' => $this->grand_total,
            'customer_notes' => $this->customer_notes,
            'branch' => $this->whenLoaded('branch', fn () => [
                'name' => $this->branch?->name,
                'slug' => $this->branch?->slug,
                'address' => $this->branch?->address,
                'phone' => $this->branch?->phone,
            ]),
            'fulfillment' => [
                'table_number' => $this->table_number,
                'pickup_notes' => $this->pickup_notes,
                'delivery_zone_name' => $this->delivery_zone_name,
                'delivery_recipient_name' => $this->delivery_recipient_name,
                'delivery_phone' => $this->delivery_phone,
                'delivery_line1' => $this->delivery_line1,
                'delivery_line2' => $this->delivery_line2,
                'delivery_area' => $this->delivery_area,
                'delivery_city' => $this->delivery_city,
                'delivery_instructions' => $this->delivery_instructions,
            ],
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
                'add_ons' => $item->relationLoaded('addOns')
                    ? $item->addOns->map(fn ($addOn) => [
                        'id' => $addOn->id,
                        'add_on_id' => $addOn->add_on_id,
                        'name' => $addOn->add_on_name,
                        'unit_price' => $addOn->unit_price,
                        'quantity' => $addOn->quantity,
                        'line_total' => $addOn->line_total,
                    ])->values()
                    : [],
            ])->values()),
            'payment' => $payment === null ? null : [
                'method' => $payment->method?->value,
                'method_label' => $methodMeta['label'] ?? $payment->method?->value,
                'status' => $payment->status?->value,
                'amount' => $payment->amount,
                'transaction_reference' => $payment->transaction_reference,
                'requires_proof' => $payment->requiresProof(),
                'can_change_method' => $payment->canChangeMethod(),
                'can_upload_proof' => $payment->canUploadProof(),
                'instructions' => $methodMeta['instructions'] ?? '',
                'details' => $methodMeta['details'] ?? [],
                'qr_image' => $methodMeta['qr_image'] ?? null,
                'customer_message' => match ($payment->status) {
                    PaymentStatus::Submitted => 'Your payment proof has been submitted and is waiting for verification.',
                    PaymentStatus::Verified => 'Your payment has been verified.',
                    PaymentStatus::Rejected => filled($payment->rejection_reason)
                        ? $payment->rejection_reason
                        : 'Your payment proof could not be verified. Please upload a clearer receipt.',
                    default => null,
                },
                'proof' => $latestProof === null ? null : [
                    'id' => $latestProof->id,
                    'original_filename' => $latestProof->original_filename,
                    'uploaded_at' => $latestProof->uploaded_at,
                    'mime_type' => $latestProof->mime_type,
                    'size' => $latestProof->size,
                ],
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
            ],
            'status_history' => $this->whenLoaded('statusHistories', fn () => $this->statusHistories
                ->sortBy('created_at')
                ->values()
                ->map(fn ($history) => [
                    'from_status' => $history->from_status?->value,
                    'status' => $history->to_status?->value,
                    'created_at' => $history->created_at,
                ])),
            'customer_actions' => CustomerOrderActions::payloadFor($this->resource),
            'lifecycle' => CustomerOrderLifecycle::payloadFor($this->resource),
        ];
    }
}
