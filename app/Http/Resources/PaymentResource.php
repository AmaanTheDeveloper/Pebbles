<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'method' => $this->method?->value,
            'status' => $this->status?->value,
            'amount' => $this->amount,
            'transaction_reference' => $this->transaction_reference,
            'paid_at' => $this->paid_at,
            'verified_at' => $this->verified_at,
        ];
    }
}
