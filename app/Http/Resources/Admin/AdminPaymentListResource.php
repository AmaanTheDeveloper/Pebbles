<?php

namespace App\Http\Resources\Admin;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Support\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class AdminPaymentListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestProof = $this->relationLoaded('proofs')
            ? $this->proofs->sortByDesc('id')->first()
            : null;

        return [
            'id' => $this->id,
            'method' => $this->method?->value,
            'status' => $this->status?->value,
            'amount' => $this->amount,
            'transaction_reference' => $this->transaction_reference,
            'requires_proof' => $this->requiresProof(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'verified_at' => $this->verified_at,
            'rejection_reason' => $this->rejection_reason,
            'verified_by' => $this->whenLoaded('verifier', fn () => [
                'name' => $this->verifier?->name,
            ]),
            'order_number' => $this->whenLoaded('order', fn () => $this->order?->public_order_number),
            'order_status' => $this->whenLoaded('order', fn () => $this->order?->status?->value),
            'customer' => $this->whenLoaded('order', fn () => [
                'name' => $this->order?->user?->name,
                'email' => $this->order?->user?->email,
            ]),
            'branch' => $this->whenLoaded('order', fn () => [
                'name' => $this->order?->branch?->name,
            ]),
            'proof' => $latestProof === null ? null : [
                'id' => $latestProof->id,
                'original_filename' => $latestProof->original_filename,
                'uploaded_at' => $latestProof->uploaded_at,
                'mime_type' => $latestProof->mime_type,
                'size' => $latestProof->size,
            ],
            'has_proof' => $latestProof !== null,
            'method_details' => $this->method === PaymentMethod::JazzCash ? [
                'business_name' => PaymentSettings::customerDetails('jazzcash')['business_name'] ?? null,
                'till_id' => PaymentSettings::customerDetails('jazzcash')['till_id'] ?? null,
                'qr_image' => PaymentSettings::jazzcashQrImagePayload(),
            ] : null,
        ];
    }
}
