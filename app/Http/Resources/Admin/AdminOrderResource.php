<?php

namespace App\Http\Resources\Admin;

use App\Models\Order;
use App\Support\OrderStatusTransitions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class AdminOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $transitions = app(OrderStatusTransitions::class);

        return [
            'order_number' => $this->public_order_number,
            'created_at' => $this->created_at,
            'status' => $this->status?->value,
            'fulfillment_type' => $this->type?->value,
            'subtotal' => $this->subtotal,
            'delivery_fee' => $this->delivery_fee,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total' => $this->grand_total,
            'customer_notes' => $this->customer_notes,
            'customer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
                'slug' => $this->branch?->slug,
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
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
                'add_ons' => $item->relationLoaded('addOns')
                    ? $item->addOns->map(fn ($addOn) => [
                        'name' => $addOn->add_on_name,
                        'unit_price' => $addOn->unit_price,
                        'quantity' => $addOn->quantity,
                        'line_total' => $addOn->line_total,
                    ])->values()
                    : [],
            ])->values()),
            'payment' => $this->whenLoaded('payment', fn () => [
                'id' => $this->payment?->id,
                'method' => $this->payment?->method?->value,
                'status' => $this->payment?->status?->value,
                'amount' => $this->payment?->amount,
                'transaction_reference' => $this->payment?->transaction_reference,
                'requires_proof' => $this->payment?->requiresProof() ?? false,
                'proof' => $this->payment?->relationLoaded('proofs')
                    ? (($latest = $this->payment->proofs->sortByDesc('id')->first())
                        ? [
                            'id' => $latest->id,
                            'original_filename' => $latest->original_filename,
                            'uploaded_at' => $latest->uploaded_at,
                            'mime_type' => $latest->mime_type,
                            'size' => $latest->size,
                        ]
                        : null)
                    : null,
            ]),
            'status_history' => $this->whenLoaded('statusHistories', fn () => $this->statusHistories
                ->sortBy('created_at')
                ->values()
                ->map(fn ($history) => [
                    'from_status' => $history->from_status?->value,
                    'status' => $history->to_status?->value,
                    'note' => $history->note,
                    'changed_by' => $history->relationLoaded('changedBy')
                        ? $history->changedBy?->name
                        : null,
                    'created_at' => $history->created_at,
                ])),
            'allowed_next_statuses' => $this->status
                ? array_map(
                    fn ($status) => $status->value,
                    $transitions->allowedNext($this->status),
                )
                : [],
        ];
    }
}
